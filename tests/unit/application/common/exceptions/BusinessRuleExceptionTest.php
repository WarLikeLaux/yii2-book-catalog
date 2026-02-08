<?php

declare(strict_types=1);

namespace tests\unit\application\common\exceptions;

use app\application\common\exceptions\BusinessRuleException;
use Codeception\Test\Unit;

final class BusinessRuleExceptionTest extends Unit
{
    public function testDefaultValues(): void
    {
        $exception = new BusinessRuleException('error.business_rule');

        $this->assertSame('error.business_rule', $exception->getMessage());
        $this->assertSame('error.business_rule', $exception->errorCode);
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getField());
        $this->assertNull($exception->getPrevious());
    }

    public function testCustomValues(): void
    {
        $previous = new \RuntimeException('logic error');
        $exception = new BusinessRuleException('book.error.publish_without_authors', 'authors', 422, $previous);

        $this->assertSame('book.error.publish_without_authors', $exception->getMessage());
        $this->assertSame('book.error.publish_without_authors', $exception->errorCode);
        $this->assertSame(422, $exception->getCode());
        $this->assertSame('authors', $exception->getField());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
