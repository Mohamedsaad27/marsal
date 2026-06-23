<?php

namespace App\Modules\Returns\Application\UseCases\Admin;

use App\Modules\Returns\Domain\Enums\ReturnStatusEnum;
use App\Modules\Returns\Domain\Interfaces\ReturnRepositoryInterface;
use App\Modules\Returns\Infrastructure\Database\Models\OrderReturn;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ReceiveReturnUseCase
{
    public function __construct(
        private ReturnRepositoryInterface $repository,
    ) {}

    public function execute(string $returnId): OrderReturn
    {
        $record = $this->repository->findOrFail($returnId);

        if ($record->return_status !== ReturnStatusEnum::Pending) {
            throw new UnprocessableEntityHttpException(
                __('returns::messages.not_in_pending_status')
            );
        }

        return $this->repository->markReceived($returnId);
    }
}
