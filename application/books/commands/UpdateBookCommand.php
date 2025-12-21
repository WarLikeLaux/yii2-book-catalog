<?php

declare(strict_types=1);

namespace app\application\books\commands;

use yii\web\UploadedFile;

final readonly class UpdateBookCommand
{
    public function __construct(
        public int $id,
        public string $title,
        public int $year,
        public string $description,
        public string $isbn,
        public array $authorIds,
        public UploadedFile|string|null $cover = null,
    ) {
    }
}
