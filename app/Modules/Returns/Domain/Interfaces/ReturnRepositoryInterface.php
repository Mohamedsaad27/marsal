<?php

namespace App\Modules\Returns\Domain\Interfaces;

use App\Modules\Returns\Infrastructure\Database\Models\OrderReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReturnRepositoryInterface
{
    public function stats(): array;

    public function paginate(
        ?int $status,
        ?string $companyId,
        ?string $agentId,
        int $perPage,
    ): LengthAwarePaginator;

    public function findOrFail(string $returnId): OrderReturn;

    public function markReceived(string $returnId): OrderReturn;

    public function markSentToCompany(string $returnId): OrderReturn;
}
