<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Orders\Domain\Enums\ImportStatusHintEnum;

$tests = [
    'تم التوصيل'           => 5,
    'تم التوصيل بتغيير سعر' => 6,
    'تسليم جزئي'           => 7,
    'رفض + دفع الشحن'      => 8,
    'رفض وعدم دفع الشحن'   => 9,
    'ألغى العميل'           => 10,
    'لا يوجد رد'            => 11,
    'الهاتف مغلق'           => 12,
    'تهرّب / مختفي'         => 13,
    'منطقة غير آمنة'        => 14,
    'مؤجل'                  => 15,
    'خارج المحافظة'         => 16,
    'رقم هاتف خاطئ'         => 17,
    'قيد التوصيل'           => 3,
    'معيّن لمندوب'          => 2,
    'نص غير معروف'          => null,  // should return null
    ''                      => null,  // empty → null
];

$pass = 0; $fail = 0;
foreach ($tests as $arabic => $expectedId) {
    $hint     = ImportStatusHintEnum::fromArabic($arabic);
    $resolved = $hint?->toStatusId() ?? null;

    if ($resolved === $expectedId) {
        echo "  ✓  [{$arabic}] → " . ($resolved ?? 'null (pending)') . PHP_EOL;
        $pass++;
    } else {
        echo "  ✗  [{$arabic}] expected={$expectedId} got=" . ($resolved ?? 'null') . PHP_EOL;
        $fail++;
    }
}

echo PHP_EOL . "Passed: {$pass}  Failed: {$fail}" . PHP_EOL;
