<?php

namespace App\Modules\Orders\Domain\Services;

use App\Modules\Orders\Domain\Enums\ImportStatusHintEnum;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;

final class OrderStatusExportLabelService
{
    /**
     * Arabic label compatible with the import sheet (empty for pending).
     */
    public function labelFor(OrderStatusEnum $status): string
    {
        if ($status === OrderStatusEnum::Pending) {
            return '';
        }

        foreach (ImportStatusHintEnum::cases() as $hint) {
            if ($hint->toStatusId() === $status->value) {
                return $hint->value;
            }
        }

        return $status->labelAr();
    }
}
