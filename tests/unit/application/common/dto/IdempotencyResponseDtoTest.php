<?php

declare(strict_types=1);

namespace app\tests\unit\application\common\dto;

use app\application\common\dto\IdempotencyResponseDto;
use Codeception\Test\Unit;

final class IdempotencyResponseDtoTest extends Unit
{
    public function testConstructAssignsValues(): void
    {
        $dto = new IdempotencyResponseDto(200, ['ok' => true], '/redirect');

        $this->assertSame(200, $dto->statusCode);
        $this->assertSame(['ok' => true], $dto->data);
        $this->assertSame('/redirect', $dto->redirectUrl);
    }
}
