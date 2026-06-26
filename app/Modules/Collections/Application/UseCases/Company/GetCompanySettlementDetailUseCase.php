<?php

namespace App\Modules\Collections\Application\UseCases\Company;

use App\Modules\Collections\Application\Exceptions\SettlementNotFoundException;
use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;

class GetCompanySettlementDetailUseCase
{
    public function __construct(
        private SettlementRepositoryInterface $repository,
    ) {}

    public function execute(string $settlementId, string $companyId): Settlement
    {
        $settlement = $this->repository->findForCompany($settlementId, $companyId);

        if ($settlement === null) {
            throw new SettlementNotFoundException();
        }

        return $settlement;
    }
}
