<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'ticketing_test') {
            throw new \RuntimeException('Tests require the isolated ticketing_test database.');
        }
    }

    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'rockstar',
            'email' => 'rock@thegetto.test',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $this->post('/login', [
            'login' => 'rockstar',
            'password' => 'SecretPass123!',
        ])->assertRedirect('/my-tickets');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'username' => 'superfan',
            'email' => 'fan@thegetto.test',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $this->post('/login', [
            'login' => 'fan@thegetto.test',
            'password' => 'SecretPass123!',
        ])->assertRedirect('/my-tickets');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_register_with_username_and_email(): void
    {
        $this->post('/register', [
            'name' => 'Budi Santoso',
            'username' => 'budi_santoso',
            'email' => 'budi@example.test',
            'password' => 'SafePassword123!',
            'password_confirmation' => 'SafePassword123!',
        ])->assertRedirect('/');

        $this->assertDatabaseHas('users', [
            'username' => 'budi_santoso',
            'email' => 'budi@example.test',
            'role' => 'customer',
        ]);
    }
}

