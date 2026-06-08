<?php

namespace App\Modules\Dashboard\Domain\Helpers;

final class PercentageChange
{
    public static function calculate(float|int $current, float|int $previous, int $precision = 1): ?float
    {
        if ((float) $previous === 0.0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, $precision);
    }
}
