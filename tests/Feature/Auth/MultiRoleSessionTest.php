<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiRoleSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_role_guards_can_be_authenticated_in_the_same_session(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        $cashierRole = Role::create(['name' => 'cashier', 'description' => 'Cashier']);

        $admin = User::factory()->create([
            'email' => 'admin-test@bec.edu.ph',
            'role_id' => $adminRole->id,
        ]);

        $cashier = User::factory()->create([
            'email' => 'cashier-test@bec.edu.ph',
            'role_id' => $cashierRole->id,
        ]);

        $this->post('/login', [
            'role' => 'admin',
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->post('/login', [
            'role' => 'cashier',
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertRedirect(route('cashier.index', absolute: false));

        $this->assertAuthenticated('admin');
        $this->assertAuthenticated('cashier');
    }

    public function test_logging_out_one_role_guard_keeps_other_role_sessions_active(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        $frontdeskRole = Role::create(['name' => 'frontdesk', 'description' => 'Front Desk']);

        $admin = User::factory()->create([
            'email' => 'admin-logout@bec.edu.ph',
            'role_id' => $adminRole->id,
        ]);

        $frontdesk = User::factory()->create([
            'email' => 'frontdesk-logout@bec.edu.ph',
            'role_id' => $frontdeskRole->id,
        ]);

        $this->post('/login', [
            'role' => 'admin',
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->post('/login', [
            'role' => 'frontdesk',
            'email' => $frontdesk->email,
            'password' => 'password',
        ]);

        $this->post('/logout', ['role' => 'frontdesk'])
            ->assertRedirect('/');

        $this->assertGuest('frontdesk');
        $this->assertAuthenticated('admin');
    }
}
