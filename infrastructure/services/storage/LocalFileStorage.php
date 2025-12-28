<?php

declare(strict_types=1);

namespace app\infrastructure\services\storage;

use app\application\ports\FileStorageInterface;
use RuntimeException;
use Yii;
use yii\helpers\FileHelper;

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
    public function save(string $tempPath, string $extension): string
    {
        $dir = Yii::getAlias($this->basePath);
        FileHelper::createDirectory($dir);

        $filename = uniqid('', true) . '.' . $extension;
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        // Используем move_uploaded_file для безопасности, если это HTTP загрузка,
        // или rename/copy если это локальное тестирование.
        // Yii2 UploadedFile::saveAs использует copy внутри, если is_uploaded_file возвращает false (для тестов)
        // Для простоты и совместимости с тестами будем использовать copy/move логику.

        if (is_uploaded_file($tempPath)) {
            $result = move_uploaded_file($tempPath, $path);
        } else {
            // Фолбек для тестов или консольных команд
            $result = copy($tempPath, $path);
        }

        if (!$result) {
            throw new RuntimeException('File save failed');
        }

        return $this->baseUrl . '/' . $filename;
    }
}
