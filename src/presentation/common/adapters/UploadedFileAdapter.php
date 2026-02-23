<?php

declare(strict_types=1);

namespace app\presentation\common\adapters;

use app\application\common\dto\UploadedFilePayload;
use app\application\ports\MimeTypeDetectorInterface;
use yii\web\UploadedFile;

final readonly class UploadedFileAdapter
{
    public function __construct(
        private MimeTypeDetectorInterface $mimeTypeDetector,
    ) {
    }

    public function toPayload(UploadedFile $uploadedFile): UploadedFilePayload
    {
        $extension = $uploadedFile->getExtension();
        $extension = $extension !== '' ? $extension : pathinfo($uploadedFile->name, PATHINFO_EXTENSION);

        return new UploadedFilePayload(
            $uploadedFile->tempName,
            $extension,
            $this->mimeTypeDetector->detect($uploadedFile->tempName),
        );
    }
}
