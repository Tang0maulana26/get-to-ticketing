<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests language switching between English (default) and Indonesian.
 */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_english_when_visiting_with_en(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/');

        $response->assertOk()
            ->assertSee('Explore concerts')
            ->assertSee('Curated event selection');
    }

    public function test_can_switch_locale_to_indonesian(): void
    {
        $response = $this->get('/locale/id');

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'id');
        $response->assertCookie('locale', 'id');

        $page = $this->withSession(['locale' => 'id'])->get('/');
        $page->assertOk()
            ->assertSee('Jelajahi konser')
            ->assertSee('Kurasi konser pilihan');
    }

    public function test_can_switch_locale_back_to_english(): void
    {
        $response = $this->withSession(['locale' => 'id'])->get('/locale/en');

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
        $response->assertCookie('locale', 'en');

        $page = $this->withSession(['locale' => 'en'])->get('/');
        $page->assertOk()
            ->assertSee('Explore concerts')
            ->assertSee('Curated event selection');
    }

    public function test_invalid_locale_defaults_to_english(): void
    {
        $response = $this->get('/locale/invalid');

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
        $response->assertCookie('locale', 'en');
    }

    public function test_locale_toggle_button_is_rendered_in_navbar(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/');

        $response->assertOk()
            ->assertSee('class="locale-switcher locale-toggle"', false)
            ->assertSee('class="locale-opt locale-text active"', false)
            ->assertSee('href="/locale/id?', false);
    }

    public function test_order_show_translates_between_languages(): void
    {
        $user = \App\Models\User::factory()->create();
        $order = \App\Models\Order::create([
            'number' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'checkout_key' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'pending',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total' => 100000,
            'expires_at' => now()->addMinutes(15),
        ]);

        // English view
        $enPage = $this->actingAs($user)->withSession(['locale' => 'en'])->get("/orders/{$order->number}");
        $enPage->assertOk()
            ->assertSee('Payment Options')
            ->assertSee('Pay via Xendit Sandbox →');

        // Indonesian view
        $idPage = $this->actingAs($user)->withSession(['locale' => 'id'])->get("/orders/{$order->number}");
        $idPage->assertOk()
            ->assertSee('Pilihan Pembayaran')
            ->assertSee('Bayar via Xendit Sandbox →');
    }
}

