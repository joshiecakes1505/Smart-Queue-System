<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $role = Role::create([
            'name' => 'frontdesk',
            'description' => 'Front Desk',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this->post('/login', [
            'role' => 'frontdesk',
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated('frontdesk');
        $response->assertRedirect(route('frontdesk.queues.index', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $role = Role::create([
            'name' => 'frontdesk',
            'description' => 'Front Desk',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $this->post('/login', [
            'role' => 'frontdesk',
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest('frontdesk');
    }

    public function test_frontdesk_login_ignores_stale_intended_url_from_other_role_context(): void
    {
        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrator',
        ]);

        $frontdeskRole = Role::create([
            'name' => 'frontdesk',
            'description' => 'Front Desk',
        ]);

        User::factory()->create([
            'email' => 'admin-intended@bec.edu.ph',
            'role_id' => $adminRole->id,
        ]);

        $frontdesk = User::factory()->create([
            'email' => 'frontdesk-intended@bec.edu.ph',
            'role_id' => $frontdeskRole->id,
        ]);

        session(['url.intended' => route('admin.dashboard')]);

        $response = $this->post('/login', [
            'role' => 'frontdesk',
            'email' => $frontdesk->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('frontdesk.queues.index', absolute: false));
        $this->assertAuthenticated('frontdesk');
    }

    public function test_users_can_logout(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'description' => 'Administrator',
        ]);

        /** @var User $user */
        $user = User::factory()->createOne([
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($user, 'admin')->post('/logout', ['role' => 'admin']);

        $this->assertGuest('admin');
        $response->assertRedirect('/');
    }
}
