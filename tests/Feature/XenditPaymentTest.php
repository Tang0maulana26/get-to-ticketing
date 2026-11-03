<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests Xendit sandbox integration: checkout creation, status sync, and webhooks.
 */
class XenditPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingOrder(User $user): Order
    {
        $category = Category::create(['name' => 'Musik', 'slug' => 'musik']);
        $event = Event::create([
            'category_id' => $category->id,
            'title' => 'Indie Fest 2026',
            'slug' => 'indie-fest-2026',
            'summary' => 'Konser musik seru',
            'description' => 'Deskripsi event musik seru di Jakarta',
            'venue' => 'Senayan Park',
            'city' => 'Jakarta',
            'address' => 'Jl. Asia Afrika',
            'start_time' => now()->addDays(10),
            'end_time' => now()->addDays(10)->addHours(4),
            'status' => 'published',
        ]);

        $ticketType = TicketCategory::create([
            'event_id' => $event->id,
            'name' => 'Regular',
            'price' => 100000,
            'capacity' => 100,
            'stock' => 98,
            'max_per_order' => 5,
            'active' => true,
        ]);

        $order = Order::create([
            'number' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'checkout_key' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'subtotal' => 200000,
            'discount_amount' => 0,
            'total' => 200000,
            'expires_at' => now()->addMinutes(15),
        ]);

        $order->items()->create([
            'ticket_category_id' => $ticketType->id,
            'event_title' => $event->title,
            'ticket_name' => $ticketType->name,
            'event_start_time' => $event->start_time,
            'venue' => $event->venue,
            'quantity' => 2,
            'unit_price' => 100000,
            'subtotal' => 200000,
        ]);

        return $order;
    }

    public function test_payment_section_rendered_on_pending_order(): void
    {
        $user = User::factory()->create();
        $order = $this->createPendingOrder($user);

        $response = $this->actingAs($user)->get("/orders/{$order->number}");

        $response->assertOk()
            ->assertSee('payment-section', false)
            ->assertSee('Xendit Test')
            ->assertSee('Bayar via Xendit');
    }

    public function test_user_can_create_xendit_invoice_and_redirect(): void
    {
        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'xnd_inv_test_123',
                'external_id' => 'test-order-uuid',
                'status' => 'PENDING',
                'invoice_url' => 'https://checkout-staging.xendit.co/web/xnd_inv_test_123',
                'amount' => 200000,
            ], 200),
        ]);

        $user = User::factory()->create();
        $order = $this->createPendingOrder($user);

        $response = $this->actingAs($user)->post("/orders/{$order->number}/xendit/checkout");

        $response->assertRedirect('https://checkout-staging.xendit.co/web/xnd_inv_test_123');

        $order->refresh();
        $this->assertEquals('xnd_inv_test_123', $order->xendit_invoice_id);
        $this->assertEquals('https://checkout-staging.xendit.co/web/xnd_inv_test_123', $order->xendit_invoice_url);
        $this->assertEquals('xendit', $order->payment_method);
    }

    public function test_xendit_webhook_marks_order_as_paid_and_issues_tickets(): void
    {
        $user = User::factory()->create();
        $order = $this->createPendingOrder($user);

        $response = $this->postJson('/xendit/webhook', [
            'id' => 'xnd_inv_test_123',
            'external_id' => $order->number,
            'status' => 'PAID',
            'payment_method' => 'BCA',
            'payment_channel' => 'VIRTUAL_ACCOUNT',
            'amount' => 200000,
            'paid_amount' => 200000,
        ]);

        $response->assertOk()->assertJson(['status' => 'OK']);

        $order->refresh();
        $this->assertEquals('paid', $order->status);
        $this->assertEquals('xendit', $order->payment_method);
        $this->assertNotNull($order->paid_at);

        $this->assertCount(2, $order->items->first()->tickets);
        $this->assertEquals('valid', $order->items->first()->tickets->first()->status);
    }

    public function test_xendit_webhook_handles_expired_invoice(): void
    {
        $user = User::factory()->create();
        $order = $this->createPendingOrder($user);

        $response = $this->postJson('/xendit/webhook', [
            'id' => 'xnd_inv_test_123',
            'external_id' => $order->number,
            'status' => 'EXPIRED',
        ]);

        $response->assertOk();

        $order->refresh();
        $this->assertEquals('expired', $order->status);
    }

    public function test_customer_can_manually_check_and_sync_paid_status(): void
    {
        $user = User::factory()->create();
        $order = $this->createPendingOrder($user);
        $order->update([
            'xendit_invoice_id' => 'xnd_inv_test_456',
            'xendit_invoice_url' => 'https://checkout-staging.xendit.co/web/xnd_inv_test_456',
        ]);

        Http::fake([
            'https://api.xendit.co/v2/invoices/xnd_inv_test_456' => Http::response([
                'id' => 'xnd_inv_test_456',
                'external_id' => $order->number,
                'status' => 'PAID',
                'payment_method' => 'QRIS',
                'amount' => 200000,
            ], 200),
        ]);

        $response = $this->actingAs($user)->get("/orders/{$order->number}/xendit/check");

        $response->assertRedirect("/orders/{$order->number}");

        $order->refresh();
        $this->assertEquals('paid', $order->status);
        $this->assertCount(2, $order->items->first()->tickets);
    }

    public function test_unauthorized_user_cannot_access_xendit_checkout(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->createPendingOrder($owner);

        $response = $this->actingAs($stranger)->post("/orders/{$order->number}/xendit/checkout");

        $response->assertForbidden();
    }
}

