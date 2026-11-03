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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class BuyerResetTest extends TestCase
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
        $user = User::factory()->create([
            'password' => Hash::make('AdminSecret123!'),
            'role' => 'admin',
        ]);
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
            'title' => 'Pop Fest',
            'slug' => 'pop-fest-' . Str::random(6),
            'summary' => 'Pop fest summary',
            'description' => 'Pop fest description',
            'venue' => 'Arena',
            'city' => 'Surabaya',
            'address' => 'Jl. Pemuda',
            'start_time' => now()->addDays(15),
            'status' => 'published',
        ]);
        $ticket = $event->ticketCategories()->create([
            'name' => 'General',
            'price' => 75000,
            'capacity' => 20,
            'stock' => 20,
            'max_per_order' => 4,
            'active' => true,
        ]);

        return [$event, $ticket];
    }

    public function test_reset_requires_valid_admin_password_and_phrase(): void
    {
        $admin = $this->admin();

        // Wrong password
        $this->actingAs($admin)
            ->post(route('admin.reset.all'), [
                'current_password' => 'WrongPassword!',
                'confirmation_phrase' => 'RESET-DATA-PEMBELI',
            ])
            ->assertSessionHasErrors('current_password');

        // Wrong phrase
        $this->actingAs($admin)
            ->post(route('admin.reset.all'), [
                'current_password' => 'AdminSecret123!',
                'confirmation_phrase' => 'WRONG-PHRASE',
            ])
            ->assertSessionHasErrors('confirmation_phrase');
    }

    public function test_reset_all_safely_wipes_orders_and_restores_stock(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        [$event, $ticket] = $this->concert();

        // Create an order
        $order = app(BookingService::class)->checkout(
            $customer,
            $event,
            [$ticket->id => 3],
            (string) Str::uuid()
        );

        $this->assertEquals(17, $ticket->fresh()->stock);
        $this->assertEquals(1, Order::count());

        // Perform safe reset
        $this->actingAs($admin)
            ->post(route('admin.reset.all'), [
                'current_password' => 'AdminSecret123!',
                'confirmation_phrase' => 'RESET-DATA-PEMBELI',
            ])
            ->assertRedirect(route('admin.reset.index'))
            ->assertSessionHas('success');

        // Orders and tickets wiped
        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, Ticket::count());

        // Master event and categories preserved
        $this->assertDatabaseHas('events', ['id' => $event->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $customer->id]);

        // Stock restored to full capacity
        $this->assertEquals(20, $ticket->fresh()->stock);
    }
}

