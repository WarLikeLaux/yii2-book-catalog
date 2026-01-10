<?php

declare(strict_types=1);

namespace app\presentation\common\filters;

use app\application\common\config\IdempotencyConfig;
use app\application\common\dto\IdempotencyRecordDto;
use app\application\common\IdempotencyServiceInterface;
use Yii;
use yii\base\ActionFilter;
use yii\web\Request;
use yii\web\Response;

final class IdempotencyFilter extends ActionFilter
{
    private const string HEADER_KEY = 'Idempotency-Key';

    private string|null $lockedKey = null;
    private readonly int $ttl;
    private readonly int $lockTimeout;
    private readonly int $waitSeconds;

    public function __construct(
        private readonly IdempotencyServiceInterface $service,
        IdempotencyConfig $idempotencyConfig,
        array $config = [],
    ) {
        $this->ttl = $idempotencyConfig->ttl;
        $this->lockTimeout = $idempotencyConfig->lockTimeout;
        $this->waitSeconds = $idempotencyConfig->waitSeconds;

        parent::__construct($config);
    }

    #[\Override]
    public function beforeAction($_action): bool
    {
        $request = Yii::$app->request;

        if (!$request instanceof Request || !$request->getIsPost()) {
            return true;
        }

        $key = $request->getHeaders()->get(self::HEADER_KEY);

        if (!is_string($key)) {
            return true;
        }

        if (!$this->service->acquireLock($key, $this->lockTimeout)) {
            $this->applyInProgressResponse();
            return false;
        }

        $this->lockedKey = $key;

        $record = $this->service->getRecord($key);

        if ($record instanceof IdempotencyRecordDto) {
            $this->releaseLockIfHeld();
            return $this->handleExistingRecord($record);
        }

        if (!$this->service->startRequest($key, $this->ttl)) {
            $this->releaseLockIfHeld();
            $this->applyUnavailableResponse();
            return false;
        }

        return true;
    }

    #[\Override]
    public function afterAction($_action, $result): mixed
    {
        $request = Yii::$app->request;

        if (!$request instanceof Request || !$request->getIsPost()) {
            return $result;
        }

        $key = $request->getHeaders()->get(self::HEADER_KEY);

        if (!is_string($key)) {
            return $result;
        }

        try {
            $response = Yii::$app->response;

            if ($response instanceof Response && $response->statusCode < 500) {
                $location = $response->getHeaders()->get('Location');
                $this->service->saveResponse(
                    $key,
                    $response->statusCode,
                    $result,
                    is_string($location) ? $location : null,
                    $this->ttl,
                );
                $response->getHeaders()->set('X-Idempotency-Cache', 'MISS');
            }
        } finally {
            $this->releaseLockIfHeld();
        }

        return $result;
    }

    private function handleExistingRecord(IdempotencyRecordDto $record): bool
    {
        if (!$record->isFinished()) {
            $this->applyInProgressResponse();
            return false;
        }

        $this->applyCachedResponse($record);
        return false;
    }

    private function applyCachedResponse(IdempotencyRecordDto $cached): void
    {
        $response = Yii::$app->response;

        if (!$response instanceof Response) {
            return; // @codeCoverageIgnore
        }

        $statusCode = $cached->statusCode;

        if (!is_int($statusCode)) {
            return; // @codeCoverageIgnore
        }

        $response->statusCode = $statusCode;

        if ($cached->redirectUrl !== null) {
            $response->getHeaders()->set('Location', $cached->redirectUrl);
        } else {
            $response->data = $cached->data;
        }

        $response->getHeaders()->set('X-Idempotency-Cache', 'HIT');
    }

    private function releaseLockIfHeld(): void
    {
        if ($this->lockedKey === null) {
            return; // @codeCoverageIgnore
        }

        $this->service->releaseLock($this->lockedKey);
        $this->lockedKey = null;
    }

    private function applyInProgressResponse(): void
    {
        $response = Yii::$app->response;

        if (!$response instanceof Response) {
            return; // @codeCoverageIgnore
        }

        $response->statusCode = 409;
        $response->content = 'Idempotency key is in progress.';
        $response->getHeaders()->set('X-Idempotency-Status', 'IN_PROGRESS');
        $response->getHeaders()->set('Retry-After', (string)$this->waitSeconds);
    }

    private function applyUnavailableResponse(): void
    {
        $response = Yii::$app->response;

        if (!$response instanceof Response) {
            return; // @codeCoverageIgnore
        }

        $response->statusCode = 503;
        $response->content = 'Idempotency storage unavailable.';
        $response->getHeaders()->set('X-Idempotency-Status', 'UNAVAILABLE');
    }
}
