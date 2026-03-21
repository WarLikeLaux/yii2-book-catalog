<?php

declare(strict_types=1);

namespace tests\unit\application\common\dto;

use app\application\common\dto\IdempotencyResponseDto;
use PHPUnit\Framework\TestCase;

final class IdempotencyResponseDtoTest extends TestCase
{
    public function testConstructAssignsValues(): void
    {
        $dto = new IdempotencyResponseDto(200, ['ok' => true], '/redirect');

        $this->assertSame(200, $dto->statusCode);
        $this->assertSame(['ok' => true], $dto->data);
        $this->assertSame('/redirect', $dto->redirectUrl);
    }
}
