<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Event;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'ticketing_test') {
            throw new \RuntimeException('Tests require the isolated ticketing_test database.');
        }
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->role = 'admin';
        $user->save();
        return $user;
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function concert(): array
    {
        $cat = Category::firstOrCreate(['slug' => 'music'], ['name' => 'Musik']);
        $event = Event::create([
            'category_id' => $cat->id,
            'title' => 'Rock Festival',
            'slug' => 'rock-fest-' . Str::random(6),
            'summary' => 'Rock concert',
            'description' => 'Rock concert description',
            'venue' => 'Gelora',
            'city' => 'Jakarta',
            'address' => 'Jl. Pintu 1',
            'start_time' => now()->addDays(20),
            'status' => 'published',
        ]);
        $ticket = $event->ticketCategories()->create([
            'name' => 'Regular',
            'price' => 100000,
            'capacity' => 50,
            'stock' => 50,
            'max_per_order' => 6,
            'active' => true,
        ]);

        return [$event, $ticket];
    }

    public function test_admin_can_create_and_manage_discounts(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.discounts.index'))
            ->assertOk()
            ->assertSee('Kode Diskon');

        $this->actingAs($admin)
            ->post(route('admin.discounts.store'), [
                'code' => 'PROMO20',
                'name' => 'Diskon 20 Persen',
                'type' => 'percent',
                'value' => 20,
                'min_order_amount' => 100000,
                'max_discount_amount' => 50000,
                'usage_limit' => 5,
                'active' => true,
            ])
            ->assertRedirect(route('admin.discounts.index'));

        $this->assertDatabaseHas('discounts', [
            'code' => 'PROMO20',
            'value' => 20,
            'used_count' => 0,
        ]);
    }

    public function test_discount_check_endpoint(): void
    {
        Discount::create([
            'code' => 'HEMAT50K',
            'name' => 'Potongan 50 Ribu',
            'type' => 'fixed',
            'value' => 50000,
            'min_order_amount' => 100000,
            'active' => true,
        ]);

        // Below minimum
        $this->postJson(route('discounts.check'), [
            'code' => 'HEMAT50K',
            'subtotal' => 50000,
        ])->assertStatus(422)
          ->assertJsonPath('ok', false);

        // Valid
        $this->postJson(route('discounts.check'), [
            'code' => 'HEMAT50K',
            'subtotal' => 200000,
        ])->assertOk()
          ->assertJsonPath('ok', true)
          ->assertJsonPath('discount_amount', 50000)
          ->assertJsonPath('final_total', 150000);
    }

    public function test_checkout_applies_percentage_discount_and_updates_used_count(): void
    {
        $customer = $this->customer();
        [$event, $ticket] = $this->concert();

        $discount = Discount::create([
            'code' => 'FESTIVAL10',
            'name' => 'Diskon 10 Persen',
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 0,
            'active' => true,
        ]);

        $order = app(BookingService::class)->checkout(
            $customer,
            $event,
            [$ticket->id => 2],
            (string) Str::uuid(),
            'FESTIVAL10'
        );

        // Subtotal = 2 * 100000 = 200000
        // Discount 10% = 20000
        // Total = 180000
        $this->assertEquals(200000, $order->subtotal);
        $this->assertEquals(20000, $order->discount_amount);
        $this->assertEquals(180000, $order->total);
        $this->assertEquals('FESTIVAL10', $order->discount_code);

        $this->assertEquals(1, $discount->fresh()->used_count);

        // Releasing order decrements used count
        app(BookingService::class)->release($order, 'cancelled');
        $this->assertEquals(0, $discount->fresh()->used_count);
    }

    public function test_event_specific_discount_applies_only_to_matching_event(): void
    {
        $customer = $this->customer();
        [$eventA, $ticketA] = $this->concert();
        [$eventB, $ticketB] = $this->concert();

        $discount = Discount::create([
            'code' => 'EVENTAONLY',
            'name' => 'Diskon Khusus Event A',
            'event_id' => $eventA->id,
            'type' => 'fixed',
            'value' => 25000,
            'min_order_amount' => 0,
            'active' => true,
        ]);

        // Attempting check on Event B should be rejected
        $this->postJson(route('discounts.check'), [
            'code' => 'EVENTAONLY',
            'subtotal' => 100000,
            'event_id' => $eventB->id,
        ])->assertStatus(422)
          ->assertJsonPath('ok', false);

        // Check on matching Event A succeeds
        $this->postJson(route('discounts.check'), [
            'code' => 'EVENTAONLY',
            'subtotal' => 100000,
            'event_id' => $eventA->id,
        ])->assertOk()
          ->assertJsonPath('ok', true)
          ->assertJsonPath('discount_amount', 25000);

        // Checkout for Event A succeeds
        $order = app(BookingService::class)->checkout(
            $customer,
            $eventA,
            [$ticketA->id => 1],
            (string) Str::uuid(),
            'EVENTAONLY'
        );
        $this->assertEquals(25000, $order->discount_amount);
    }
}

