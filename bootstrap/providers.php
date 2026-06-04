<?php

use App\Modules\Core\Infrastructure\Providers\CoreServiceProvider;
use App\Providers\AppServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    ExcelServiceProvider::class,
];
