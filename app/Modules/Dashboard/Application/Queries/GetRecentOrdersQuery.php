<?php

namespace App\Modules\Dashboard\Application\Queries;

use App\Modules\Dashboard\Infrastructure\Database\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetRecentOrdersQuery
{
    private const ALLOWED_SORT_COLUMNS = [
        'created_at',
        'updated_at',
        'internal_code',
        'status',
    ];

    public function execute(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $sortBy = in_array($filters['sort_by'] ?? 'created_at', self::ALLOWED_SORT_COLUMNS, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;

        $query = Order::query()
            ->with([
                'customerInfo',
                'financials',
                'deliveryAgent.user',
                'shippingCompany',
                'address.governorate',
                'address.city',
            ]);

        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        if ($search !== null && $search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('internal_code', 'like', $term)
                    ->orWhereHas('customerInfo', function ($customerQuery) use ($term) {
                        $customerQuery->where('customer_name', 'like', $term)
                            ->orWhere('customer_phone', 'like', $term);
                    });
            });
        }

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }
}
