<?php

namespace Tests\Feature\Admin;

use App\Modules\Roles\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Users\Infrastructure\Database\Models\User;
use App\Modules\Users\Infrastructure\Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppWelcomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SuperAdminSeeder::class);
    }

    public function test_create_user_stores_welcome_whatsapp_url(): void
    {
        $token = $this->loginAsSuperAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/staff-members', [
                'name' => 'WhatsApp User',
                'email' => 'wauser@example.com',
                'phone' => '01077778888',
                'password' => 'TempPass@123',
                'roles' => ['staff_member'],
                'profile' => [
                    'department' => 'ops',
                    'job_title' => 'Coordinator',
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.phone', '01077778888');

        $url = $response->json('data.welcome_whatsapp_url');
        $this->assertNotEmpty($url);
        $this->assertStringStartsWith('https://wa.me/201077778888?text=', $url);

        $user = User::query()->where('email', 'wauser@example.com')->first();
        $this->assertSame($url, $user->welcome_whatsapp_url);
    }

    protected function loginAsSuperAdmin(): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => env('SUPER_ADMIN_EMAIL', 'admin@shipops.local'),
            'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
        ]);

        return $response->json('data.access_token');
    }
}
