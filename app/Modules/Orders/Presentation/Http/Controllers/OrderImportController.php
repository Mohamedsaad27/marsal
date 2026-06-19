<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Orders\Infrastructure\Jobs\ProcessOrdersImportJob;
use App\Modules\Orders\Presentation\Http\Requests\ImportOrdersRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderImportController extends Controller
{
    use ApiResponseTrait;
    public function store(ImportOrdersRequest $request): \Illuminate\Http\JsonResponse
    {
        $file     = $request->file('file');
        $batchId  = (string) Str::uuid();
        $filePath = $file->storeAs('imports/orders', "{$batchId}.xlsx", 'local');

        ProcessOrdersImportJob::dispatch(
            filePath:   Storage::disk('local')->path($filePath),
            batchId:    $batchId,
            uploadedBy: auth()->id(),
        )->onQueue('reports');

        return $this->success(
            data:    ['batch_id' => $batchId],
            message: 'جاري معالجة ملف الاستيراد في الخلفية',
            statusCode:  202,
        );
    }
}
