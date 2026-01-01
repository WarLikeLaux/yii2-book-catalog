<?php

declare(strict_types=1);

namespace app\infrastructure\services\storage;

use app\application\common\dto\TemporaryFile;
use app\application\ports\FileStorageInterface;
use RuntimeException;
use Yii;
use yii\helpers\FileHelper;

final readonly class LocalFileStorage implements FileStorageInterface
{
    public function __construct(
        private string $basePath,
        private string $baseUrl,
        private string $tempBasePath = '@app/runtime/tmp_uploads'
    ) {
    }

    public function saveTemporary(string $tempPath, string $extension): TemporaryFile
    {
        $dir = Yii::getAlias($this->tempBasePath);
        FileHelper::createDirectory($dir);

        $filename = uniqid('', true) . '.' . $extension;
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        $result = is_uploaded_file($tempPath) ? move_uploaded_file($tempPath, $path) : copy($tempPath, $path);

        if (!$result) {
            throw new RuntimeException('File save failed'); // @codeCoverageIgnore
        }

        return new TemporaryFile($path, $this->baseUrl . '/' . $filename);
    }

    public function moveToPermanent(TemporaryFile $file): void
    {
        $dir = Yii::getAlias($this->basePath);
        FileHelper::createDirectory($dir);

        $filename = basename($file->url);
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!rename($file->tempPath, $path)) {
            throw new RuntimeException('File move failed'); // @codeCoverageIgnore
        }
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
        $path = Yii::getAlias($this->basePath) . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($path)) {
            return;
        }

        unlink($path);
    }
}
