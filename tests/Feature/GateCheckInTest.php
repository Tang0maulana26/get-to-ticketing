<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GateCheckInTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $gateStaff;
    private User $customer;
    private Event $event;
    private TicketCategory $ticketCategory;
    private BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingService = app(BookingService::class);

        $this->admin = User::factory()->create([
            'name' => 'Admin Gate',
            'username' => 'admingate',
            'email' => 'admin.gate@example.com',
        ]);
        $this->admin->role = 'admin';
        $this->admin->save();

        $this->gateStaff = User::factory()->create([
            'name' => 'Staff Satpam',
            'username' => 'staffgate',
            'email' => 'staff.gate@example.com',
        ]);
        $this->gateStaff->role = 'gate_staff';
        $this->gateStaff->save();

        $this->customer = User::factory()->create([
            'name' => 'Buyer John',
            'username' => 'buyerjohn',
            'email' => 'buyer.john@example.com',
        ]);

        $cat = Category::firstOrCreate(['slug' => 'konser-musik'], ['name' => 'Konser Musik']);

        $this->event = Event::create([
            'category_id' => $cat->id,
            'title' => 'Jakarta Soundwave 2026',
            'slug' => 'jakarta-soundwave-2026-'.Str::random(5),
            'summary' => 'Konser akbar',
            'description' => 'Deskripsi konser',
            'venue' => 'Stadion Madya GBK',
            'city' => 'Jakarta',
            'address' => 'Senayan, Jakarta',
            'status' => 'published',
            'start_time' => now()->addDays(7),
            'end_time' => now()->addDays(7)->addHours(5),
        ]);

        $this->ticketCategory = TicketCategory::create([
            'event_id' => $this->event->id,
            'name' => 'Festival A',
            'price' => 500000,
            'capacity' => 100,
            'stock' => 50,
            'max_per_order' => 4,
            'active' => true,
        ]);
    }

    private function createPaidOrderAndGetTicket(): Ticket
    {
        $order = $this->bookingService->checkout(
            $this->customer,
            $this->event,
            [$this->ticketCategory->id => 1],
            (string) Str::uuid()
        );

        $this->bookingService->payDemo($order);

        $order->refresh();
        return $order->items->first()->tickets->first();
    }

    public function test_guest_and_customer_cannot_access_gate_scanner()
    {
        // Guest redirected to login
        $this->get(route('gate.scan'))->assertRedirect(route('login'));

        // Customer gets 403 Forbidden
        $this->actingAs($this->customer)
            ->get(route('gate.scan'))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->postJson(route('gate.check-in'), ['code' => 'dummy'])
            ->assertForbidden();
    }

    public function test_admin_and_gate_staff_can_access_scanner_ui()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('gate.scan'))
            ->assertOk()
            ->assertSee('Jakarta Soundwave 2026')
            ->assertSee('jsqr.min.js')
            ->assertSee('gate-scanner.js');

        $this->assertStringContainsString(
            'camera=(self)',
            (string) $response->headers->get('Permissions-Policy')
        );

        $this->actingAs($this->gateStaff)
            ->get(route('gate.scan'))
            ->assertOk()
            ->assertSee('Jakarta Soundwave 2026');
    }

    public function test_valid_ticket_check_in_marks_as_used()
    {
        $ticket = $this->createPaidOrderAndGetTicket();
        $this->assertEquals('valid', $ticket->status);
        $this->assertNull($ticket->checked_in_at);

        $payload = $ticket->getQrPayload();

        $response = $this->actingAs($this->gateStaff)
            ->postJson(route('gate.check-in'), [
                'code' => $payload,
                'event_id' => $this->event->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => 'valid',
                'ticket' => [
                    'code' => $ticket->code,
                    'status' => 'used',
                    'holder' => 'Buyer John',
                ],
            ]);

        $ticket->refresh();
        $this->assertEquals('used', $ticket->status);
        $this->assertNotNull($ticket->checked_in_at);
    }

    public function test_duplicate_check_in_is_rejected()
    {
        $ticket = $this->createPaidOrderAndGetTicket();
        $payload = $ticket->getQrPayload();

        // 1st Check-in: Success
        $this->actingAs($this->admin)
            ->postJson(route('gate.check-in'), [
                'code' => $payload,
                'event_id' => $this->event->id,
            ])
            ->assertOk();

        // 2nd Check-in: Rejected with already_used
        $response = $this->actingAs($this->admin)
            ->postJson(route('gate.check-in'), [
                'code' => $payload,
                'event_id' => $this->event->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'status' => 'already_used',
            ]);
    }

    public function test_forged_hmac_signature_is_rejected()
    {
        $ticket = $this->createPaidOrderAndGetTicket();
        $fakePayload = $ticket->code . '.deadbeef12345678';

        $response = $this->actingAs($this->gateStaff)
            ->postJson(route('gate.check-in'), [
                'code' => $fakePayload,
                'event_id' => $this->event->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'status' => 'forged',
            ]);

        $ticket->refresh();
        $this->assertEquals('valid', $ticket->status);
    }

    public function test_invalid_uuid_syntax_returns_clean_not_found_without_sql_error()
    {
        $response = $this->actingAs($this->gateStaff)
            ->postJson(route('gate.check-in'), [
                'code' => '47f734b1-e2ea-4377-bb41-30eba959125', // invalid UUID
                'event_id' => $this->event->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'status' => 'not_found',
            ]);
    }

    public function test_check_in_for_wrong_event_is_rejected()
    {
        $ticket = $this->createPaidOrderAndGetTicket();

        // Another event
        $cat = Category::first();
        $otherEvent = Event::create([
            'category_id' => $cat->id,
            'title' => 'Bandung Jazz Night',
            'slug' => 'bandung-jazz-night-'.Str::random(5),
            'summary' => 'Other event',
            'description' => 'Other event',
            'venue' => 'Sasana Budaya',
            'city' => 'Bandung',
            'address' => 'Bandung',
            'status' => 'published',
            'start_time' => now()->addDays(20),
            'end_time' => now()->addDays(20)->addHours(4),
        ]);

        $response = $this->actingAs($this->gateStaff)
            ->postJson(route('gate.check-in'), [
                'code' => $ticket->getQrPayload(),
                'event_id' => $otherEvent->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'status' => 'wrong_event',
            ]);

        $ticket->refresh();
        $this->assertEquals('valid', $ticket->status);
    }

    public function test_manifest_endpoint_provides_tickets_for_offline_cache()
    {
        $ticket = $this->createPaidOrderAndGetTicket();

        $response = $this->actingAs($this->gateStaff)
            ->getJson(route('gate.manifest', $this->event));

        $response->assertOk()
            ->assertJsonStructure([
                'event_id',
                'event_title',
                'total_tickets',
                'synced_at',
                'tickets' => [
                    '*' => [
                        'code',
                        'signature',
                        'status',
                        'holder',
                        'ticket_name',
                    ],
                ],
            ]);

        $this->assertEquals(1, $response->json('total_tickets'));
        $this->assertEquals($ticket->code, $response->json('tickets.0.code'));
        $this->assertEquals($ticket->getHmacSignature(), $response->json('tickets.0.signature'));
    }

    public function test_batch_sync_updates_offline_check_ins()
    {
        $ticket = $this->createPaidOrderAndGetTicket();
        $this->assertEquals('valid', $ticket->status);

        $checkinTime = now()->subMinutes(10)->toIso8601String();

        $response = $this->actingAs($this->gateStaff)
            ->postJson(route('gate.sync-batch'), [
                'check_ins' => [
                    [
                        'code' => $ticket->code,
                        'checked_in_at' => $checkinTime,
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'synced_count' => 1,
            ]);

        $ticket->refresh();
        $this->assertEquals('used', $ticket->status);
    }

    public function test_gate_system_lock_and_start_time_window_restriction()
    {
        $ticket = $this->createPaidOrderAndGetTicket();
        $payload = $ticket->getQrPayload();

        // 1. Lock the gate system completely (system_active = false)
        $this->actingAs($this->admin)
            ->postJson(route('gate.settings'), [
                'event_id' => $this->event->id,
                'system_active' => false,
                'time_lock_enabled' => false,
                'hours_before_start' => 24,
            ])
            ->assertOk()
            ->assertJsonPath('gate_config.system_active', false);

        $this->actingAs($this->gateStaff)
            ->postJson(route('gate.check-in'), [
                'code' => $payload,
                'event_id' => $this->event->id,
            ])
            ->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'status' => 'gate_closed',
            ]);

        // 2. Enable system, but lock check-in to H-1 (24 hours before start_time; event is 7 days away)
        $this->actingAs($this->admin)
            ->postJson(route('gate.settings'), [
                'event_id' => $this->event->id,
                'system_active' => true,
                'time_lock_enabled' => true,
                'hours_before_start' => 24,
            ])
            ->assertOk();

        $this->actingAs($this->gateStaff)
            ->postJson(route('gate.check-in'), [
                'code' => $payload,
                'event_id' => $this->event->id,
            ])
            ->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'status' => 'too_early',
            ]);

        // 3. Turn off time lock (or set custom hours covering 7 days = 200 hours) -> Check-in succeeds!
        $this->actingAs($this->admin)
            ->postJson(route('gate.settings'), [
                'event_id' => $this->event->id,
                'system_active' => true,
                'time_lock_enabled' => false,
                'hours_before_start' => 24,
            ])
            ->assertOk();

        $this->actingAs($this->gateStaff)
            ->postJson(route('gate.check-in'), [
                'code' => $payload,
                'event_id' => $this->event->id,
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => 'valid',
            ]);
    }

    public function test_multi_device_batch_sync_detects_offline_conflicts_and_returns_used_tickets()
    {
        $ticket = $this->createPaidOrderAndGetTicket();

        // Device 1 checks in the ticket first
        $this->actingAs($this->admin)
            ->postJson(route('gate.check-in'), [
                'code' => $ticket->getQrPayload(),
                'event_id' => $this->event->id,
            ])
            ->assertOk();

        // Device 2 was offline and tries to sync the same ticket later
        $response = $this->actingAs($this->gateStaff)
            ->postJson(route('gate.sync-batch'), [
                'event_id' => $this->event->id,
                'check_ins' => [
                    [
                        'code' => $ticket->code,
                        'checked_in_at' => now()->toIso8601String(),
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'synced_count' => 0,
                'conflict_count' => 1,
            ]);

        $this->assertEquals($ticket->code, $response->json('conflicts.0.code'));
        $this->assertEquals($ticket->code, $response->json('used_tickets.0.code'));
    }

    public function test_only_admin_can_update_gate_settings()
    {
        // Gate staff receives 403 Forbidden
        $this->actingAs($this->gateStaff)
            ->postJson(route('gate.settings'), [
                'event_id' => $this->event->id,
                'system_active' => false,
                'time_lock_enabled' => false,
                'hours_before_start' => 24,
            ])
            ->assertForbidden();

        // Customer receives 403 Forbidden
        $this->actingAs($this->customer)
            ->postJson(route('gate.settings'), [
                'event_id' => $this->event->id,
                'system_active' => false,
                'time_lock_enabled' => false,
                'hours_before_start' => 24,
            ])
            ->assertForbidden();

        // Admin succeeds
        $this->actingAs($this->admin)
            ->postJson(route('gate.settings'), [
                'event_id' => $this->event->id,
                'system_active' => true,
                'time_lock_enabled' => false,
                'hours_before_start' => 24,
            ])
            ->assertOk();
    }

    public function test_gate_scanner_supports_english_and_indonesian_locales()
    {
        // Indonesian Locale
        $this->actingAs($this->admin)
            ->withSession(['locale' => 'id'])
            ->get(route('gate.scan'))
            ->assertOk()
            ->assertSee('Pemindai Tiket Pintu Masuk')
            ->assertSee('Unduh Manifest Offline')
            ->assertSee('Kamera Pemindai');

        // English Locale
        $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->get(route('gate.scan'))
            ->assertOk()
            ->assertSee('Gate Ticket Scanner')
            ->assertSee('Download Offline Manifest')
            ->assertSee('Camera Scanner');
    }
}
