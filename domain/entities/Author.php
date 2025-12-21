<?php

declare(strict_types=1);

namespace app\domain\entities;

use app\domain\valueObjects\Fio;

final class Author
{
    private function __construct(
        private Fio $fio,
    ) {
    }

    public static function create(Fio $fio): self
    {
        return new self(fio: $fio);
    }

    public function edit(Fio $fio): void
    {
        $this->fio = $fio;
    }

    public function getFio(): Fio
    {
        return $this->fio;
    }
}
