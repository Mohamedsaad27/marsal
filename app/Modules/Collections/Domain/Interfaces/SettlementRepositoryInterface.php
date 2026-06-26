<?php

namespace App\Modules\Collections\Domain\Interfaces;

use App\Modules\Collections\Application\DTOs\CreateSettlementDTO;
use App\Modules\Collections\Application\DTOs\SettlementFilterDTO;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

interface SettlementRepositoryInterface
{
    public function stats(): array;

    public function paginate(SettlementFilterDTO $filter): LengthAwarePaginator;

    public function findOrFail(string $settlementId): Settlement;

    public function findEligibleCollections(CreateSettlementDTO $dto): SupportCollection;

    public function createFromCollections(CreateSettlementDTO $dto, SupportCollection $collections): Settlement;

    public function approve(string $settlementId): Settlement;

    public function markPaid(
        string $settlementId,
        string $paymentMethod,
        ?string $paymentReference,
        ?string $notes,
    ): Settlement;

    public function countEligibleCollections(Settlement $settlement): int;

    public function findForCompany(string $settlementId, string $companyId): ?Settlement;

    /**
     * Returns settlement reference, net_amount, and paid_at for the last paid company settlement,
     * or null if none exists.
     *
     * @return array{reference: string, net_amount: float, paid_at: string}|null
     */
    public function getLastPaidForCompany(string $companyId): ?array;
}
