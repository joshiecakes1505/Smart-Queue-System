<?php

namespace Tests\Feature\Admin;

use App\Mail\AccountCreatedMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private const DEFAULT_PASSWORD = 'BECQueue@2026';

    public function test_admin_can_open_the_edit_user_page(): void
    {
        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrator',
        ]);

        $cashierRole = Role::create([
            'name' => 'cashier',
            'description' => 'Cashier',
        ]);

        $frontdeskRole = Role::create([
            'name' => 'frontdesk',
            'description' => 'Front Desk',
        ]);

        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role_id' => $adminRole->id,
        ]);

        /** @var User $managedUser */
        $managedUser = User::factory()->createOne([
            'name' => 'Cashier Sample',
            'email' => 'cashier@example.com',
            'role_id' => $cashierRole->id,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.users.edit', $managedUser, false));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Edit')
            ->where('user.id', $managedUser->id)
            ->where('user.name', 'Cashier Sample')
            ->where('user.email', 'cashier@example.com')
            ->has('roles', 3)
            ->where('roles.0.name', 'admin')
            ->where('roles.1.name', 'cashier')
            ->where('roles.2.name', 'frontdesk')
        );
    }

    public function test_admin_can_update_a_user_profile_details(): void
    {
        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrator',
        ]);

        $cashierRole = Role::create([
            'name' => 'cashier',
            'description' => 'Cashier',
        ]);

        $frontdeskRole = Role::create([
            'name' => 'frontdesk',
            'description' => 'Front Desk',
        ]);

        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role_id' => $adminRole->id,
        ]);

        /** @var User $managedUser */
        $managedUser = User::factory()->createOne([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'role_id' => $cashierRole->id,
        ]);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.users.update', $managedUser, false), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role_id' => $frontdeskRole->id,
        ]);

        $response->assertRedirect(route('admin.users.index', absolute: false));

        $managedUser->refresh();

        $this->assertSame('Updated Name', $managedUser->name);
        $this->assertSame('updated@example.com', $managedUser->email);
        $this->assertSame($frontdeskRole->id, $managedUser->role_id);
    }

    public function test_admin_created_user_gets_default_password_and_account_email(): void
    {
        Mail::fake();

        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrator',
        ]);

        $cashierRole = Role::create([
            'name' => 'cashier',
            'description' => 'Cashier',
        ]);

        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role_id' => $adminRole->id,
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.users.store', absolute: false), [
            'name' => 'Created User',
            'email' => 'created@example.com',
            'role_id' => $cashierRole->id,
        ]);

        $response->assertRedirect(route('admin.users.index', absolute: false));

        /** @var User $createdUser */
        $createdUser = User::query()->where('email', 'created@example.com')->firstOrFail();

        $this->assertTrue(Hash::check(self::DEFAULT_PASSWORD, $createdUser->password));

        Mail::assertSent(AccountCreatedMail::class, function (AccountCreatedMail $mail) use ($createdUser) {
            return $mail->hasTo($createdUser->email)
                && $mail->email === $createdUser->email
                && $mail->password === self::DEFAULT_PASSWORD;
        });
    }

    public function test_admin_can_reset_another_users_password_to_default_and_resend_email(): void
    {
        Mail::fake();

        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrator',
        ]);

        $cashierRole = Role::create([
            'name' => 'cashier',
            'description' => 'Cashier',
        ]);

        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role_id' => $adminRole->id,
        ]);

        /** @var User $managedUser */
        $managedUser = User::factory()->createOne([
            'email' => 'reset@example.com',
            'password' => Hash::make('old-password'),
            'role_id' => $cashierRole->id,
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.users.reset-password', $managedUser, false));

        $response->assertRedirect(route('admin.users.edit', $managedUser, false));

        $managedUser->refresh();

        $this->assertTrue(Hash::check(self::DEFAULT_PASSWORD, $managedUser->password));

        Mail::assertSent(AccountCreatedMail::class, function (AccountCreatedMail $mail) use ($managedUser) {
            return $mail->hasTo($managedUser->email)
                && $mail->email === $managedUser->email
                && $mail->password === self::DEFAULT_PASSWORD;
        });
    }

    public function test_admin_cannot_reset_their_own_password_from_the_admin_panel(): void
    {
        Mail::fake();

        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrator',
        ]);

        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role_id' => $adminRole->id,
        ]);

        $existingPasswordHash = $admin->password;

        $response = $this->actingAs($admin, 'admin')->post(route('admin.users.reset-password', $admin, false));

        $response->assertForbidden();
        $this->assertSame($existingPasswordHash, $admin->fresh()->password);
        Mail::assertNothingSent();
    }
}