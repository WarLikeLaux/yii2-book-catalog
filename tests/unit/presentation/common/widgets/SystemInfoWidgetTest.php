<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\widgets;

use app\application\common\dto\SystemInfoDto;
use app\application\ports\SystemInfoProviderInterface;
use app\presentation\common\widgets\SystemInfoWidget;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\di\Container;

final class SystemInfoWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$container = new Container();
    }

    public function testRunAsMysql(): void
    {
        $provider = $this->createMock(SystemInfoProviderInterface::class);
        $provider->method('getInfo')->willReturn(new SystemInfoDto(
            '8.2.0',
            '2.0.48',
            'MYSQL',
            '8.0.32',
        ));

        Yii::$container->set(SystemInfoProviderInterface::class, $provider);

        $widget = new SystemInfoWidget();
        $html = $widget->run();

        $this->assertStringContainsString('PHP:', $html);
        $this->assertStringContainsString('8.2.0', $html);
        $this->assertStringContainsString('Yii2:', $html);
        $this->assertStringContainsString('2.0.48', $html);
        $this->assertStringContainsString('MYSQL:', $html);
        $this->assertStringContainsString('8.0.32', $html);
        $this->assertStringContainsString('https://www.mysql.com/', $html);
    }

    public function testRunAsPostgres(): void
    {
        $provider = $this->createMock(SystemInfoProviderInterface::class);
        $provider->method('getInfo')->willReturn(new SystemInfoDto(
            '8.2.0',
            '2.0.48',
            'PGSQL',
            '15.2',
        ));

        Yii::$container->set(SystemInfoProviderInterface::class, $provider);

        $widget = new SystemInfoWidget();
        $html = $widget->run();

        $this->assertStringContainsString('PGSQL:', $html);
        $this->assertStringContainsString('15.2', $html);
        $this->assertStringContainsString('https://www.postgresql.org/', $html);
    }
}
