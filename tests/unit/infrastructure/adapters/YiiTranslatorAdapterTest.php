<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\infrastructure\adapters\YiiTranslatorAdapter;
use PHPUnit\Framework\TestCase;

final class YiiTranslatorAdapterTest extends TestCase
{
    private YiiTranslatorAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new YiiTranslatorAdapter();
    }

    public function testTranslateUsingYii(): void
    {
        $category = 'app';
        $message = 'Hello {name}';
        $params = ['name' => 'World'];

        $result = $this->adapter->translate($category, $message, $params);

        $this->assertStringContainsString('World', $result);
    }
}
