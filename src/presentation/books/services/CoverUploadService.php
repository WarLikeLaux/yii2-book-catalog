<?php

declare(strict_types=1);

namespace app\presentation\books\services;

use app\application\common\services\UploadedFileStorage;
use app\presentation\common\adapters\UploadedFileAdapter;
use yii\web\UploadedFile;

final readonly class CoverUploadService
{
    public function __construct(
        private UploadedFileStorage $storage,
        private UploadedFileAdapter $adapter,
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $payload = $this->adapter->toPayload($file);
        return $this->storage->store($payload);
    }
}
