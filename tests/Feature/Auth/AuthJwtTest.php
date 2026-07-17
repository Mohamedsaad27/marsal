<?php

namespace Tests\Feature\Auth;

use App\Modules\Roles\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Users\Infrastructure\Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthJwtTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SuperAdminSeeder::class);
    }

    public function test_login_with_email_returns_jwt(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com'),
            'password' => env('SUPER_ADMIN_PASSWORD', 'Admin@123'),
        ]);

        $response->assertOk()
            ->assertJsonPath('isSuccess', true)
            ->assertJsonStructure([
                'data' => ['access_token', 'token_type', 'expires_in', 'user' => ['user_id', 'roles', 'permissions']],
            ]);
    }

    public function test_login_with_phone_returns_jwt(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => '01098001021',
            'password' => env('SUPER_ADMIN_PASSWORD', 'Admin@123'),
        ]);

        $response->assertOk()->assertJsonPath('isSuccess', true);
    }

    public function test_login_updates_fcm_token_when_provided(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com');
        $fcmToken = 'test-fcm-token';

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $email,
            'password' => env('SUPER_ADMIN_PASSWORD', 'Admin@123'),
            'fcm_token' => $fcmToken,
        ]);

        $response->assertOk()->assertJsonPath('isSuccess', true);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'fcm_token' => $fcmToken,
        ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com'),
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $token = $this->loginAsSuperAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.account_type', 'super_admin')
            ->assertJsonPath('data.roles.0', 'super_admin');
    }

    public function test_register_route_does_not_exist(): void
    {
        $this->postJson('/api/v1/auth/register', [])->assertNotFound();
    }

    protected function loginAsSuperAdmin(): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com'),
            'password' => env('SUPER_ADMIN_PASSWORD', 'Admin@123'),
        ]);

        return $response->json('data.access_token');
    }
}
