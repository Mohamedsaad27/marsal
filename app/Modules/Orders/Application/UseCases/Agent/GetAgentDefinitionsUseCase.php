<?php

namespace App\Modules\Orders\Application\UseCases\Agent;

use App\Modules\Orders\Domain\Services\AgentReferenceDefinitionsService;

class GetAgentDefinitionsUseCase
{
    public function __construct(
        private AgentReferenceDefinitionsService $definitions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        return $this->definitions->getAll();
    }
}
