<?php

declare(strict_types=1);

namespace app\infrastructure\services\sms;

use app\application\ports\SmsSenderInterface;
use Psr\Log\LoggerInterface;

final readonly class LogSmsSender implements SmsSenderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function send(string $phone, string $message): bool
    {
        $this->logger->info('SMS sent (logged)', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return true;
    }
}
