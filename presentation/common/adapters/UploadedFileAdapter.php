<?php

declare(strict_types=1);

namespace app\presentation\common\adapters;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\OperationFailedException;
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
        $path = $uploadedFile->tempName;

        if (!file_exists($path) || !is_readable($path)) {
            throw new OperationFailedException(DomainErrorCode::FileOpenFailed); // @codeCoverageIgnore
        }

        return FileContent::fromPath(
            $path,
            $uploadedFile->getExtension(),
            $this->mimeTypeDetector,
        );
    }
}
