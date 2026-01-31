<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\mappers;

use app\presentation\common\mappers\AutoMapperContextBuilder;
use Codeception\Test\Unit;

final class AutoMapperContextBuilderTest extends Unit
{
    public function testBuildCreatesConstructorArguments(): void
    {
        $builder = new AutoMapperContextBuilder();

        $context = $builder->build([
            'Some\\Class' => [
                'id' => 10,
                'cover' => 'path',
            ],
        ]);

        $this->assertSame(10, $context['constructor_arguments']['Some\\Class']['id']);
        $this->assertSame('path', $context['constructor_arguments']['Some\\Class']['cover']);
    }
}
