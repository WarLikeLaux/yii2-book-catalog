<?php

declare(strict_types=1);

namespace app\application\ports;

use yii\web\UploadedFile;

interface FileStorageInterface
{
    public function save(UploadedFile $file): string;
}
