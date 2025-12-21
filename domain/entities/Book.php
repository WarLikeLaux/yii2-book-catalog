<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\valueObjects\Isbn;
use app\domain\valueObjects\Year;

final class Book
{
    private function __construct(
        private string $title,
        private Year $year,
        private Isbn $isbn,
        private ?string $description,
        private ?string $coverUrl,
    ) {}

    public static function create(
        string $title,
        Year $year,
        Isbn $isbn,
        ?string $description,
        ?string $coverUrl
    ): self {
        return new self(
            title: $title,
            year: $year,
            isbn: $isbn,
            description: $description,
            coverUrl: $coverUrl
        );
    }

    public function edit(
        string $title,
        Year $year,
        Isbn $isbn,
        ?string $description,
        ?string $coverUrl
    ): void {
        $this->title = $title;
        $this->year = $year;
        $this->isbn = $isbn;
        $this->description = $description;
        if ($coverUrl === null) {
            return;
        }

        $this->coverUrl = $coverUrl;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getYear(): Year
    {
        return $this->year;
    }

    public function getIsbn(): Isbn
    {
        return $this->isbn;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }
}
