<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\services;

use app\application\ports\NotificationInterface;
use app\application\ports\TranslatorInterface;
use app\domain\exceptions\DomainException;
use app\presentation\common\services\WebUseCaseRunner;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class WebUseCaseRunnerTest extends Unit
{
    private NotificationInterface&MockObject $notifier;

    private LoggerInterface&MockObject $logger;

    private TranslatorInterface&MockObject $translator;

    private WebUseCaseRunner $runner;

    protected function _before(): void
    {
        $this->notifier = $this->createMock(NotificationInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->runner = new WebUseCaseRunner($this->notifier, $this->logger, $this->translator);
    }

    public function testExecuteSuccessNotifiesAndReturnsTrue(): void
    {
        $this->notifier->expects($this->once())
            ->method('success')
            ->with('ok');
        $this->notifier->expects($this->never())
            ->method('error');
        $this->logger->expects($this->never())
            ->method('error');

        $called = false;

        $result = $this->runner->execute(function () use (&$called): void {
            $called = true;
        }, 'ok');

        $this->assertTrue($result);
        $this->assertTrue($called);
    }

    public function testExecuteHandlesDomainException(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', 'domain.error.key')
            ->willReturn('Translated error');
        $this->notifier->expects($this->once())
            ->method('error')
            ->with('Translated error');
        $this->notifier->expects($this->never())
            ->method('success');
        $this->logger->expects($this->never())
            ->method('error');

        $result = $this->runner->execute(function (): void {
            throw new DomainException('domain.error.key');
        }, 'ok');

        $this->assertFalse($result);
    }

    public function testExecuteHandlesUnexpectedException(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('app', 'error.unexpected', [])
            ->willReturn('unexpected');
        $this->notifier->expects($this->once())
            ->method('error')
            ->with('unexpected');
        $this->notifier->expects($this->never())
            ->method('success');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'boom',
                $this->callback(function (array $context): bool {
                    if (!isset($context['foo']) || !isset($context['exception'])) {
                        return false;
                    }
                    return $context['foo'] === 'bar' && $context['exception'] instanceof \Throwable;
                })
            );

        $result = $this->runner->execute(function (): void {
            throw new \RuntimeException('boom');
        }, 'ok', ['foo' => 'bar']);

        $this->assertFalse($result);
    }

    public function testExecuteForApiReturnsSuccessPayload(): void
    {
        $this->logger->expects($this->never())
            ->method('error');

        $result = $this->runner->executeForApi(function (): void {
        }, 'done');

        $this->assertSame(['success' => true, 'message' => 'done'], $result);
    }

    public function testExecuteForApiHandlesUnexpectedExceptionWithLogging(): void
    {
        $this->translator->method('translate')->willReturn('error');

        $exception = new \RuntimeException('api boom');
        $logContext = ['requestId' => '123'];

        $this->logger->expects($this->once())
            ->method('error')
            ->with('api boom', $this->callback(function (array $context) use ($exception): bool {
                if (!isset($context['requestId']) || !isset($context['exception'])) {
                    return false;
                }
                return $context['requestId'] === '123' && $context['exception'] === $exception;
            }));

        $result = $this->runner->executeForApi(function () use ($exception): void {
            throw $exception;
        }, 'ok', $logContext);

        $this->assertSame(['success' => false, 'message' => 'error'], $result);
    }
}
