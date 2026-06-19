<?php

namespace App\Modules\Orders\Infrastructure\Jobs;

use App\Modules\Orders\Application\UseCases\ImportOrdersFromExcelUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrdersImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function __construct(
        private string $filePath,    // absolute path to the stored file
        private string $batchId,     // UUID returned to client in 202
        private string $uploadedBy,  // user_id of admin who triggered the import
    ) {
        $this->onQueue('reports');
    }

    public function handle(ImportOrdersFromExcelUseCase $useCase): void
    {
        try {
            $results = $useCase->execute($this->filePath, $this->batchId);

            Log::channel('daily')->info('OrderImport complete', [
                'batch_id'           => $this->batchId,
                'uploaded_by'        => $this->uploadedBy,
                'imported'           => $results['imported'],
                'skipped'            => $results['skipped'],
                'created_companies'  => $results['created_companies'],
                'errors'             => $results['errors'],
            ]);

        } catch (\Throwable $e) {
            Log::channel('daily')->error('OrderImport failed', [
                'batch_id' => $this->batchId,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
