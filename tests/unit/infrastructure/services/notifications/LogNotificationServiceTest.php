<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\notifications;

use app\infrastructure\services\notifications\LogNotificationService;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;

final class LogNotificationServiceTest extends Unit
{
    private const NOTIFICATION_PREFIX = 'Notification: ';
    private LoggerInterface $logger;
    private LogNotificationService $service;

    protected function _before(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new LogNotificationService($this->logger);
    }

    public function testSuccessLog(): void
    {
        $message = 'Operation successful';

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                self::NOTIFICATION_PREFIX . $message,
                ['type' => 'success'],
            );

        $this->service->success($message);
    }

    public function testErrorLog(): void
    {
        $message = 'Operation failed';

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                self::NOTIFICATION_PREFIX . $message,
                ['type' => 'error'],
            );

        $this->service->error($message);
    }

    public function testInfoLog(): void
    {
        $message = 'Just info';

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                self::NOTIFICATION_PREFIX . $message,
                ['type' => 'info'],
            );

        $this->service->info($message);
    }

    public function testWarningLog(): void
    {
        $message = 'Warning message';

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                self::NOTIFICATION_PREFIX . $message,
                ['type' => 'warning'],
            );

        $this->service->warning($message);
    }
}
