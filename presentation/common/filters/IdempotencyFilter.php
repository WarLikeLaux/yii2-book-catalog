<?php

declare(strict_types=1);

namespace app\presentation\common\filters;

use app\application\common\dto\IdempotencyResponseDto;
use app\application\common\IdempotencyServiceInterface;
use Yii;
use yii\base\ActionFilter;
use yii\web\Request;
use yii\web\Response;

final class IdempotencyFilter extends ActionFilter
{
    private const string HEADER_KEY = 'Idempotency-Key';
    private const int TTL = 86400;

    public function __construct(
        private readonly IdempotencyServiceInterface $service,
        array $config = []
    ) {
        parent::__construct($config);
    }

    #[\Override]
    public function beforeAction($action): bool
    {
        $request = Yii::$app->request;
        if (!$request instanceof Request || !$request->getIsPost()) {
            return true;
        }

        $key = $request->getHeaders()->get(self::HEADER_KEY);
        if (!is_string($key)) {
            return true;
        }

        $cached = $this->service->getResponse($key);
        if (!$cached instanceof IdempotencyResponseDto) {
            return true;
        }

        $response = Yii::$app->response;
        if ($response instanceof Response) {
            $response->statusCode = $cached->statusCode;
            if ($cached->redirectUrl !== null) {
                $response->getHeaders()->set('Location', $cached->redirectUrl);
            } else {
                $response->data = $cached->data;
            }
            $response->getHeaders()->set('X-Idempotency-Cache', 'HIT');
        }

        return false;
    }

    #[\Override]
    public function afterAction($action, $result): mixed
    {
        $request = Yii::$app->request;
        if (!$request instanceof Request || !$request->getIsPost()) {
            return $result;
        }

        $key = $request->getHeaders()->get(self::HEADER_KEY);
        if (!is_string($key)) {
            return $result;
        }

        $response = Yii::$app->response;
        if ($response instanceof Response && $response->statusCode < 500) {
            $location = $response->getHeaders()->get('Location');
            $this->service->saveResponse(
                $key,
                $response->statusCode,
                $result,
                is_string($location) ? $location : null,
                self::TTL
            );
            $response->getHeaders()->set('X-Idempotency-Cache', 'MISS');
        }

        return $result;
    }
}
