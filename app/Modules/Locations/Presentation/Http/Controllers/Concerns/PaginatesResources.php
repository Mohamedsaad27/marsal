<?php

namespace App\Modules\Locations\Presentation\Http\Controllers\Concerns;

use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait PaginatesResources
{
    protected function paginatedSuccess(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        /** @var JsonResource $resourceClass */
        return $this->success(array_merge(
            ['items' => $resourceClass::collection($paginator->items())],
            PaginationMeta::getMeta($paginator),
        ));
    }
}
