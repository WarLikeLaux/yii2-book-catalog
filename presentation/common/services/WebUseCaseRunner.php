<?php

declare(strict_types=1);

namespace app\presentation\common\services;

use app\application\ports\NotificationInterface;
use app\application\ports\TranslatorInterface;
use app\domain\exceptions\DomainException;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class WebUseCaseRunner
{
    public function __construct(
        private NotificationInterface $notifier,
        private LoggerInterface $logger,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @param array<string, mixed> $logContext
     */
    public function execute(callable $useCase, string $successMessage, array $logContext = []): bool
    {
        try {
            $useCase();
            $this->notifier->success($successMessage);
            return true;
        } catch (DomainException $e) {
            $this->notifier->error($this->translator->translate('app', $e->getMessage()));
            return false;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), array_merge($logContext, ['exception' => $e]));
            $this->notifier->error($this->translator->translate('app', 'error.unexpected'));
            return false;
        }
    }

    /**
     * @param array<string, mixed> $logContext
     * @return array<string, mixed>
     */
    public function executeForApi(callable $useCase, string $successMessage, array $logContext = []): array
    {
        try {
            $useCase();
            return ['success' => true, 'message' => $successMessage];
        } catch (DomainException $e) {
            return ['success' => false, 'message' => $this->translator->translate('app', $e->getMessage())];
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), array_merge($logContext, ['exception' => $e]));
            return ['success' => false, 'message' => $this->translator->translate('app', 'error.unexpected')];
        }
    }

    /**
     * @template T
     * @param callable(): T $action
     * @param callable(DomainException): void $onDomainError
     * @param (callable(): void)|null $onError
     * @return T|null
     */
    public function executeWithFormErrors(
        callable $action,
        string $successMessage,
        callable $onDomainError,
        callable|null $onError = null
    ): mixed {
        try {
            $result = $action();
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
     * @param array<string, mixed> $logContext
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
