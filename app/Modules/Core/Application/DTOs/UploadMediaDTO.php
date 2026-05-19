<?php

namespace App\Modules\Core\Application\DTOs;

use Illuminate\Http\UploadedFile;

readonly class UploadMediaDTO
{
    public function __construct(
        public UploadedFile $file,
        public ?string $collection = 'default',
        public ?string $disk = null,
    ) {}
}
