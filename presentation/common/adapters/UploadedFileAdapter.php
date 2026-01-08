<?php

declare(strict_types=1);

namespace app\presentation\common\adapters;

use app\domain\values\FileContent;
use yii\web\UploadedFile;

final readonly class UploadedFileAdapter
{
    public function toFileContent(UploadedFile $uploadedFile): FileContent
    {
        return FileContent::fromPath($uploadedFile->tempName, $uploadedFile->getExtension());
    }
}
