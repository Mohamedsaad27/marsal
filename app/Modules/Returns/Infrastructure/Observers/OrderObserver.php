<?php

namespace App\Modules\Returns\Infrastructure\Observers;

use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Returns\Application\Services\ReturnRecordSynchronizer;

class OrderObserver
{
    public function __construct(
        private ReturnRecordSynchronizer $returns,
    ) {}

    public function saved(Order $order): void
    {
        $this->returns->sync($order);
    }
}
