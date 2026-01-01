<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\services;

use app\application\ports\NotificationInterface;
use app\application\ports\TranslatorInterface;
use app\domain\exceptions\DomainException;
use app\presentation\common\services\WebUseCaseRunner;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class WebUseCaseRunnerQueryTest extends Unit
{
    private NotificationInterface $notifier;

    private LoggerInterface $logger;

    private TranslatorInterface $translator;

    private WebUseCaseRunner $runner;

    protected function _before(): void
    {
        $this->notifier = $this->createMock(NotificationInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->runner = new WebUseCaseRunner($this->notifier, $this->logger, $this->translator);
    }

    public function testQueryReturnsResult(): void
    {
        $result = $this->runner->query(fn() => 'data', 'fallback', 'error message');
        $this->assertSame('data', $result);
    }

    public function testQueryReturnsFallbackOnDomainException(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', 'domain.error.key')
            ->willReturn('domain.error.key');
        $this->notifier->expects($this->once())->method('error')->with('domain.error.key');

        $result = $this->runner->query(
            fn() => throw new DomainException('domain.error.key'),
            'fallback',
            'error message'
        );

        $this->assertSame('fallback', $result);
    }

    public function testQueryReturnsFallbackOnException(): void
    {
        $this->logger->expects($this->once())->method('error');
        $this->notifier->expects($this->once())->method('error')->with('generic error');

        $result = $this->runner->query(
            fn() => throw new RuntimeException('boom'),
            'fallback',
            'generic error'
        );

        $this->assertSame('fallback', $result);
    }

    public function testExecuteForApiReturnsDomainError(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', 'domain.error.key')
            ->willReturn('domain.error.key');

        $result = $this->runner->executeForApi(
            fn() => throw new DomainException('domain.error.key'),
            'success'
        );

        $this->assertFalse($result['success']);
        $this->assertSame('domain.error.key', $result['message']);
    }

    public function testExecuteForApiReturnsGenericError(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->willReturn('error.unexpected');
        $this->logger->expects($this->once())->method('error');

        $result = $this->runner->executeForApi(
            fn() => throw new RuntimeException('boom'),
            'success'
        );

        $this->assertFalse($result['success']);
        $this->assertSame('error.unexpected', $result['message']);
    }
}
