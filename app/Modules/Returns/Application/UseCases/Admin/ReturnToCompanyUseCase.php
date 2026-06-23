<?php

namespace App\Modules\Returns\Application\UseCases\Admin;

use App\Modules\Returns\Domain\Enums\ReturnStatusEnum;
use App\Modules\Returns\Domain\Interfaces\ReturnRepositoryInterface;
use App\Modules\Returns\Infrastructure\Database\Models\OrderReturn;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ReturnToCompanyUseCase
{
    public function __construct(
        private ReturnRepositoryInterface $repository,
    ) {}

    public function execute(string $returnId): OrderReturn
    {
        $record = $this->repository->findOrFail($returnId);

        if ($record->return_status !== ReturnStatusEnum::ReceivedByAdmin) {
            throw new UnprocessableEntityHttpException(
                __('returns::messages.not_in_received_status')
            );
        }

        return $this->repository->markSentToCompany($returnId);
    }
}
