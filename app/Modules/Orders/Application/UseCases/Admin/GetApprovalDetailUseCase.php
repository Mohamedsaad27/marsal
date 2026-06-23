<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Domain\Interfaces\ApprovalRequestRepositoryInterface;
use App\Modules\Orders\Infrastructure\Database\Models\ApprovalRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetApprovalDetailUseCase
{
    public function __construct(
        private ApprovalRequestRepositoryInterface $repository,
    ) {}

    public function execute(string $approvalRequestId): ApprovalRequest
    {
        $record = $this->repository->findWithRelations($approvalRequestId);

        if ($record === null) {
            throw new NotFoundHttpException(__('orders::messages.approval_not_found'));
        }

        return $record;
    }
}
