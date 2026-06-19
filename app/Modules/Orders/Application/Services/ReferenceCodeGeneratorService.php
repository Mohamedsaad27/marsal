<?php

namespace App\Modules\Orders\Application\Services;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Generates a unique order reference_code.
 *
 * Format: {COMPANY_PREFIX}-{D}-{M}-{YYYY}({EXCEL_ORDER_CODE})
 * Example: EN-19-6-2026(17)
 *
 * If the base code already exists an incremental suffix is appended:
 * EN-19-6-2026(17)-1, EN-19-6-2026(17)-2, …
 */
class ReferenceCodeGeneratorService
{
    public function generate(
        string $companyName,
        string $excelOrderCode,
        DateTimeInterface $date,
    ): string {
        $prefix   = $this->extractPrefix($companyName);
        $datePart = $date->format('j-n-Y');
        $codePart = $excelOrderCode !== '' ? "({$excelOrderCode})" : '';

        $base = "{$prefix}-{$datePart}{$codePart}";

        return $this->ensureUnique($base);
    }

    private function extractPrefix(string $companyName): string
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $companyName);

        if ($letters === null || $letters === '') {
            return 'XX';
        }

        $prefix = mb_strtoupper(mb_substr($letters, 0, 2, 'UTF-8'), 'UTF-8');

        if (mb_strlen($prefix, 'UTF-8') < 2) {
            $prefix .= 'X';
        }

        return $prefix;
    }

    private function ensureUnique(string $base): string
    {
        if (! DB::table('orders')->where('reference_code', $base)->exists()) {
            return $base;
        }

        $suffix = 1;

        do {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        } while (DB::table('orders')->where('reference_code', $candidate)->exists());

        return $candidate;
    }
}
