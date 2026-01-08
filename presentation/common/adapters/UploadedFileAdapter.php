<?php

declare(strict_types=1);

namespace app\presentation\common\adapters;

use app\domain\values\FileContent;
use RuntimeException;
use yii\web\UploadedFile;

final readonly class UploadedFileAdapter
{
    /**
     * Convert a Yii UploadedFile into a FileContent domain object.
     *
     * @param UploadedFile $uploadedFile The uploaded file to convert.
     * @return FileContent A FileContent instance created from the uploaded file's temporary path and extension.
     * @throws RuntimeException If the uploaded file's temporary path does not exist or is not readable.
     */
    public function toFileContent(UploadedFile $uploadedFile): FileContent
    {
        $path = $uploadedFile->tempName;

        if (!file_exists($path) || !is_readable($path)) {
            throw new RuntimeException('Temporary file is not accessible: ' . $path); // @codeCoverageIgnore
        }

        return FileContent::fromPath($path, $uploadedFile->getExtension());
    }
}