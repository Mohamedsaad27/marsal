<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Infrastructure\Mail\PasswordResetOtpMail;
use App\Modules\Auth\Infrastructure\Mail\WelcomeUserMail;
use App\Modules\Roles\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Users\Infrastructure\Database\Models\User;
use App\Modules\Users\Infrastructure\Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SuperAdminSeeder::class);
    }

    public function test_forgot_password_sends_otp_mail_for_known_email(): void
    {
        Mail::fake();

        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com');

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $email])
            ->assertOk()
            ->assertJsonPath('isSuccess', true);

        Mail::assertSent(PasswordResetOtpMail::class, fn (PasswordResetOtpMail $mail) => $mail->hasTo($email));
    }

    public function test_forgot_password_returns_success_for_unknown_email(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown@example.com'])
            ->assertOk()
            ->assertJsonPath('isSuccess', true);

        Mail::assertNothingSent();
    }

    public function test_reset_password_with_valid_otp(): void
    {
        Mail::fake();

        $user = User::query()->where('email', env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com'))->first();
        $plainOtp = '482910';

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);

        $record = \App\Modules\Auth\Infrastructure\Database\Models\PasswordResetOtp::query()
            ->where('email', $user->email)
            ->latest('created_at')
            ->first();

        $record->update(['otp_hash' => Hash::make($plainOtp)]);

        $newPassword = 'NewSecure@99';

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'otp' => $plainOtp,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));

        $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->email,
            'password' => $newPassword,
        ])->assertOk();
    }

    public function test_reset_password_fails_with_wrong_otp(): void
    {
        Mail::fake();

        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com');

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $email]);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $email,
            'otp' => '000000',
            'password' => 'NewSecure@99',
            'password_confirmation' => 'NewSecure@99',
        ])->assertStatus(422);
    }

    public function test_change_password_requires_auth(): void
    {
        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'Admin@123',
            'password' => 'NewSecure@88',
            'password_confirmation' => 'NewSecure@88',
        ])->assertUnauthorized();
    }

    public function test_change_password_success(): void
    {
        $token = $this->loginToken();
        $newPassword = 'Changed@Secure99';

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => env('SUPER_ADMIN_PASSWORD', 'Admin@123'),
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'identifier' => env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com'),
            'password' => $newPassword,
        ])->assertOk();
    }

    public function test_change_password_fails_with_wrong_current(): void
    {
        $token = $this->loginToken();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'wrong-password',
                'password' => 'NewSecure@77',
                'password_confirmation' => 'NewSecure@77',
            ])
            ->assertStatus(422);
    }

    public function test_admin_create_user_dispatches_welcome_mail(): void
    {
        Mail::fake();

        $token = $this->loginToken();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/users', [
                'name' => 'Mail Test User',
                'email' => 'mailtest@example.com',
                'phone' => '01055556666',
                'password' => 'TempPass@123',
                'account_type' => 'staff_member',
                'roles' => ['staff_member'],
                'profile' => [
                    'department' => 'IT',
                    'job_title' => 'Tester',
                ],
            ])
            ->assertCreated();

        Mail::assertSent(WelcomeUserMail::class, fn (WelcomeUserMail $mail) => $mail->hasTo('mailtest@example.com'));
    }

    protected function loginToken(): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com'),
            'password' => env('SUPER_ADMIN_PASSWORD', 'Admin@123'),
        ]);

        return $response->json('data.access_token');
    }
}
