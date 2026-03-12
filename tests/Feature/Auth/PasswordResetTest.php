<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_is_not_available(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertNotFound();
    }

    public function test_forgot_password_link_cannot_be_requested(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertNotFound();
    }

    public function test_reset_password_screen_is_not_available(): void
    {
        $response = $this->get('/reset-password/example-token');

        $response->assertNotFound();
    }

    public function test_reset_password_submission_is_not_available(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
                'token' => 'example-token',
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertNotFound();
    }
}
