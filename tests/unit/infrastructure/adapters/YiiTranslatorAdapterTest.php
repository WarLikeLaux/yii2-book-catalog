<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\infrastructure\adapters\YiiTranslatorAdapter;
use Codeception\Test\Unit;

final class YiiTranslatorAdapterTest extends Unit
{
    private YiiTranslatorAdapter $adapter;

    protected function _before(): void
    {
        $this->adapter = new YiiTranslatorAdapter();
    }

    public function testTranslateUsingYii(): void
    {
        // Simple integration test since we can rely on Yii::t mock/implementation behavior
        // In unit tests usually Yii::$app is not fully bootstrapped for translations,
        // but let's see if we can check basic behavior or need to mock Yii.
        // Assuming Yii::t returns category/message if not found or simple replace.

        // However, Yii::t is a static method.
        // If we want to strictly unit test this adapter WITHOUT relying on real Yii::t behavior,
        // we might be limited. But since this IS an adapter to Yii, simpler is better.
        // Let's assume standard behavior: returns message if no translation.

        $category = 'app';
        $message = 'Hello {name}';
        $params = ['name' => 'World'];

        // This relies on whatever Yii::t does in the test environment.
        // Usually unit suite has Yii2 module enabled, so Yii class is available.
        $result = $this->adapter->translate($category, $message, $params);

        // By default without translation source, Yii::t returns the message heavily processed or just message.
        // If we want to verify it actually CALLED Yii::t, we can't easily spy on static method without AspectMock.
        // But we can check output.

        // Let's try to see if parameter replacement works, which is Yii::t feature.
        // If parameter replacement works, then Yii::t was called.
        $this->assertStringContainsString('World', $result);
    }
}
