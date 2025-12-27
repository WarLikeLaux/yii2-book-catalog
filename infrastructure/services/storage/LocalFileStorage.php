<?php

declare(strict_types=1);

namespace app\infrastructure\services\storage;

use app\application\ports\FileStorageInterface;
use RuntimeException;
use Yii;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

final readonly class LocalFileStorage implements FileStorageInterface
{
    public function __construct(
        private string $basePath,
        private string $baseUrl
    ) {
    }

    /**
     * @codeCoverageIgnore Зависит от Yii::getAlias и файловой системы
     */
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
