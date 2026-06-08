<?php

namespace Tests\Feature\Admin;

use App\Modules\Locations\Infrastructure\Database\Models\Address;
use App\Modules\Locations\Infrastructure\Database\Models\City;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;
use App\Modules\Roles\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Users\Application\DTOs\CreateUserDTO;
use App\Modules\Users\Application\UseCases\CreateUserUseCase;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserListPagesTest extends TestCase
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

    public function test_staff_members_index_filters_by_department_and_status(): void
    {
        $create = app(CreateUserUseCase::class);

        $create->execute(new CreateUserDTO(
            name: 'Ops One',
            email: 'ops1@example.com',
            phone: '01090000001',
            password: 'password123',
            accountType: AccountTypeEnum::StaffMember,
            roles: ['staff_member'],
            profile: ['department' => 'operations', 'job_title' => 'Coordinator'],
        ));

        $create->execute(new CreateUserDTO(
            name: 'Finance One',
            email: 'finance1@example.com',
            phone: '01090000002',
            password: 'password123',
            accountType: AccountTypeEnum::StaffMember,
            roles: ['staff_member'],
            profile: ['department' => 'finance', 'job_title' => 'Analyst'],
        ));

        $this->auth()
            ->getJson('/api/v1/admin/staff-members?department=operations')
            ->assertOk()
            ->assertJsonPath('data.counts.total', 2)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.staff_member.department', 'operations');

        $this->auth()
            ->getJson('/api/v1/admin/staff-members?is_active=1')
            ->assertOk()
            ->assertJsonStructure(['data' => ['counts' => ['total', 'active', 'inactive'], 'items']]);
    }

    public function test_shipping_companies_index_filters_by_city(): void
    {
        $governorate = Governorate::query()->create([
            'name_ar' => 'القاهرة',
            'name_en' => 'Cairo',
            'code' => 'CAI',
            'is_active' => true,
        ]);

        $city = City::query()->create([
            'governorate_id' => $governorate->governorate_id,
            'name_ar' => 'مدينة نصر',
            'name_en' => 'Nasr City',
            'code' => 'NSR',
            'is_active' => true,
        ]);

        $create = app(CreateUserUseCase::class);

        $withCity = $create->execute(new CreateUserDTO(
            name: 'City Co',
            email: 'cityco@example.com',
            phone: '01090000003',
            password: 'password123',
            accountType: AccountTypeEnum::ShippingCompany,
            roles: ['shipping_company'],
            profile: ['company_name' => 'City Co'],
        ));

        Address::query()->create([
            'user_id' => $withCity->user_id,
            'city_id' => $city->city_id,
            'address_line' => 'Main street',
            'is_default' => true,
        ]);

        $create->execute(new CreateUserDTO(
            name: 'Other Co',
            email: 'otherco@example.com',
            phone: '01090000004',
            password: 'password123',
            accountType: AccountTypeEnum::ShippingCompany,
            roles: ['shipping_company'],
            profile: ['company_name' => 'Other Co'],
        ));

        $this->auth()
            ->getJson('/api/v1/admin/shipping-companies?city_id='.$city->city_id)
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.shipping_company.company_name', 'City Co');
    }

    public function test_delivery_agents_index_filters_by_commission_type(): void
    {
        $create = app(CreateUserUseCase::class);

        $create->execute(new CreateUserDTO(
            name: 'Fixed Agent',
            email: 'fixed@example.com',
            phone: '01090000005',
            password: 'password123',
            accountType: AccountTypeEnum::DeliveryAgent,
            roles: ['delivery_agent'],
            profile: ['commission_type' => 2, 'commission_value' => 50],
        ));

        $create->execute(new CreateUserDTO(
            name: 'Percent Agent',
            email: 'percent@example.com',
            phone: '01090000006',
            password: 'password123',
            accountType: AccountTypeEnum::DeliveryAgent,
            roles: ['delivery_agent'],
            profile: ['commission_type' => 1, 'commission_value' => 10],
        ));

        $this->auth()
            ->getJson('/api/v1/admin/delivery-agents?commission_type=2')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.delivery_agent.commission.type.code', 2);
    }

    public function test_delivery_agent_supervisors_index_returns_only_supervisors(): void
    {
        $create = app(CreateUserUseCase::class);

        $supervisor = $create->execute(new CreateUserDTO(
            name: 'Supervisor Alpha',
            email: 'supervisor-alpha@example.com',
            phone: '01090000007',
            password: 'password123',
            accountType: AccountTypeEnum::DeliveryAgent,
            roles: ['delivery_agent'],
            profile: ['national_id' => '29001011234567'],
        ));

        $supervisorId = $supervisor->deliveryAgent->delivery_agent_id;

        $create->execute(new CreateUserDTO(
            name: 'Subordinate Agent',
            email: 'subordinate@example.com',
            phone: '01090000008',
            password: 'password123',
            accountType: AccountTypeEnum::DeliveryAgent,
            roles: ['delivery_agent'],
            profile: [
                'supervisor_agent_id' => $supervisorId,
                'national_id' => '29001019876543',
            ],
        ));

        $this->auth()
            ->getJson('/api/v1/admin/delivery-agents/supervisors')
            ->assertOk()
            ->assertJsonPath('isSuccess', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Supervisor Alpha')
            ->assertJsonPath('data.0.phone', '01090000007');

        $this->auth()
            ->getJson('/api/v1/admin/delivery-agents/supervisors?search=Subordinate')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    protected function auth(): static
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}");
    }
}
