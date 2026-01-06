<?php

declare(strict_types=1);

namespace app\application\common\middleware;

use app\application\common\dto\IdempotencyRecordDto;
use app\application\common\IdempotencyServiceInterface;
use app\application\ports\CommandInterface;
use app\application\ports\IdempotentCommandInterface;
use app\application\ports\MiddlewareInterface;
use app\domain\exceptions\BusinessRuleException;
use app\domain\exceptions\DomainErrorCode;

final readonly class IdempotencyMiddleware implements MiddlewareInterface
{
    private const int DEFAULT_TTL = 86400;
    private const int LOCK_TIMEOUT = 1;

    public function __construct(
        private IdempotencyServiceInterface $idempotencyService,
        private int $ttl = self::DEFAULT_TTL,
    ) {
    }

    public function process(CommandInterface $command, callable $next): mixed
    {
        if (!$command instanceof IdempotentCommandInterface) {
            return $next($command);
        }

        $key = $command->getIdempotencyKey();

        if (!$this->idempotencyService->acquireLock($key, self::LOCK_TIMEOUT)) {
            throw new BusinessRuleException(DomainErrorCode::IdempotencyKeyInProgress);
        }

        try {
            $record = $this->idempotencyService->getRecord($key);

            if ($record instanceof IdempotencyRecordDto && $record->isFinished()) {
                if (!array_key_exists('result', $record->data)) {
                     throw new BusinessRuleException(DomainErrorCode::IdempotencyStorageUnavailable);
                }

                return $record->data['result'];
            }

            if (!$this->idempotencyService->startRequest($key, $this->ttl)) {
                $this->idempotencyService->releaseLock($key);

                throw new BusinessRuleException(DomainErrorCode::IdempotencyStorageUnavailable);
            }

            $result = $next($command);

            $this->idempotencyService->saveResponse(
                $key,
                200,
                ['result' => $result],
                null,
                $this->ttl,
            );

            return $result;
        } finally {
            $this->idempotencyService->releaseLock($key);
        }
    }
}
