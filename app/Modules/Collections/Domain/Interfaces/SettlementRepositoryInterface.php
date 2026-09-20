<?php

namespace App\Modules\Collections\Domain\Interfaces;

use App\Modules\Collections\Application\DTOs\CreateSettlementDTO;
use App\Modules\Collections\Application\DTOs\SettlementFilterDTO;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SettlementRepositoryInterface
{
    public function stats(): array;

    public function paginate(SettlementFilterDTO $filter): LengthAwarePaginator;

    public function findOrFail(string $settlementId): Settlement;

    public function createFromEligibleCollections(CreateSettlementDTO $dto): Settlement;

    public function approve(string $settlementId): Settlement;

    public function markPaid(
        string $settlementId,
        string $paymentMethod,
        ?string $paymentReference,
        ?string $notes,
    ): Settlement;

    public function findForCompany(string $settlementId, string $companyId): ?Settlement;

    /**
     * Returns settlement reference, signed net amount, direction, payable amount, and paid_at,
     * or null if none exists.
     *
     * @return array{reference: string, net_amount: float, payment_direction: string, payable_amount: float, paid_at: string}|null
     */
    public function getLastPaidForCompany(string $companyId): ?array;
}
