<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\widgets;

use app\domain\values\BookStatus;
use app\presentation\books\widgets\BookStatusActions;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Application as BaseApplication;
use yii\web\Application;
use yii\web\Controller;

final class BookStatusActionsTest extends TestCase
{
    private ?BaseApplication $appBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appBackup = Yii::$app instanceof BaseApplication ? Yii::$app : null;

        $app = new Application([
            'id' => 'test-web',
            'basePath' => dirname(__DIR__, 6),
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'test',
                ],
            ],
        ]);

        $app->controller = new Controller('book', $app);
        Yii::$app = $app;
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            Yii::$app = $this->appBackup;
        }
    }

    public function testDraftShowsPublishButton(): void
    {
        $html = BookStatusActions::widget(['bookId' => 1, 'status' => BookStatus::Draft->value]);

        $this->assertStringContainsString('btn-success', $html);
        $this->assertStringContainsString('data-method="post"', $html);
        $this->assertStringContainsString('book%2Fpublish', $html);
    }

    public function testPublishedShowsUnpublishAndArchiveButtons(): void
    {
        $html = BookStatusActions::widget(['bookId' => 1, 'status' => BookStatus::Published->value]);

        $this->assertStringContainsString('btn-warning', $html);
        $this->assertStringContainsString('btn-secondary', $html);
        $this->assertStringContainsString('book%2Funpublish', $html);
        $this->assertStringContainsString('book%2Farchive', $html);
    }

    public function testArchivedShowsRestoreButton(): void
    {
        $html = BookStatusActions::widget(['bookId' => 1, 'status' => BookStatus::Archived->value]);

        $this->assertStringContainsString('btn-info', $html);
        $this->assertStringContainsString('book%2Frestore', $html);
    }

    public function testUnknownStatusRendersNoButtons(): void
    {
        $html = BookStatusActions::widget(['bookId' => 1, 'status' => 'unknown']);

        $this->assertSame('', $html);
    }
}
