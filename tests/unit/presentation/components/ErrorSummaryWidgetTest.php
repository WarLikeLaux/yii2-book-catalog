<?php

declare(strict_types=1);

namespace tests\unit\presentation\components;

use app\presentation\components\ErrorSummaryWidget;
use PHPUnit\Framework\TestCase;
use Stringable;
use Yii;
use yii\base\Application as BaseApplication;
use yii\base\Model;
use yii\web\Application;

final class ErrorSummaryWidgetTest extends TestCase
{
    private ?BaseApplication $appBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appBackup = Yii::$app instanceof BaseApplication ? Yii::$app : null;

        Yii::$app = new Application([
            'id' => 'test-web',
            'basePath' => dirname(__DIR__, 5),
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

    public function testRunRendersHeaderFooterAndErrors(): void
    {
        $model1 = $this->createModelWithError('Error 1');
        $model2 = $this->createModelWithError('Error 2');

        $widget = new ErrorSummaryWidget([
            'models' => [$model1, $model2],
            'options' => [
                'header' => '<h1>Header</h1>',
                'footer' => '<p>Footer</p>',
            ],
        ]);

        $html = $widget->run();

        $this->assertStringContainsString('<h1>Header</h1>', $html);
        $this->assertStringContainsString('<p>Footer</p>', $html);
        $this->assertStringContainsString('Error 1', $html);
        $this->assertStringContainsString('Error 2', $html);
    }

    public function testRunUsesStringableHeaderFooterAndHidesWhenEmpty(): void
    {
        $model = new class () extends Model {
        };

        $widget = new ErrorSummaryWidget([
            'models' => $model,
            'options' => [
                'header' => new class () implements Stringable {
                    public function __toString(): string
                    {
                        return '<h2>Header</h2>';
                    }
                },
                'footer' => new class () implements Stringable {
                    public function __toString(): string
                    {
                        return '<p>Footer</p>';
                    }
                },
                'style' => 'color:red;',
            ],
        ]);

        $html = $widget->run();

        $this->assertStringContainsString('<h2>Header</h2>', $html);
        $this->assertStringContainsString('<p>Footer</p>', $html);
        $this->assertStringContainsString('style="color:red; display:none"', $html);
    }

    public function testRunUsesDefaultHeaderAndEmptyFooterOnInvalidOptions(): void
    {
        $model = $this->createModelWithError('Error');

        $widget = new ErrorSummaryWidget([
            'models' => $model,
            'options' => [
                'header' => 123,
                'footer' => 123,
            ],
        ]);

        $html = $widget->run();

        $expectedHeader = '<p>' . Yii::t('yii', 'Please fix the following errors:') . '</p>';

        $this->assertStringContainsString($expectedHeader, $html);
    }

    private function createModelWithError(string $message): Model
    {
        $model = new class () extends Model {
        };

        $model->addError('field', $message);

        return $model;
    }
}
