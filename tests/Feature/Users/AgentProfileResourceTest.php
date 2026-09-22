<?php

namespace Tests\Feature\Users;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Users\Application\UseCases\Agent\GetAgentProfileUseCase;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\User;
use App\Modules\Users\Presentation\Http\Resources\AgentProfileResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentProfileResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_profile_balance_uses_collection_net_due_only(): void
    {
        $user = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::DeliveryAgent->value,
        ]);

        $agent = DeliveryAgent::query()->forceCreate([
            'delivery_agent_id' => (string) Str::uuid(),
            'user_id' => $user->user_id,
            'commission_value' => 0,
            'balance' => 999,
        ]);

        $this->createCollection($agent, collectedAmount: 120, agentCommission: 20);
        $this->createCollection($agent, collectedAmount: 50, agentCommission: 80);

        $data = app(GetAgentProfileUseCase::class)->execute($user->user_id);
        $payload = (new AgentProfileResource($data))->toArray(request());

        $this->assertSame(70.0, $payload['agent']['balance']);
        $this->assertSame('999.00', $agent->fresh()->balance);
    }

    private function createCollection(
        DeliveryAgent $agent,
        float $collectedAmount,
        float $agentCommission,
    ): Collection {
        return Collection::query()->forceCreate([
            'collection_id' => (string) Str::uuid(),
            'delivery_agent_id' => $agent->delivery_agent_id,
            'collection_type' => CollectionTypeEnum::Cod->value,
            'collected_amount' => $collectedAmount,
            'agent_commission_amount' => $agentCommission,
            'agent_net_due' => $collectedAmount - $agentCommission,
            'system_commission_amount' => 0,
            'company_net_due' => $collectedAmount,
            'collected_at' => now(),
        ]);
    }
}
