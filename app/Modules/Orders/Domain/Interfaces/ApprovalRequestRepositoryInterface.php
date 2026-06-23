<?php

namespace App\Modules\Orders\Domain\Interfaces;

use App\Modules\Orders\Infrastructure\Database\Models\ApprovalRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ApprovalRequestRepositoryInterface
{
    public function stats(): array;

    public function paginate(
        ?int $status,
        ?int $type,
        ?string $agentId,
        int $perPage,
    ): LengthAwarePaginator;

    public function findWithRelations(string $approvalRequestId): ?ApprovalRequest;

    public function markReviewed(
        string $approvalRequestId,
        int $approvalStatus,
        string $reviewedBy,
        ?string $reviewNotes,
    ): ApprovalRequest;
}
