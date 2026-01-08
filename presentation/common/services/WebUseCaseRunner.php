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
     * Executes the given use case via the default pipeline, sends a success notification on success, and routes errors to provided callbacks.
     *
     * @template TCommand of CommandInterface
     * @template TResponse
     * @param TCommand $command The command to execute.
     * @param UseCaseInterface<TCommand, TResponse> $useCase The use case to run.
     * @param string $successMessage Message displayed on successful execution.
     * @param callable(DomainException): void $onDomainError Callback invoked with the DomainException to handle form validation errors.
     * @param (callable(): void)|null $onError Optional callback invoked on any error before other error handling.
     * @return TResponse|null The use-case result on success, or `null` if a domain or unexpected error occurred.
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