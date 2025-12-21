<?php

declare(strict_types=1);

namespace app\services\storage;

use app\interfaces\FileStorageInterface;
use RuntimeException;
use Yii;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

final class LocalFileStorage implements FileStorageInterface
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $baseUrl
    ) {
    }

    public function save(UploadedFile $file): string
    {
        $dir = Yii::getAlias($this->basePath);
        FileHelper::createDirectory($dir);

        $filename = uniqid('', true) . '.' . $file->extension;
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!$file->saveAs($path)) {
            throw new RuntimeException('File save failed');
        }

        return $this->baseUrl . '/' . $filename;
    }
}
