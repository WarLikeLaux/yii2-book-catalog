<?php

declare(strict_types=1);

namespace app\application\books\commands;

use yii\web\UploadedFile;

final readonly class CreateBookCommand
{
    public function __construct(
        public string $title,
        public int $year,
        public string $description,
        public string $isbn,
        public array $authorIds,
        public UploadedFile|string|null $cover = null,
    ) {
    }
}
