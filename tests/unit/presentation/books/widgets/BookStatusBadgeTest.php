<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\widgets;

use app\presentation\books\widgets\BookStatusBadge;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Application as BaseApplication;
use yii\web\Application;

final class BookStatusBadgeTest extends TestCase
{
    private ?BaseApplication $appBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appBackup = Yii::$app instanceof BaseApplication ? Yii::$app : null;

        Yii::$app = new Application([
            'id' => 'test-web',
            'basePath' => dirname(__DIR__, 6),
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'test',
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            Yii::$app = $this->appBackup;
        }
    }

    public function testPublishedBadgeHasSuccessClass(): void
    {
        $html = BookStatusBadge::widget(['status' => 'published']);

        $this->assertStringContainsString('badge bg-success', $html);
        $this->assertStringContainsString('<span', $html);
    }

    public function testDraftBadgeHasSecondaryClass(): void
    {
        $html = BookStatusBadge::widget(['status' => 'draft']);

        $this->assertStringContainsString('badge bg-secondary', $html);
    }

    public function testArchivedBadgeHasDarkClass(): void
    {
        $html = BookStatusBadge::widget(['status' => 'archived']);

        $this->assertStringContainsString('badge bg-dark', $html);
    }

    public function testUnknownStatusFallsBackToSecondary(): void
    {
        $html = BookStatusBadge::widget(['status' => 'unknown']);

        $this->assertStringContainsString('badge bg-secondary', $html);
    }
}
