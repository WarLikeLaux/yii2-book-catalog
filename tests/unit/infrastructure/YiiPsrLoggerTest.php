<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\infrastructure\services\YiiPsrLogger;
use Codeception\Test\Unit;
use Psr\Log\LogLevel;

final class YiiPsrLoggerTest extends Unit
{
    private YiiPsrLogger $logger;

    protected function _before(): void
    {
        $this->logger = new YiiPsrLogger('test');
    }

    public function testEmergency(): void
    {
        $this->logger->emergency('emergency message');
        $this->assertTrue(true);
    }

    public function testAlert(): void
    {
        $this->logger->alert('alert message');
        $this->assertTrue(true);
    }

    public function testCritical(): void
    {
        $this->logger->critical('critical message');
        $this->assertTrue(true);
    }

    public function testError(): void
    {
        $this->logger->error('error message');
        $this->assertTrue(true);
    }

    public function testWarning(): void
    {
        $this->logger->warning('warning message');
        $this->assertTrue(true);
    }

    public function testNotice(): void
    {
        $this->logger->notice('notice message');
        $this->assertTrue(true);
    }

    public function testInfo(): void
    {
        $this->logger->info('info message');
        $this->assertTrue(true);
    }

    public function testDebug(): void
    {
        $this->logger->debug('debug message');
        $this->assertTrue(true);
    }

    public function testLogWithContext(): void
    {
        $this->logger->log(LogLevel::INFO, 'message with context', ['key' => 'value']);
        $this->assertTrue(true);
    }

    public function testLogWithEmptyContext(): void
    {
        $this->logger->log(LogLevel::INFO, 'message without context');
        $this->assertTrue(true);
    }
}
