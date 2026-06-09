<?php

namespace Database\Seeders;

use App\Modules\Dashboard\Domain\Enums\OrderStatusEnum;
use App\Modules\Locations\Infrastructure\Database\Models\City;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    private int $counter = 1;

    /** @var array<string> */
    private array $governorateIds = [];

    /** @var array<string, array<string>> city_id list keyed by governorate_id */
    private array $cityMap = [];

    /** @var Collection<int, ShippingCompany> */
    private Collection $companies;

    /** @var Collection<int, DeliveryAgent> */
    private Collection $agents;

    // ─────────────────────────────────────────────────────────────────────────
    // Arabic sample data
    // ─────────────────────────────────────────────────────────────────────────

    private array $customerNames = [
        'محمد علي', 'أحمد حسن', 'فاطمة إبراهيم', 'سارة محمود',
        'خالد عمر', 'نورا حسن', 'عمر سالم', 'ليلى أحمد',
        'كريم طارق', 'منى رضا', 'هشام مصطفى', 'رانيا حامد',
        'تامر حسين', 'دينا فاروق', 'أمير جمال',
    ];

    private array $itemDescriptions = [
        'ملابس رجالية', 'ملابس نسائية', 'أجهزة إلكترونية', 'هواتف محمولة',
        'أدوات منزلية', 'كتب ومجلات', 'مستحضرات تجميل', 'أحذية رياضية',
        'حقائب جلدية', 'إكسسوارات موبايل', 'ألعاب أطفال', 'مواد غذائية',
    ];

    private array $streetNames = [
        'شارع التحرير', 'شارع النصر', 'شارع الجمهورية', 'شارع الملك فيصل',
        'شارع المنتزه', 'شارع الهرم', 'شارع فيصل', 'شارع البطل أحمد عبد العزيز',
    ];

    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        if (DB::table('orders')->count() > 0) {
            $this->command->info('Orders already seeded. Skipping.');
            return;
        }

        $this->companies = ShippingCompany::all();
        $this->agents    = DeliveryAgent::all();

        if ($this->companies->isEmpty()) {
            $this->command->error('No shipping companies found. Run ShippingCompanySeeder first.');
            return;
        }

        if ($this->agents->isEmpty()) {
            $this->command->error('No delivery agents found. Run DeliveryAgentSeeder first.');
            return;
        }

        $this->loadLocations();

        if (empty($this->governorateIds)) {
            $this->command->error('No governorates found. Run EgyptLocationsSeeder first.');
            return;
        }

        $now = Carbon::now();

        // Week start (Saturday-based — matches GetShipmentsChartQuery)
        $thisWeekStart  = $now->copy()->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $lastWeekStart  = $thisWeekStart->copy()->subWeek();

        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd   = $now->copy()->subMonth()->endOfMonth();

        $this->command->info('Seeding orders…');

        // ── A. Today — delivered orders for GetTopAgentsQuery ────────────────
        // The query filters: status=3 AND whereDate('updated_at', today)
        // We give 4 agents 4 delivered orders each today.
        $topAgents = $this->agents->take(4);
        foreach ($topAgents as $agent) {
            for ($i = 0; $i < 4; $i++) {
                $created = Carbon::today()->setHour(rand(7, 10))->setMinute(rand(0, 59));
                $updated = $created->copy()->addHours(rand(2, 5));
                $this->insertOrder(
                    status:      OrderStatusEnum::Delivered->value,
                    agent:       $agent,
                    createdAt:   $created,
                    updatedAt:   $updated,
                    deliveredAt: $updated,
                );
            }
        }

        // ── B. Today — extra pending orders (for chart "today" column) ───────
        for ($i = 0; $i < 5; $i++) {
            $created = Carbon::today()->setHour(rand(9, 15))->setMinute(rand(0, 59));
            $this->insertOrder(
                status:    OrderStatusEnum::Pending->value,
                agent:     null,
                createdAt: $created,
                updatedAt: $created,
            );
        }

        // ── C. This week — per-day orders (for GetShipmentsChartQuery) ───────
        // Days from weekStart up to (but not including) today
        $daysElapsed = (int) $thisWeekStart->diffInDays($now->copy()->startOfDay());
        for ($day = 0; $day < $daysElapsed; $day++) {
            $date = $thisWeekStart->copy()->addDays($day);

            // Delivered — 6 per past day this week (also feeds GetAvgDeliveryTimeQuery)
            for ($i = 0; $i < 6; $i++) {
                $created = $date->copy()->setHour(rand(7, 11))->setMinute(rand(0, 59));
                $updated = $created->copy()->addHours(rand(3, 9)); // TIMESTAMPDIFF basis
                $this->insertOrder(
                    status:      OrderStatusEnum::Delivered->value,
                    agent:       $this->agents->random(),
                    createdAt:   $created,
                    updatedAt:   $updated,
                    deliveredAt: $updated,
                );
            }

            // Pending — 4 per day
            for ($i = 0; $i < 4; $i++) {
                $created = $date->copy()->setHour(rand(9, 15))->setMinute(rand(0, 59));
                $this->insertOrder(
                    status:    OrderStatusEnum::Pending->value,
                    agent:     null,
                    createdAt: $created,
                    updatedAt: $created,
                );
            }

            // Postponed — 2 per day (counted in pending on chart)
            for ($i = 0; $i < 2; $i++) {
                $created = $date->copy()->setHour(rand(8, 13))->setMinute(rand(0, 59));
                $updated = $created->copy()->addHours(1);
                $this->insertOrder(
                    status:    OrderStatusEnum::Postponed->value,
                    agent:     $this->agents->random(),
                    createdAt: $created,
                    updatedAt: $updated,
                );
            }
        }

        // ── D. Last week — for avg-delivery-time & summary comparison ────────
        for ($day = 0; $day < 7; $day++) {
            $date = $lastWeekStart->copy()->addDays($day);

            // Delivered — 5 per day (avg delivery time comparison baseline)
            for ($i = 0; $i < 5; $i++) {
                $created = $date->copy()->setHour(rand(8, 12))->setMinute(rand(0, 59));
                $updated = $created->copy()->addHours(rand(5, 12)); // slightly slower last week
                $this->insertOrder(
                    status:      OrderStatusEnum::Delivered->value,
                    agent:       $this->agents->random(),
                    createdAt:   $created,
                    updatedAt:   $updated,
                    deliveredAt: $updated,
                );
            }

            // Pending — 3 per day
            for ($i = 0; $i < 3; $i++) {
                $created = $date->copy()->setHour(rand(10, 16))->setMinute(rand(0, 59));
                $this->insertOrder(
                    status:    OrderStatusEnum::Pending->value,
                    agent:     null,
                    createdAt: $created,
                    updatedAt: $created,
                );
            }
        }

        // ── E. Failed + Rejected (for GetDeliveryPerformanceQuery) ───────────
        for ($i = 0; $i < 12; $i++) {
            $date    = $now->copy()->subDays(rand(1, 25));
            $updated = $date->copy()->addHours(rand(1, 4));
            $this->insertOrder(
                status:    OrderStatusEnum::Failed->value,
                agent:     $this->agents->random(),
                createdAt: $date,
                updatedAt: $updated,
            );
        }

        for ($i = 0; $i < 6; $i++) {
            $date    = $now->copy()->subDays(rand(1, 25));
            $updated = $date->copy()->addHours(rand(1, 3));
            $this->insertOrder(
                status:    OrderStatusEnum::Rejected->value,
                agent:     $this->agents->random(),
                createdAt: $date,
                updatedAt: $updated,
            );
        }

        // ── F. InDelivery orders (actively assigned right now) ────────────────
        for ($i = 0; $i < 8; $i++) {
            $created = $now->copy()->subHours(rand(1, 6));
            $this->insertOrder(
                status:    OrderStatusEnum::InDelivery->value,
                agent:     $this->agents->random(),
                createdAt: $created,
                updatedAt: $created,
            );
        }

        // ── G. Last month orders (for GetDashboardSummaryQuery month comparison)
        $lastMonthDays = $lastMonthEnd->day;
        for ($i = 0; $i < 40; $i++) {
            $day  = rand(1, $lastMonthDays);
            $date = Carbon::create(
                $lastMonthStart->year,
                $lastMonthStart->month,
                $day,
                rand(8, 18),
                rand(0, 59),
            );

            $isDelivered = ($i % 3 === 0);
            $status      = $isDelivered ? OrderStatusEnum::Delivered->value : OrderStatusEnum::Pending->value;
            $updated     = $isDelivered ? $date->copy()->addHours(rand(3, 8)) : $date;

            $this->insertOrder(
                status:      $status,
                agent:       $isDelivered ? $this->agents->random() : null,
                createdAt:   $date,
                updatedAt:   $updated,
                deliveredAt: $isDelivered ? $updated : null,
            );
        }

        $total = $this->counter - 1;
        $this->command->info("Done. Created {$total} orders with full sub-records.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Core insert helper
    // ─────────────────────────────────────────────────────────────────────────

    private function insertOrder(
        int $status,
        ?DeliveryAgent $agent,
        Carbon $createdAt,
        Carbon $updatedAt,
        ?Carbon $deliveredAt = null,
    ): void {
        $orderId = Str::uuid()->toString();
        $company = $this->companies->random();
        $pad     = str_pad((string) $this->counter, 6, '0', STR_PAD_LEFT);

        // ── orders ───────────────────────────────────────────────────────────
        DB::table('orders')->insert([
            'order_id'            => $orderId,
            'reference_no'        => 'REF-' . $pad,
            'internal_code'       => 'MRS-' . $pad,
            'shipping_company_id' => $company->shipping_company_id,
            'delivery_agent_id'   => $agent?->delivery_agent_id,
            'status'              => $status,
            'assigned_at'         => $agent
                ? $createdAt->copy()->addMinutes(rand(10, 60))->toDateTimeString()
                : null,
            'delivered_at'        => $deliveredAt?->toDateTimeString(),
            'created_at'          => $createdAt->toDateTimeString(),
            'updated_at'          => $updatedAt->toDateTimeString(),
        ]);

        // ── order_customer_info ───────────────────────────────────────────────
        DB::table('order_customer_info')->insert([
            'order_customer_info_id' => Str::uuid()->toString(),
            'order_id'               => $orderId,
            'customer_name'          => $this->sample($this->customerNames),
            'customer_phone'         => '010' . rand(10000000, 99999999),
            'phone_alt'              => rand(0, 1) ? '011' . rand(10000000, 99999999) : null,
            'created_at'             => $createdAt->toDateTimeString(),
            'updated_at'             => $createdAt->toDateTimeString(),
        ]);

        // ── order_addresses ───────────────────────────────────────────────────
        [$govId, $cityId] = $this->randomLocation();
        DB::table('order_addresses')->insert([
            'order_address_id' => Str::uuid()->toString(),
            'order_id'         => $orderId,
            'governorate_id'   => $govId,
            'city_id'          => $cityId,
            'address_line'     => $this->sample($this->streetNames)
                . '، عمارة ' . rand(1, 99)
                . '، شقة ' . rand(1, 20),
            'created_at'       => $createdAt->toDateTimeString(),
            'updated_at'       => $createdAt->toDateTimeString(),
        ]);

        // ── order_financials ──────────────────────────────────────────────────
        $originalAmount    = round(rand(150, 3000) + (rand(0, 99) / 100), 2);
        $commissionRate    = (float) $company->commission_value;
        $commissionAmount  = $company->commission_type == 1
            ? round($originalAmount * ($commissionRate / 100), 2)
            : $commissionRate;
        $collectedAmount   = ($status === OrderStatusEnum::Delivered->value)
            ? $originalAmount
            : null;
        $netDueCompany     = $collectedAmount !== null
            ? round($collectedAmount - $commissionAmount, 2)
            : null;

        DB::table('order_financials')->insert([
            'order_financial_id' => Str::uuid()->toString(),
            'order_id'           => $orderId,
            'original_amount'    => $originalAmount,
            'approved_amount'    => null,
            'collected_amount'   => $collectedAmount,
            'shipping_fee'       => null,
            'commission_amount'  => $commissionAmount,
            'net_due_company'    => $netDueCompany,
            'is_settled'         => 0,
            'created_at'         => $createdAt->toDateTimeString(),
            'updated_at'         => $updatedAt->toDateTimeString(),
        ]);

        // ── order_items ───────────────────────────────────────────────────────
        $qty          = rand(1, 6);
        $deliveredQty = ($status === OrderStatusEnum::Delivered->value) ? $qty : null;
        $returnedQty  = null;

        DB::table('order_items')->insert([
            'order_item_id'       => Str::uuid()->toString(),
            'order_id'            => $orderId,
            'item_description'    => $this->sample($this->itemDescriptions),
            'total_quantity'      => $qty,
            'delivered_quantity'  => $deliveredQty,
            'returned_quantity'   => $returnedQty,
            'created_at'          => $createdAt->toDateTimeString(),
            'updated_at'          => $createdAt->toDateTimeString(),
        ]);

        // ── order_status_history — creation entry ─────────────────────────────
        DB::table('order_status_history')->insert([
            'order_status_history_id' => Str::uuid()->toString(),
            'order_id'                => $orderId,
            'from_status_id'          => null,
            'to_status_id'            => OrderStatusEnum::Pending->value,
            'changed_by'              => null,
            'notes'                   => 'تم إنشاء الطلب',
            'created_at'              => $createdAt->toDateTimeString(),
            'updated_at'              => $createdAt->toDateTimeString(),
        ]);

        // ── order_status_history — transition to current status ───────────────
        if ($status !== OrderStatusEnum::Pending->value) {
            DB::table('order_status_history')->insert([
                'order_status_history_id' => Str::uuid()->toString(),
                'order_id'                => $orderId,
                'from_status_id'          => OrderStatusEnum::Pending->value,
                'to_status_id'            => $status,
                'changed_by'              => null,
                'notes'                   => null,
                'created_at'              => $updatedAt->toDateTimeString(),
                'updated_at'              => $updatedAt->toDateTimeString(),
            ]);
        }

        $this->counter++;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Location helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function loadLocations(): void
    {
        $governorates = Governorate::query()->where('is_active', true)->get();

        foreach ($governorates as $gov) {
            $this->governorateIds[] = $gov->governorate_id;

            $this->cityMap[$gov->governorate_id] = City::query()
                ->where('governorate_id', $gov->governorate_id)
                ->where('is_active', true)
                ->pluck('city_id')
                ->toArray();
        }
    }

    /** @return array{0: string, 1: string|null} [governorate_id, city_id|null] */
    private function randomLocation(): array
    {
        $govId  = $this->governorateIds[array_rand($this->governorateIds)];
        $cities = $this->cityMap[$govId] ?? [];
        $cityId = ! empty($cities) ? $cities[array_rand($cities)] : null;

        return [$govId, $cityId];
    }

    private function sample(array $arr): mixed
    {
        return $arr[array_rand($arr)];
    }
}
