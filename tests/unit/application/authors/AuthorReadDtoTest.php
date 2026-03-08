<?php

declare(strict_types=1);

namespace tests\unit\application\authors;

use app\application\authors\queries\AuthorReadDto;
use PHPUnit\Framework\TestCase;

final class AuthorReadDtoTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $dto = new AuthorReadDto(id: 42, fio: 'Иванов Иван');

        $result = $dto->jsonSerialize();

        $this->assertSame(['id' => 42, 'fio' => 'Иванов Иван'], $result);
    }
}
