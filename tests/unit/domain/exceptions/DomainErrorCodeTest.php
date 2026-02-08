<?php

declare(strict_types=1);

namespace tests\unit\domain\exceptions;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ErrorMapping;
use Codeception\Test\Unit;
use ReflectionEnum;

final class DomainErrorCodeTest extends Unit
{
    public function testAllCasesHaveErrorMappingAttribute(): void
    {
        $reflection = new ReflectionEnum(DomainErrorCode::class);

        foreach ($reflection->getCases() as $case) {
            $attrs = $case->getAttributes(ErrorMapping::class);
            $this->assertNotEmpty(
                $attrs,
                sprintf('Case %s is missing #[ErrorMapping] attribute', $case->getName()),
            );
        }
    }

    public function testAllCasesHaveUniqueValues(): void
    {
        $values = array_map(
            static fn(DomainErrorCode $case) => $case->value,
            DomainErrorCode::cases(),
        );

        $this->assertSame(
            count($values),
            count(array_unique($values)),
        );
    }
}
