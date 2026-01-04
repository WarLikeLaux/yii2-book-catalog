<?php

declare(strict_types=1);

namespace app\infrastructure\services\observability;

use app\application\ports\SpanInterface;
use app\application\ports\TracerInterface;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\web\Application as WebApplication;

/**
 * @codeCoverageIgnore Интеграционный компонент Yii2
 */
final class TracerBootstrap extends Component implements BootstrapInterface
{
    public bool $enabled = true;
    public string $endpoint = 'http://buggregator:8000';
    public string $ingestionKey = 'buggregator';
    public string $serviceName = 'yii2-book-catalog';
    private TracerInterface|null $tracer = null;
    private SpanInterface|null $rootSpan = null;

    /**
     * @param \yii\base\Application $app
     */
    #[\Override]
    public function bootstrap($app): void
    {
        if (!$this->enabled) {
            $this->tracer = new NullTracer();
            $this->registerTracer();
            return;
        }

        $this->tracer = new InspectorTracer($this->ingestionKey, $this->endpoint);
        $this->registerTracer();

        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app): void {
            $this->startRootSpan($app);
        });

        $app->on(Application::EVENT_AFTER_REQUEST, function () use ($app): void {
            $this->endRootSpan($app);
        });
    }

    private function registerTracer(): void
    {
        \Yii::$container->setSingleton(TracerInterface::class, fn(): TracerInterface|null => $this->tracer);
    }

    private function startRootSpan(Application $app): void
    {
        if (!$app instanceof WebApplication) {
            return;
        }

        RequestIdProvider::reset();

        $request = $app->getRequest();
        $pathInfo = $request->getPathInfo();

        if (
            str_starts_with($pathInfo, 'debug/')
            || str_starts_with($pathInfo, 'gii/')
            || str_starts_with($pathInfo, '.well-known/')
        ) {
            return;
        }

        $attributes = [
            'http.method' => $request->getMethod(),
            'http.url' => $request->getAbsoluteUrl(),
            'http.target' => $request->getUrl(),
            'http.user_agent' => $request->getUserAgent() ?? '',
            'http.client_ip' => $request->getUserIP() ?? 'unknown',
            'http.headers' => json_encode($request->getHeaders()->toArray(), JSON_UNESCAPED_UNICODE),
            'request_id' => RequestIdProvider::get(),
        ];

        $spanName = $request->getMethod() . ' ' . $pathInfo;
        $this->rootSpan = $this->tracer?->startSpan($spanName, $attributes);

        if (!($this->rootSpan instanceof SpanInterface)) {
            return;
        }

        $this->rootSpan->setAttribute('url', $request->getAbsoluteUrl());
        $this->rootSpan->setAttribute('method', $request->getMethod());
        $queryParams = $request->getQueryParams();

        if ($queryParams !== []) {
            $this->rootSpan->setAttribute('query_params', (string)json_encode($queryParams, JSON_UNESCAPED_UNICODE));
        }

        $this->rootSpan->setAttribute('headers', (string)json_encode($request->getHeaders()->toArray(), JSON_UNESCAPED_UNICODE));
    }

    private function endRootSpan(Application $app): void
    {
        if (!$this->rootSpan instanceof SpanInterface) {
            return;
        }

        if ($app instanceof WebApplication) {
            $response = $app->getResponse();
            $statusCode = $response->getStatusCode();
            $this->rootSpan->setAttribute('http.status_code', $statusCode);
            $this->rootSpan->setStatus($statusCode < 400);
        }

        $this->rootSpan->end();
        $this->tracer?->flush();
    }
}
