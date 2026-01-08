<?php

declare(strict_types=1);

namespace app\presentation\common\services;

use app\application\common\pipeline\PipelineFactory;
use app\application\ports\CommandInterface;
use app\application\ports\NotificationInterface;
use app\application\ports\TranslatorInterface;
use app\application\ports\UseCaseInterface;
use app\domain\exceptions\DomainException;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class WebUseCaseRunner
{
    public function __construct(
        private NotificationInterface $notifier,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private PipelineFactory $pipelineFactory,
    ) {
    }

    /**
     * @template TCommand of CommandInterface
     * @template TResponse
     * @param TCommand $command
     * @param UseCaseInterface<TCommand, TResponse> $useCase
     * @param array<string, mixed> $logContext
     * @return TResponse|null
     */
    public function execute(
        CommandInterface $command,
        UseCaseInterface $useCase,
        string $successMessage,
        array $logContext = [],
    ): mixed {
        try {
            /** @var TResponse $result */
            $result = $this->pipelineFactory->createDefault()->execute($command, $useCase);
            $this->notifier->success($successMessage);
            return $result;
        } catch (DomainException $e) {
            $this->notifier->error($this->translator->translate('app', $e->getMessage()));
            return null;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), array_merge($logContext, ['exception' => $e]));
            $this->notifier->error($this->translator->translate('app', 'error.unexpected'));
            return null;
        }
    }

    /**
     * @template TCommand of CommandInterface
     * @template TResponse
     * @param TCommand $command
     * @param UseCaseInterface<TCommand, TResponse> $useCase
     * @param array<string, mixed> $logContext
     * @return array<string, mixed>
     */
    public function executeForApi(
        CommandInterface $command,
        UseCaseInterface $useCase,
        string $successMessage,
        array $logContext = [],
    ): array {
        try {
            /** @var TResponse $result */
            $result = $this->pipelineFactory->createDefault()->execute($command, $useCase);
            return ['success' => true, 'message' => $successMessage, 'data' => $result];
        } catch (DomainException $e) {
            return ['success' => false, 'message' => $this->translator->translate('app', $e->getMessage())];
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), array_merge($logContext, ['exception' => $e]));
            return ['success' => false, 'message' => $this->translator->translate('app', 'error.unexpected')];
        }
    }

    /**
     * @template TCommand of CommandInterface
     * @template TResponse
     * @param TCommand $command
     * @param UseCaseInterface<TCommand, TResponse> $useCase
     * @param callable(DomainException): void $onDomainError
     * @param (callable(): void)|null $onError
     * @return TResponse|null
     */
    public function executeWithFormErrors(
        CommandInterface $command,
        UseCaseInterface $useCase,
        string $successMessage,
        callable $onDomainError,
        callable|null $onError = null,
    ): mixed {
        try {
            /** @var TResponse $result */
            $result = $this->pipelineFactory->createDefault()->execute($command, $useCase);
            $this->notifier->success($successMessage);
            return $result;
        } catch (DomainException $e) {
            if ($onError !== null) {
                $onError();
            }

            $onDomainError($e);
            return null;
        } catch (Throwable $e) {
            if ($onError !== null) {
                $onError();
            }

            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $this->notifier->error($this->translator->translate('app', 'error.unexpected'));
            return null;
        }
    }

    /**
     * @template TResponse
     * @param callable(): TResponse $query
     * @param TResponse $fallback
     * @param array<string, mixed> $logContext
     * @return TResponse
     */
    public function query(callable $query, mixed $fallback, string $errorMessage, array $logContext = []): mixed
    {
        try {
            return $query();
        } catch (DomainException $e) {
            $this->notifier->error($this->translator->translate('app', $e->getMessage()));
            return $fallback;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), array_merge($logContext, ['exception' => $e]));
            $this->notifier->error($errorMessage);
            return $fallback;
        }
    }
}
