<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'ticketing_test') {
            throw new \RuntimeException('Tests require the isolated ticketing_test database.');
        }
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Lupa kata sandi?');
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        $user = User::factory()->create(['email' => 'user@example.test']);

        $response = $this->post('/forgot-password', [
            'email' => 'user@example.test',
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'user@example.test',
        ]);
    }

    public function test_reset_password_screen_can_be_rendered_with_token(): void
    {
        $this->get('/reset-password/sample-token?email=user@example.test')
            ->assertOk()
            ->assertSee('Atur kata sandi baru.');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'user@example.test', 'password' => Hash::make('OldPassword123!')]);
        $token = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.test',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'user@example.test',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect('/login');
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'user@example.test']);
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        User::factory()->create(['email' => 'user@example.test']);
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.test',
            'token' => Hash::make('correct-token'),
            'created_at' => now(),
        ]);

        $response = $this->post('/reset-password', [
            'token' => 'wrong-token',
            'email' => 'user@example.test',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
    }
}

