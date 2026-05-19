<?php

namespace App\Modules\Core\Application\UseCases;

use App\Modules\Core\Domain\Interfaces\HasMediaFiles;
use App\Modules\Core\Domain\Interfaces\MediaRepositoryInterface;
use App\Modules\Core\Infrastructure\Persistence\Models\MediaFile;
use App\Modules\Core\Infrastructure\Services\MediaStorageService;
class DeleteMediaUseCase
{
    public function __construct(
        private readonly MediaRepositoryInterface $mediaRepository,
        private readonly MediaStorageService $mediaStorage,
    ) {}

    public function execute(HasMediaFiles $owner, MediaFile $mediaFile): void
    {
        $owner->deleteMedia($mediaFile);
    }

    public function executeById(string $mediaFileId): void
    {
        $mediaFile = $this->mediaRepository->findById($mediaFileId);

        if ($mediaFile === null) {
            return;
        }

        $this->mediaStorage->delete(
            $mediaFile->{config('core.media.disk_column')},
            $mediaFile->{config('core.media.path_column')}
        );

        $this->mediaRepository->delete($mediaFile);
    }
}
