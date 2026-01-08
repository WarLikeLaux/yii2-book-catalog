<?php

declare(strict_types=1);

namespace app\presentation\common\adapters;

use app\domain\values\FileContent;
use RuntimeException;
use yii\web\UploadedFile;

final readonly class UploadedFileAdapter
{
    public function toFileContent(UploadedFile $uploadedFile): FileContent
    {
        $path = $uploadedFile->tempName;

        if (!file_exists($path) || !is_readable($path)) {
            throw new RuntimeException('Temporary file is not accessible: ' . $path); // @codeCoverageIgnore
        }

        return FileContent::fromPath($path, $uploadedFile->getExtension());
    }
}
