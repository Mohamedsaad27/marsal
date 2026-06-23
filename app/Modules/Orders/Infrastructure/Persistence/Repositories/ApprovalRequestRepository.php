<?php

namespace App\Modules\Orders\Infrastructure\Persistence\Repositories;

use App\Modules\Orders\Domain\Interfaces\ApprovalRequestRepositoryInterface;
use App\Modules\Orders\Infrastructure\Database\Models\ApprovalRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ApprovalRequestRepository implements ApprovalRequestRepositoryInterface
{
    private const LIST_RELATIONS = [
        'order',
        'requestedByUser',
    ];

    private const DETAIL_RELATIONS = [
        'order.customerInfo',
        'order.address.governorate',
        'order.address.city',
        'order.shippingCompany.user',
        'order.deliveryAgent.user',
        'requestedByUser',
        'reviewedByUser',
    ];

    public function stats(): array
    {
        $base    = ApprovalRequest::query();
        $urgency = Carbon::now()->addMinutes(30);

        $awaiting = (clone $base)->where('approval_status', 1)->count();
        $urgent   = (clone $base)
            ->where('approval_status', 1)
            ->where('expires_at', '<=', $urgency)
            ->whereNotNull('expires_at')
            ->count();
        $approved = (clone $base)->where('approval_status', 2)->count();
        $rejected = (clone $base)->where('approval_status', 3)->count();
        $expired  = (clone $base)->where('approval_status', 4)->count();

        return [
            'awaiting' => $awaiting,
            'urgent'   => $urgent,
            'approved' => $approved,
            'rejected' => $rejected,
            'expired'  => $expired,
        ];
    }

    public function paginate(
        ?int $status,
        ?int $type,
        ?string $agentId,
        int $perPage,
    ): LengthAwarePaginator {
        $query = ApprovalRequest::query()
            ->with(self::LIST_RELATIONS)
            ->orderByRaw('CASE WHEN approval_status = 1 THEN 0 ELSE 1 END')
            ->orderBy('expires_at')
            ->orderByDesc('created_at');

        if ($status !== null) {
            $query->where('approval_status', $status);
        }

        if ($type !== null) {
            $query->where('approval_type', $type);
        }

        if ($agentId !== null) {
            $query->whereHas('order', fn ($q) => $q->where('delivery_agent_id', $agentId));
        }

        return $query->paginate($perPage);
    }

    public function findWithRelations(string $approvalRequestId): ?ApprovalRequest
    {
        return ApprovalRequest::query()
            ->with(self::DETAIL_RELATIONS)
            ->where('approval_request_id', $approvalRequestId)
            ->first();
    }

    public function markReviewed(
        string $approvalRequestId,
        int $approvalStatus,
        string $reviewedBy,
        ?string $reviewNotes,
    ): ApprovalRequest {
        $record = ApprovalRequest::query()
            ->findOrFail($approvalRequestId);

        $record->update([
            'approval_status' => $approvalStatus,
            'reviewed_by'     => $reviewedBy,
            'reviewed_at'     => Carbon::now(),
            'review_notes'    => $reviewNotes,
        ]);

        return $record->fresh(self::DETAIL_RELATIONS);
    }
}
