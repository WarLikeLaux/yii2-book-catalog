<?php

declare(strict_types=1);

namespace app\infrastructure\runtime;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use yii\web\Application;
use yii2\extensions\psrbridge\http\Response as PsrBridgeResponse;

final class RunnerApplication extends Application
{
    public bool $keepDbConnection = true;

    public function handlePsrRequest(ServerRequestInterface $psrRequest): ResponseInterface
    {
        try {
            $this->state = self::STATE_BEFORE_REQUEST;
            $this->trigger(self::EVENT_BEFORE_REQUEST);

            $this->state = self::STATE_HANDLING_REQUEST;

            $this->resetRequest($psrRequest);

            $response = $this->handleRequest($this->request);

            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);
            $this->state = self::STATE_END;

            if ($response instanceof PsrBridgeResponse) {
                return $response->getPsr7Response();
            }

            throw new \RuntimeException('Response component must be instance of yii2\extensions\psrbridge\http\Response');
        } catch (Throwable $e) {
            return $this->handleErrorAsPsrResponse($e);
        } finally {
            $this->cleanup();
        }
    }

    private function resetRequest(ServerRequestInterface $psrRequest): void
    {
        if ($this->has('db', true)) {
            $this->db->open();
        }

        /** @var \yii2\extensions\psrbridge\http\Request $requestComponent */
        $requestComponent = $this->request;
        $requestComponent->setPsr7Request($psrRequest);

        $this->response->clear();

        if ($this->has('user', true)) {
            $this->user->logout(false);
        }
    }

    private function handleErrorAsPsrResponse(Throwable $e): ResponseInterface
    {
        error_log(sprintf(
            "[RunnerError] %s in %s:%d\nStack trace:\n%s",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));

        try {
            $response = $this->errorHandler->handleException($e);
            /** @var PsrBridgeResponse $response */
            return $response->getPsr7Response();
        } catch (Throwable $t) {
            error_log("[RunnerCritical] Failed to handle exception: " . $t->getMessage());
            return new \GuzzleHttp\Psr7\Response(500, [], 'Critical Error: ' . $t->getMessage());
        }
    }

    private function cleanup(): void
    {
        if ($this->has('db', true) && $this->db->isActive) {
            $transaction = $this->db->getTransaction();
            if ($transaction !== null && $transaction->isActive) {
                try {
                    $transaction->rollBack();
                } catch (Throwable $t) {
                    error_log("[RunnerError] Failed to rollback transaction: " . $t->getMessage());
                }
            }
            $this->db->close();
        }
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function coreComponents(): array
    {
        return array_merge(parent::coreComponents(), [
            'request' => ['class' => \yii2\extensions\psrbridge\http\Request::class],
            'response' => ['class' => \yii2\extensions\psrbridge\http\Response::class],
            'errorHandler' => ['class' => \yii2\extensions\psrbridge\http\ErrorHandler::class],
        ]);
    }
}
