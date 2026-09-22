<?php

namespace Tests\Feature\Orders;

use App\Modules\Orders\Application\UseCases\Agent\GetAgentDashboardUseCase;
use App\Modules\Orders\Presentation\Http\Resources\AgentDashboardResource;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_collected_amount_uses_current_settlement_balance(): void
    {
        $user = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::DeliveryAgent->value,
        ]);

        $agent = DeliveryAgent::query()->forceCreate([
            'delivery_agent_id' => (string) Str::uuid(),
            'user_id' => $user->user_id,
            'commission_value' => 5,
            'balance' => 720,
        ]);

        $payload = $this->dashboardPayload($user);

        $this->assertSame(720.0, $payload['today']['collected_amount']);

        $agent->update(['balance' => 0]);

        $payload = $this->dashboardPayload($user);

        $this->assertSame(0.0, $payload['today']['collected_amount']);
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardPayload(User $user): array
    {
        $data = app(GetAgentDashboardUseCase::class)->execute($user->user_id);

        return (new AgentDashboardResource($data))->toArray(request());
    }
}
