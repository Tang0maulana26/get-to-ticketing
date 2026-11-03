<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_admin_can_view_users_list_and_filter_by_role(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['name' => 'John Doe', 'role' => 'customer']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Pengguna & Peran', false)
            ->assertSee('John Doe');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'admin']))
            ->assertOk()
            ->assertSee($admin->name)
            ->assertDontSee('John Doe');
    }

    public function test_admin_can_promote_customer_to_admin(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Budi Santoso', 'email' => 'budi@example.com']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $customer), [
                'name' => 'Budi Santoso (Admin)',
                'email' => 'budi@example.com',
                'role' => 'admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($customer->fresh()->isAdmin());
        $this->assertEquals('Budi Santoso (Admin)', $customer->fresh()->name);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'customer',
            ])
            ->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_user_can_update_their_own_profile(): void
    {
        $user = User::factory()->create(['name' => 'Old Name', 'username' => 'old_user', 'email' => 'old@example.com']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'New Name',
                'username' => 'new_user',
                'email' => 'new@example.com',
            ])
            ->assertSessionHas('success');

        $this->assertEquals('New Name', $user->fresh()->name);
        $this->assertEquals('new_user', $user->fresh()->username);
        $this->assertEquals('new@example.com', $user->fresh()->email);
    }

    public function test_admin_can_export_customers_csv(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Audited Customer', 'email' => 'audit@example.test', 'role' => 'customer']);

        $response = $this->actingAs($admin)->get(route('admin.customers.export'));

        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'attachment; filename="data-pembeli-'));
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('content-type'));
    }

    public function test_admin_can_delete_customer(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $customer))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }

    public function test_user_can_delete_their_own_account_with_password(): void
    {
        $user = User::factory()->create([
            'password' => 'MyPassword123!',
        ]);

        $this->actingAs($user)
            ->delete(route('profile.destroy'), [
                'delete_password' => 'MyPassword123!',
            ])
            ->assertRedirect(route('catalog'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertGuest();
    }
}
