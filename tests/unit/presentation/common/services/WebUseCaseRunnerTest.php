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
            ->willReturn('domain.error.key');
        $this->notifier->expects($this->once())
            ->method('error')
            ->with('domain.error.key');
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
            ->willReturn('error.unexpected');
        $this->notifier->expects($this->once())
            ->method('error')
            ->with('error.unexpected');
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
        $this->translator->method('translate')->willReturn('error.unexpected');

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

        $this->assertSame(['success' => false, 'message' => 'error.unexpected'], $result);
    }

    public function testExecuteWithFormErrorsReturnsResultOnSuccess(): void
    {
        $this->notifier->expects($this->once())
            ->method('success')
            ->with('created');

        $onDomainErrorCalled = false;

        $result = $this->runner->executeWithFormErrors(
            fn() => 42,
            'created',
            function () use (&$onDomainErrorCalled): void {
                $onDomainErrorCalled = true;
            }
        );

        $this->assertSame(42, $result);
        $this->assertFalse($onDomainErrorCalled);
    }

    public function testExecuteWithFormErrorsCallsOnDomainErrorCallback(): void
    {
        $exception = new DomainException('test.error');
        $receivedException = null;

        $this->notifier->expects($this->never())->method('success');

        $result = $this->runner->executeWithFormErrors(
            function () use ($exception): void {
                throw $exception;
            },
            'ok',
            function (DomainException $e) use (&$receivedException): void {
                $receivedException = $e;
            }
        );

        $this->assertNull($result);
        $this->assertSame($exception, $receivedException);
    }

    public function testExecuteWithFormErrorsCallsOnErrorCallbackOnDomainException(): void
    {
        $onErrorCalled = false;

        $this->runner->executeWithFormErrors(
            function (): void {
                throw new DomainException('test.error');
            },
            'ok',
            function (): void {
            },
            function () use (&$onErrorCalled): void {
                $onErrorCalled = true;
            }
        );

        $this->assertTrue($onErrorCalled);
    }

    public function testExecuteWithFormErrorsCallsOnErrorCallbackOnUnexpectedException(): void
    {
        $onErrorCalled = false;
        $this->translator->method('translate')->willReturn('error.unexpected');

        $this->logger->expects($this->once())->method('error');
        $this->notifier->expects($this->once())->method('error');

        $this->runner->executeWithFormErrors(
            function (): void {
                throw new \RuntimeException('boom');
            },
            'ok',
            function (): void {
            },
            function () use (&$onErrorCalled): void {
                $onErrorCalled = true;
            }
        );

        $this->assertTrue($onErrorCalled);
    }
}
