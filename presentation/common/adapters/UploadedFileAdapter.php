<?php

declare(strict_types=1);

namespace app\presentation\common\adapters;

use app\domain\exceptions\OperationFailedException;
use app\domain\exceptions\ValidationException;
use app\domain\services\MimeTypeDetectorInterface;
use app\domain\values\FileContent;
use yii\web\UploadedFile;

final readonly class UploadedFileAdapter
{
    public function __construct(
        private MimeTypeDetectorInterface $mimeTypeDetector,
    ) {
    }

    public function toFileContent(UploadedFile $uploadedFile): FileContent
    {
        try {
            $extension = $uploadedFile->getExtension() !== '' ? $uploadedFile->getExtension() : null;

            return FileContent::fromPath(
                $uploadedFile->tempName,
                $extension,
                $this->mimeTypeDetector,
            );
        } catch (ValidationException $exception) {
            throw new OperationFailedException($exception->errorCode, $exception->getCode(), $exception);
        }
    }
}
