<?php

namespace Tests\Feature;

use App\Mail\OrderPaidMail;
use App\Models\Category;
use App\Models\Event;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\BookingService;
use App\Services\TicketPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketPdfAndMailTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Event $event;
    private TicketCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'name' => 'Demo Customer',
            'username' => 'democustomer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password123'),
            'role' => 'customer',
        ]);

        $cat = Category::create(['name' => 'Konser', 'slug' => 'konser']);

        $this->event = Event::create([
            'category_id' => $cat->id,
            'title' => 'Indie Rock Fest 2026',
            'slug' => 'indie-rock-fest-2026-'.Str::random(5),
            'summary' => 'Konser akbar indie',
            'description' => 'Deskripsi konser indie rock',
            'venue' => 'Gelora Bung Karno',
            'city' => 'Jakarta',
            'address' => 'Jl. Pintu Satu Senayan',
            'status' => 'published',
            'start_time' => now()->addDays(14),
            'end_time' => now()->addDays(14)->addHours(4),
        ]);

        $this->category = TicketCategory::create([
            'event_id' => $this->event->id,
            'name' => 'VIP Festival',
            'price' => 750000,
            'capacity' => 100,
            'stock' => 50,
            'max_per_order' => 5,
            'active' => true,
        ]);
    }

    public function test_paid_order_can_download_ticket_pdf(): void
    {
        $order = app(BookingService::class)->checkout(
            $this->customer,
            $this->event,
            [$this->category->id => 2],
            (string) Str::uuid()
        );
        app(BookingService::class)->payDemo($order);

        $response = $this->actingAs($this->customer)->get(route('orders.ticket.pdf', $order));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString("E-Ticket-{$order->number}.pdf", $response->headers->get('Content-Disposition'));
    }

    public function test_pending_order_cannot_download_ticket_pdf(): void
    {
        $order = app(BookingService::class)->checkout(
            $this->customer,
            $this->event,
            [$this->category->id => 1],
            (string) Str::uuid()
        );

        $response = $this->actingAs($this->customer)->get(route('orders.ticket.pdf', $order));

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('error');
    }

    public function test_unauthorized_user_cannot_download_other_users_ticket_pdf(): void
    {
        $order = app(BookingService::class)->checkout(
            $this->customer,
            $this->event,
            [$this->category->id => 1],
            (string) Str::uuid()
        );
        app(BookingService::class)->payDemo($order);

        $stranger = User::create([
            'name' => 'Stranger',
            'username' => 'stranger123',
            'email' => 'stranger@example.com',
            'password' => bcrypt('password123'),
            'role' => 'customer',
        ]);

        $response = $this->actingAs($stranger)->get(route('orders.ticket.pdf', $order));

        $response->assertForbidden();
    }

    public function test_ticket_pdf_service_generates_valid_pdf_with_qr_code(): void
    {
        $order = app(BookingService::class)->checkout(
            $this->customer,
            $this->event,
            [$this->category->id => 1],
            (string) Str::uuid()
        );
        app(BookingService::class)->payDemo($order);

        $pdfService = app(TicketPdfService::class);
        $binary = $pdfService->output($order);

        $this->assertNotEmpty($binary);
        // PDF header magic bytes "%PDF-"
        $this->assertStringStartsWith('%PDF-', $binary);
    }

    public function test_order_payment_automatically_dispatches_confirmation_email_with_pdf(): void
    {
        Mail::fake();

        $order = app(BookingService::class)->checkout(
            $this->customer,
            $this->event,
            [$this->category->id => 2],
            (string) Str::uuid()
        );

        app(BookingService::class)->payDemo($order);

        Mail::assertSent(OrderPaidMail::class, function (OrderPaidMail $mail) use ($order) {
            return $mail->hasTo('customer@example.com')
                && $mail->order->id === $order->id;
        });
    }

    public function test_customer_can_manually_resend_ticket_email(): void
    {
        Mail::fake();

        $order = app(BookingService::class)->checkout(
            $this->customer,
            $this->event,
            [$this->category->id => 1],
            (string) Str::uuid()
        );
        app(BookingService::class)->payDemo($order);

        $response = $this->actingAs($this->customer)
            ->post(route('orders.resend.email', $order));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(OrderPaidMail::class, function (OrderPaidMail $mail) use ($order) {
            return $mail->hasTo('customer@example.com')
                && $mail->order->id === $order->id;
        });
    }

    public function test_my_tickets_list_hides_pdf_download_button_and_filters_active_vs_past_tabs(): void
    {
        $order = app(BookingService::class)->checkout(
            $this->customer,
            $this->event,
            [$this->category->id => 2],
            (string) Str::uuid()
        );
        app(BookingService::class)->payDemo($order);

        // /my-tickets should list the order and link to orders.show, but NOT render the PDF download link
        $response = $this->actingAs($this->customer)->get(route('orders.index'));
        $response->assertOk()
            ->assertSee('Indie Rock Fest 2026')
            ->assertSee(route('orders.show', $order), false)
            ->assertDontSee(route('orders.ticket.pdf', $order), false);

        // Active tab includes the upcoming order
        $this->actingAs($this->customer)
            ->get(route('orders.index', ['tab' => 'active']))
            ->assertOk()
            ->assertSee('Indie Rock Fest 2026');

        // Past tab does not include the upcoming paid order
        $this->actingAs($this->customer)
            ->get(route('orders.index', ['tab' => 'past']))
            ->assertOk()
            ->assertDontSee('Indie Rock Fest 2026');
    }
}
