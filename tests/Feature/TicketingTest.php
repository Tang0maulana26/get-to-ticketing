<?php

namespace Tests\Feature;

use App\Models\{Category, Event, Order, Ticket, TicketCategory, User};
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketingTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'ticketing_test') { throw new \RuntimeException('Tests require the isolated ticketing_test database.'); }
    }
    private function event(array $overrides = []): Event
    {
        $category = Category::firstOrCreate(['slug' => 'music'], ['name' => 'Musik']);
        return Event::create(array_merge(['category_id' => $category->id, 'title' => 'Test Concert', 'slug' => 'test-'.Str::random(8), 'summary' => 'Test summary', 'description' => 'Test description', 'venue' => 'Test Venue', 'city' => 'Jakarta', 'address' => 'Test address', 'start_time' => now()->addDays(30), 'status' => 'published'], $overrides));
    }
    private function type(Event $event, array $overrides = []): TicketCategory
    {
        return $event->ticketCategories()->create(array_merge(['name' => 'VIP', 'price' => 250000, 'capacity' => 10, 'stock' => 10, 'max_per_order' => 6, 'active' => true], $overrides));
    }
    private function admin(): User
    {
        $user = User::factory()->create(); $user->role = 'admin'; $user->save(); return $user;
    }
    private function book(User $user, Event $event, TicketCategory $type, int $quantity = 1): Order
    {
        return app(BookingService::class)->checkout($user, $event, [$type->id => $quantity], (string) Str::uuid());
    }
    public function test_catalog_filters_and_draft_visibility(): void
    {
        $event = $this->event(['title' => 'Visible concert']); $this->type($event);
        $draft = $this->event(['title' => 'Secret draft', 'status' => 'draft']);
        $this->get('/')->assertOk()->assertSee('Visible concert')->assertDontSee('Secret draft');
        $this->get('/?city=Bandung')->assertOk()->assertDontSee('Visible concert');
        $this->get('/events/'.$draft->slug)->assertNotFound();
        $this->get('/events/'.$event->slug)->assertOk()->assertSee('Pilih tiketmu');
    }
    public function test_registration_cannot_assign_admin_role(): void
    {
        $this->post('/register', ['name'=>'Customer', 'email'=>'new@example.test', 'password'=>'SafePassword123', 'password_confirmation'=>'SafePassword123', 'role'=>'admin'])->assertRedirect('/');
        $this->assertDatabaseHas('users',['email'=>'new@example.test','role'=>'customer']);
    }
    public function test_authentication_rejects_wrong_password_and_allows_valid_login(): void
    {
        $user = User::factory()->create(['password'=>'SafePassword123']);
        $this->post('/login',['email'=>$user->email,'password'=>'wrong'])->assertSessionHasErrors('email');
        $this->post('/login',['email'=>$user->email,'password'=>'SafePassword123'])->assertRedirect('/my-tickets');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/'); $this->assertGuest();
    }
    public function test_customer_cannot_access_admin_pages_or_mutations(): void
    {
        $event = $this->event(); $this->actingAs(User::factory()->create());
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/events')->assertForbidden();
        $this->post('/admin/events',[])->assertForbidden();
        $this->delete('/admin/events/'.$event->id)->assertForbidden();
        $this->assertDatabaseHas('events',['id'=>$event->id,'deleted_at'=>null]);
    }
    public function test_checkout_calculates_price_server_side_and_reserves_stock(): void
    {
        $event = $this->event(); $type = $this->type($event); $user = User::factory()->create();
        $this->actingAs($user)->post('/events/'.$event->id.'/checkout',['tickets'=>[$type->id=>2],'checkout_key'=>(string) Str::uuid(),'total'=>1,'price'=>1])->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame(500000,$order->total); $this->assertSame(8,$type->fresh()->stock);
        $this->get('/orders/'.$order->number)->assertOk()->assertSee('Rp500.000');
    }
    public function test_duplicate_checkout_key_does_not_double_reserve(): void
    {
        $event=$this->event(); $type=$this->type($event); $user=User::factory()->create(); $key=(string) Str::uuid();
        $service=app(BookingService::class);
        $a=$service->checkout($user,$event,[$type->id=>2],$key);
        $b=$service->checkout($user,$event,[$type->id=>2],$key);
        $this->assertSame($a->id,$b->id); $this->assertSame(8,$type->fresh()->stock); $this->assertSame(1,Order::count());
    }
    public function test_insufficient_stock_does_not_leave_partial_order(): void
    {
        $event=$this->event(); $first=$this->type($event); $second=$this->type($event,['name'=>'Regular','capacity'=>1,'stock'=>1]);
        $this->actingAs(User::factory()->create())->post('/events/'.$event->id.'/checkout',['tickets'=>[$first->id=>2,$second->id=>2],'checkout_key'=>(string) Str::uuid()])->assertSessionHasErrors('tickets');
        $this->assertSame(10,$first->fresh()->stock); $this->assertSame(1,$second->fresh()->stock); $this->assertSame(0,Order::count());
    }
    public function test_foreign_event_ticket_is_rejected(): void
    {
        $event=$this->event(); $other=$this->type($this->event());
        $this->actingAs(User::factory()->create())->post('/events/'.$event->id.'/checkout',['tickets'=>[$other->id=>1],'checkout_key'=>(string) Str::uuid()])->assertSessionHasErrors('tickets');
        $this->assertSame(0,Order::count());
    }
    public function test_negative_quantity_and_empty_selection_are_rejected(): void
    {
        $event=$this->event(); $type=$this->type($event); $this->actingAs(User::factory()->create());
        $this->post('/events/'.$event->id.'/checkout',['tickets'=>[$type->id=>-1],'checkout_key'=>(string) Str::uuid()])->assertSessionHasErrors();
        $this->post('/events/'.$event->id.'/checkout',['tickets'=>[$type->id=>0],'checkout_key'=>(string) Str::uuid()])->assertSessionHasErrors('tickets');
    }
    public function test_unpublished_or_past_event_cannot_be_booked(): void
    {
        $event=$this->event(['status'=>'draft']); $type=$this->type($event); $user=User::factory()->create();
        $this->actingAs($user)->post('/events/'.$event->id.'/checkout',['tickets'=>[$type->id=>1],'checkout_key'=>(string) Str::uuid()])->assertSessionHasErrors('tickets');
        $event->update(['status'=>'published','start_time'=>now()->subDay()]);
        $this->post('/events/'.$event->id.'/checkout',['tickets'=>[$type->id=>1],'checkout_key'=>(string) Str::uuid()])->assertSessionHasErrors('tickets');
    }
    public function test_order_access_is_scoped_to_owner(): void
    {
        $event=$this->event(); $type=$this->type($event); $order=$this->book(User::factory()->create(),$event,$type);
        $this->actingAs(User::factory()->create());
        $this->get('/orders/'.$order->number)->assertForbidden();
        $this->post('/orders/'.$order->number.'/cancel')->assertForbidden();
        $this->post('/orders/'.$order->number.'/demo-payment')->assertForbidden();
    }
    public function test_cancellation_and_expiration_release_stock_exactly_once(): void
    {
        $event=$this->event(); $type=$this->type($event); $user=User::factory()->create(); $service=app(BookingService::class);
        $order=$this->book($user,$event,$type,2); $service->release($order); $service->release($order);
        $this->assertSame(10,$type->fresh()->stock);
        $expired=$this->book($user,$event,$type,3); $expired->update(['expires_at'=>now()->subMinute()]);
        $service->expire(); $service->expire();
        $this->assertSame('expired',$expired->fresh()->status); $this->assertSame(10,$type->fresh()->stock);
    }
    public function test_payment_is_idempotent_and_issues_one_ticket_per_quantity(): void
    {
        config(['ticketing.demo_payments'=>true]);
        $event=$this->event(); $type=$this->type($event); $user=User::factory()->create(); $order=$this->book($user,$event,$type,2);
        $this->actingAs($user)->post('/orders/'.$order->number.'/demo-payment')->assertRedirect();
        $this->post('/orders/'.$order->number.'/demo-payment')->assertRedirect();
        $this->assertSame(2,Ticket::count()); $this->assertSame('paid',$order->fresh()->status);
        app(BookingService::class)->release($order); $this->assertSame(8,$type->fresh()->stock);
        $this->get('/orders/'.$order->number)->assertOk()->assertSee('Tiket aktif');
    }
    public function test_expired_payment_commits_stock_release_even_when_rejected(): void
    {
        config(['ticketing.demo_payments'=>true]);
        $event=$this->event(); $type=$this->type($event); $user=User::factory()->create(); $order=$this->book($user,$event,$type,2);
        $order->update(['expires_at'=>now()->subMinute()]);
        $this->actingAs($user)->post('/orders/'.$order->number.'/demo-payment')->assertSessionHasErrors('order');
        $this->assertSame('expired',$order->fresh()->status); $this->assertSame(10,$type->fresh()->stock); $this->assertSame(0,Ticket::count());
    }
    public function test_demo_payment_is_disabled_by_configuration(): void
    {
        config(['ticketing.demo_payments'=>false]);
        $event=$this->event(); $type=$this->type($event); $user=User::factory()->create(); $order=$this->book($user,$event,$type);
        $this->actingAs($user)->post('/orders/'.$order->number.'/demo-payment')->assertForbidden();
    }
    public function test_admin_can_edit_ticket_without_changing_order_snapshot(): void
    {
        $event=$this->event(); $type=$this->type($event); $order=$this->book(User::factory()->create(),$event,$type,2);
        $data=['name'=>'New VIP','price'=>300000,'capacity'=>20,'max_per_order'=>6,'active'=>1];
        $this->actingAs($this->admin())->put('/admin/events/'.$event->id.'/ticket-categories/'.$type->id,$data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(18,$type->fresh()->stock); $this->assertSame('VIP',$order->items()->first()->ticket_name); $this->assertSame(500000,$order->total);
        $this->put('/admin/events/'.$event->id.'/ticket-categories/'.$type->id,array_merge($data,['capacity'=>1]))->assertSessionHasErrors('capacity');
    }
    public function test_admin_nested_ticket_route_checks_parent_event(): void
    {
        $event=$this->event(); $other=$this->type($this->event());
        $this->actingAs($this->admin())->delete('/admin/events/'.$event->id.'/ticket-categories/'.$other->id)->assertNotFound();
    }
    public function test_deleting_purchased_ticket_deactivates_it_and_preserves_history(): void
    {
        $event=$this->event(); $type=$this->type($event); $order=$this->book(User::factory()->create(),$event,$type);
        $this->actingAs($this->admin())->delete('/admin/events/'.$event->id.'/ticket-categories/'.$type->id)->assertRedirect();
        $this->assertFalse($type->fresh()->active); $this->assertSame(1,$order->items()->count());
    }
    public function test_admin_event_crud_and_raster_upload(): void
    {
        Storage::fake('public'); $this->actingAs($this->admin()); $event=$this->event();
        $this->get('/admin')->assertOk(); $this->get('/admin/events/create')->assertOk(); $this->get('/admin/events/'.$event->id.'/edit')->assertOk(); $this->get('/admin/customers')->assertOk();
        $data=['category_id'=>$event->category_id,'title'=>'New show','slug'=>'new-show','summary'=>'Summary','description'=>'Details','venue'=>'Hall','city'=>'Jakarta','address'=>'Street','start_time'=>now()->addDay()->format('Y-m-d H:i'),'status'=>'draft','banner_file'=>UploadedFile::fake()->image('banner.jpg',800,600)];
        $this->post('/admin/events',$data)->assertSessionHasNoErrors()->assertRedirect();
        $new=Event::where('slug','new-show')->firstOrFail(); Storage::disk('public')->assertExists($new->banner);
        $this->assertStringEndsWith('.webp',$new->banner);
        $this->delete('/admin/events/'.$new->id)->assertRedirect(); $this->assertSoftDeleted('events',['id'=>$new->id]);
    }
    public function test_event_text_is_escaped_and_security_headers_are_set(): void
    {
        $event=$this->event(['description'=>'<script>alert(1)</script>']);
        $this->get('/events/'.$event->slug)->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false)->assertHeader('X-Frame-Options','DENY');
    }
    public function test_database_prevents_negative_stock(): void
    {
        $type=$this->type($this->event());
        $this->expectException(\Illuminate\Database\QueryException::class);
        $type->update(['stock'=>-1]);
    }
}
