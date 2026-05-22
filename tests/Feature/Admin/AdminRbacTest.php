<?php

namespace Tests\Feature\Admin;

use App\Modules\Roles\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Users\Infrastructure\Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRbacTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $login = $this->postJson('/api/v1/auth/login', [
            'identifier' => env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com'),
            'password' => env('SUPER_ADMIN_PASSWORD', 'Admin@123'),
        ]);

        $this->token = $login->json('data.access_token');
    }

    public function test_super_admin_can_create_role_and_permission(): void
    {
        $response = $this->auth()->postJson('/api/v1/admin/roles', [
            'name' => 'finance_manager',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'finance_manager');

        $perm = $this->auth()->postJson('/api/v1/admin/permissions', [
            'name' => 'finance.reports.view',
        ]);

        $perm->assertCreated();
    }

    public function test_super_admin_can_create_shipping_company_with_roles(): void
    {
        $response = $this->auth()->postJson('/api/v1/admin/shipping-companies', [
            'name' => 'Acme Logistics',
            'email' => 'acme@example.com',
            'phone' => '01011111111',
            'password' => 'password123',
            'roles' => ['shipping_company'],
            'profile' => [
                'company_name' => 'Acme Logistics',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.account_type', 'shipping_company')
            ->assertJsonPath('data.roles.0', 'shipping_company');
    }

    public function test_super_admin_can_create_staff_member(): void
    {
        $response = $this->auth()->postJson('/api/v1/admin/staff-members', [
            'name' => 'Ops Staff',
            'email' => 'staff@marsal.com',
            'phone' => '01044444444',
            'password' => 'password123',
            'roles' => ['staff_member'],
            'profile' => [
                'department' => 'operations',
                'job_title' => 'Operations Coordinator',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.account_type', 'staff_member')
            ->assertJsonPath('data.roles.0', 'staff_member')
            ->assertJsonPath('data.staff_member.department', 'operations');
    }

    public function test_super_admin_can_create_delivery_agent_with_custom_role(): void
    {
        Role::query()->create(['name' => 'field_lead', 'guard_name' => 'api']);

        $response = $this->auth()->postJson('/api/v1/admin/delivery-agents', [
            'name' => 'Agent One',
            'email' => 'agent1@example.com',
            'phone' => '01022222222',
            'password' => 'password123',
            'roles' => ['field_lead'],
            'profile' => [],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.roles.0', 'field_lead');
    }

    public function test_sync_role_permissions(): void
    {
        $role = Role::query()->create(['name' => 'ops_manager', 'guard_name' => 'api']);

        $this->auth()->putJson("/api/v1/admin/roles/{$role->id}/permissions", [
            'permissions' => ['users.view', 'users.create'],
        ])->assertOk();
    }

    protected function auth(): static
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}");
    }
}
