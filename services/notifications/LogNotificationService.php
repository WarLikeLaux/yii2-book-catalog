<?php

declare(strict_types=1);

namespace app\services\notifications;

use app\interfaces\NotificationInterface;
use Psr\Log\LoggerInterface;

final class LogNotificationService implements NotificationInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function success(string $message): void
    {
        $this->logger->info('Notification: ' . $message, ['type' => 'success']);
    }

    public function error(string $message): void
    {
        $this->logger->error('Notification: ' . $message, ['type' => 'error']);
    }

    public function info(string $message): void
    {
        $this->logger->info('Notification: ' . $message, ['type' => 'info']);
    }

    public function warning(string $message): void
    {
        $this->logger->warning('Notification: ' . $message, ['type' => 'warning']);
    }
}
