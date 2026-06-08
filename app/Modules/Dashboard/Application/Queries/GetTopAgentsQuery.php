<?php

namespace App\Modules\Dashboard\Application\Queries;

use App\Modules\Dashboard\Application\Services\DashboardCacheService;
use App\Modules\Dashboard\Domain\Enums\OrderStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetTopAgentsQuery
{
    public function __construct(
        private readonly DashboardCacheService $cache,
    ) {}

    /**
     * @return list<array{
     *     rank: int,
     *     delivery_agent_id: string,
     *     name: string|null,
     *     city: string|null,
     *     avatar_url: string|null,
     *     shipments_today: int
     * }>
     */
    public function execute(): array
    {
        return $this->cache->remember('top-agents', fn () => $this->compute());
    }

    private function compute(): array
    {
        $today = Carbon::today()->toDateString();

        $rows = DB::table('orders as o')
            ->join('delivery_agents as da', 'da.delivery_agent_id', '=', 'o.delivery_agent_id')
            ->join('users as u', 'u.user_id', '=', 'da.user_id')
            ->leftJoin('order_addresses as oa', function ($join) {
                $join->on('oa.order_id', '=', 'o.order_id')
                    ->whereNull('oa.deleted_at');
            })
            ->leftJoin('cities as c', 'c.city_id', '=', 'oa.city_id')
            ->whereNull('o.deleted_at')
            ->whereNull('da.deleted_at')
            ->whereNull('u.deleted_at')
            ->where('o.status', OrderStatusEnum::Delivered->value)
            ->whereDate('o.updated_at', $today)
            ->whereNotNull('o.delivery_agent_id')
            ->select([
                'o.delivery_agent_id',
                'u.name',
                DB::raw('MAX(c.name_ar) as city'),
                'u.avatar as avatar_url',
                DB::raw('COUNT(*) as shipments_today'),
            ])
            ->groupBy('o.delivery_agent_id', 'u.name', 'u.avatar')
            ->orderByDesc('shipments_today')
            ->limit(4)
            ->get();

        $rank = 1;
        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'rank' => $rank++,
                'delivery_agent_id' => $row->delivery_agent_id,
                'name' => $row->name,
                'city' => $row->city,
                'avatar_url' => $row->avatar_url,
                'shipments_today' => (int) $row->shipments_today,
            ];
        }

        return $result;
    }
}
