<?php

declare(strict_types=1);

namespace tests\unit\application;

use app\application\common\UseCaseExecutor;
use app\application\ports\NotificationInterface;
use app\application\ports\TranslatorInterface;
use app\domain\exceptions\DomainException;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class UseCaseExecutorQueryTest extends Unit
{
    private NotificationInterface $notifier;

    private LoggerInterface $logger;

    private TranslatorInterface $translator;

    private UseCaseExecutor $executor;

    protected function _before(): void
    {
        $this->notifier = $this->createMock(NotificationInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->executor = new UseCaseExecutor($this->notifier, $this->logger, $this->translator);
    }

    public function testQueryReturnsResult(): void
    {
        $result = $this->executor->query(fn() => 'data', 'fallback', 'error message');
        $this->assertSame('data', $result);
    }

    public function testQueryReturnsFallbackOnDomainException(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('domain', 'domain.error.key')
            ->willReturn('Translated domain error');
        $this->notifier->expects($this->once())->method('error')->with('Translated domain error');

        $result = $this->executor->query(
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

        $result = $this->executor->query(
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
            ->with('domain', 'domain.error.key')
            ->willReturn('Translated domain error');

        $result = $this->executor->executeForApi(
            fn() => throw new DomainException('domain.error.key'),
            'success'
        );

        $this->assertFalse($result['success']);
        $this->assertSame('Translated domain error', $result['message']);
    }

    public function testExecuteForApiReturnsGenericError(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->willReturn('unexpected error');
        $this->logger->expects($this->once())->method('error');

        $result = $this->executor->executeForApi(
            fn() => throw new RuntimeException('boom'),
            'success'
        );

        $this->assertFalse($result['success']);
        $this->assertSame('unexpected error', $result['message']);
    }
}
