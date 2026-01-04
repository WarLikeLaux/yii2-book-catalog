<?php

declare(strict_types=1);

namespace app\infrastructure\services\storage;

use app\application\common\dto\TemporaryFile;
use app\application\ports\FileStorageInterface;
use app\domain\values\StoredFileReference;
use RuntimeException;
use Yii;
use yii\helpers\FileHelper;

final readonly class LocalFileStorage implements FileStorageInterface
{
    public function __construct(
        private StorageConfig $config
    ) {
    }

    public function saveTemporary(string $tempPath, string $extension): TemporaryFile
    {
        $dir = Yii::getAlias($this->config->tempBasePath);
        FileHelper::createDirectory($dir);

        $filename = uniqid('', true) . '.' . $extension;
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!copy($tempPath, $path)) {
             throw new RuntimeException('Failed to save temporary file'); // @codeCoverageIgnore
        }

        return new TemporaryFile($path, $filename);
    }

    public function moveToPermanent(TemporaryFile $file): StoredFileReference
    {
        $dir = Yii::getAlias($this->config->basePath);
        FileHelper::createDirectory($dir);

        $filename = $file->filename;
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!rename($file->tempPath, $path)) {
            throw new RuntimeException('File move failed'); // @codeCoverageIgnore
        }

        return new StoredFileReference($filename);
    }

    public function deleteTemporary(TemporaryFile $file): void
    {
        if (!file_exists($file->tempPath)) {
            return;
        }

        unlink($file->tempPath);
    }

    public function delete(string $url): void
    {
        $filename = basename($url);
        $path = Yii::getAlias($this->config->basePath) . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($path)) {
            return;
        }

        unlink($path);
    }
}
