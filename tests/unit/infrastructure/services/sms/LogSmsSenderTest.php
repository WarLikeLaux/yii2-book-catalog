<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\sms;

use app\infrastructure\services\sms\LogSmsSender;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;

final class LogSmsSenderTest extends Unit
{
    public function testSendLogsMessageAndReturnsTrue(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $sender = new LogSmsSender($logger);

        $phone = '+79991234567';
        $message = 'Test message';

        $logger->expects($this->once())
            ->method('info')
            ->with('SMS sent (logged)', [
                'phone' => $phone,
                'message' => $message,
            ]);

        $result = $sender->send($phone, $message);

        $this->assertTrue($result);
    }
}
