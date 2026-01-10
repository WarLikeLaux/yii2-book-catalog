<?php

declare(strict_types=1);

namespace app\presentation\common\filters;

use app\application\common\config\RateLimitConfig;
use app\application\common\RateLimitServiceInterface;
use Yii;
use yii\base\ActionFilter;
use yii\web\Request;
use yii\web\Response;

final class RateLimitFilter extends ActionFilter
{
    private readonly int $limit;
    private readonly int $window;

    public function __construct(
        private readonly RateLimitServiceInterface $service,
        RateLimitConfig $rateLimitConfig,
        array $config = [],
    ) {
        $this->limit = $rateLimitConfig->limit;
        $this->window = $rateLimitConfig->window;

        parent::__construct($config);
    }

    #[\Override]
    public function beforeAction($_action): bool
    {
        $request = Yii::$app->request;

        if (!$request instanceof Request) {
            return true; // @codeCoverageIgnore
        }

        $ip = $this->getClientIp($request);

        if ($ip === null) {
            return true; // @codeCoverageIgnore
        }

        $result = $this->service->isAllowed($ip, $this->limit, $this->window);
        $this->setRateLimitHeaders($result->current, $result->limit, $result->resetAt);

        if (!$result->allowed) {
            $this->applyRateLimitExceeded($result->resetAt);
            return false;
        }

        return true;
    }

    private function getClientIp(Request $request): string|null
    {
        $ip = $request->getUserIP();
        return is_string($ip) ? $ip : null;
    }

    private function setRateLimitHeaders(int $current, int $limit, int $resetAt): void
    {
        $response = Yii::$app->response;

        if (!$response instanceof Response) {
            return; // @codeCoverageIgnore
        }

        $response->getHeaders()->set('X-RateLimit-Limit', (string)$limit);
        $response->getHeaders()->set('X-RateLimit-Remaining', (string)max(0, $limit - $current));
        $response->getHeaders()->set('X-RateLimit-Reset', (string)$resetAt);
    }

    private function applyRateLimitExceeded(int $resetAt): void
    {
        $response = Yii::$app->response;

        if (!$response instanceof Response) {
            return; // @codeCoverageIgnore
        }

        $retryAfter = max(1, $resetAt - time());
        $response->statusCode = 429;
        $response->getHeaders()->set('Retry-After', (string)$retryAfter);
        $response->data = [
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retryAfter' => $retryAfter,
        ];
    }
}
