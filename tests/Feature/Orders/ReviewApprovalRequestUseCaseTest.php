<?php

namespace Tests\Feature\Orders;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Orders\Application\UseCases\Admin\ReviewApprovalRequestUseCase;
use App\Modules\Orders\Domain\Enums\ApprovalStatusEnum;
use App\Modules\Orders\Domain\Enums\ApprovalTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\ApprovalRequest;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

class ReviewApprovalRequestUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_price_change_updates_order_financials_and_agent_collection(): void
    {
        $this->mock(SendNotificationUseCase::class, function (MockInterface $mock): void {
            $mock->shouldReceive('execute')->once()->andReturn([]);
        });

        $admin = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::SuperAdmin->value,
        ]);
        $agentUser = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::DeliveryAgent->value,
        ]);
        $companyUser = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::ShippingCompany->value,
        ]);

        $agent = DeliveryAgent::query()->forceCreate([
            'delivery_agent_id' => (string) Str::uuid(),
            'user_id' => $agentUser->user_id,
            'commission_value' => 25,
            'balance' => 100,
        ]);

        $company = ShippingCompany::query()->forceCreate([
            'shipping_company_id' => (string) Str::uuid(),
            'user_id' => $companyUser->user_id,
            'company_name' => 'Acme Logistics',
        ]);

        $order = Order::query()->forceCreate([
            'order_id' => (string) Str::uuid(),
            'reference_no' => 'EXT-200',
            'reference_code' => 'ACME-200',
            'shipping_company_id' => $company->shipping_company_id,
            'delivery_agent_id' => $agent->delivery_agent_id,
            'status' => OrderStatusEnum::AwaitingApproval->value,
        ]);

        DB::table('order_financials')->insert([
            'order_financial_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'original_amount' => 800,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $approval = ApprovalRequest::query()->forceCreate([
            'approval_request_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'approval_type' => ApprovalTypeEnum::PriceChange->value,
            'approval_status' => ApprovalStatusEnum::Pending->value,
            'requested_by' => $agentUser->user_id,
            'original_amount' => 800,
            'requested_amount' => 650,
            'reason' => 'Customer accepted a lower price',
        ]);

        app(ReviewApprovalRequestUseCase::class)->execute(
            approvalRequestId: $approval->approval_request_id,
            action: 'approve',
            adminUserId: $admin->user_id,
            reviewNotes: null,
        );

        $agent->refresh();

        $this->assertSame('750.00', $agent->balance);
        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'status' => OrderStatusEnum::DeliveredPriceChanged->value,
        ]);
        $this->assertDatabaseHas('order_financials', [
            'order_id' => $order->order_id,
            'approved_amount' => 650,
            'collected_amount' => 650,
            'commission_amount' => 25,
            'net_due_company' => 625,
        ]);
        $this->assertDatabaseHas('collections', [
            'order_id' => $order->order_id,
            'delivery_agent_id' => $agent->delivery_agent_id,
            'collection_type' => CollectionTypeEnum::Cod->value,
            'collected_amount' => 650,
            'commission_amount' => 25,
            'net_due' => 625,
        ]);
    }
}
