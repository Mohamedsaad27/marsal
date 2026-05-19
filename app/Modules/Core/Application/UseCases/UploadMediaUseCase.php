<?php

namespace App\Modules\Core\Application\UseCases;

use App\Modules\Core\Application\DTOs\UploadMediaDTO;
use App\Modules\Core\Domain\Interfaces\HasMediaFiles;
use App\Modules\Core\Infrastructure\Persistence\Models\MediaFile;

class UploadMediaUseCase
{
    public function execute(HasMediaFiles $owner, UploadMediaDTO $dto): MediaFile
    {
        return $owner->addMedia($dto->file, $dto->collection, $dto->disk);
    }
}
