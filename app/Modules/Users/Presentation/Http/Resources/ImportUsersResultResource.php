<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Users\Application\DTOs\ImportUsersResultDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportUsersResultResource extends JsonResource
{
    /**
     * @param  ImportUsersResultDTO  $resource
     */
    public function toArray(Request $request): array
    {
        /** @var ImportUsersResultDTO $result */
        $result = $this->resource;

        return [
            'summary' => [
                'total_rows' => $result->totalRows,
                'imported_count' => $result->importedCount,
                'failed_count' => $result->failedCount,
            ],
            'imported' => $result->imported,
            'errors' => $result->errors,
        ];
    }
}
