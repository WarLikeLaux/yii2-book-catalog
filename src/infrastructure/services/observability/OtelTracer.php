<?php

declare(strict_types=1);

namespace app\infrastructure\services\observability;

use app\application\ports\SpanInterface;
use app\application\ports\TracerInterface;
use OpenTelemetry\API\Trace\TracerInterface as OtelApiTracerInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use Override;
use Throwable;

final class OtelTracer implements TracerInterface
{
    private readonly OtelApiTracerInterface $tracer;
    private readonly TracerProviderInterface $tracerProvider;
    private OtelSpan|null $currentSpan = null;

    /** @codeCoverageIgnore */
    public function __construct(
        string $serviceName,
        string $endpoint,
    ) {
        $resource = ResourceInfoFactory::emptyResource()->merge(ResourceInfo::create(Attributes::create([
            'service.name' => $serviceName,
        ])));

        $transport = (new OtlpHttpTransportFactory())->create($endpoint, 'application/x-protobuf');
        $exporter = new SpanExporter($transport);
        $spanProcessor = BatchSpanProcessor::builder($exporter)->build();

        $this->tracerProvider = TracerProvider::builder()
            ->addSpanProcessor($spanProcessor)
            ->setResource($resource)
            ->setSampler(new AlwaysOnSampler())
            ->build();

        $this->tracer = $this->tracerProvider->getTracer($serviceName);
    }

    /**
     * @codeCoverageIgnore
     * @param array<string, mixed> $attributes
     */
    #[Override]
    public function startSpan(string $name, array $attributes = []): SpanInterface
    {
        $spanBuilder = $this->tracer->spanBuilder($name);

        foreach ($attributes as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $spanBuilder->setAttribute($key, $value);
        }

        $otelSpan = $spanBuilder->startSpan();

        $scope = $otelSpan->activate();
        $previousSpan = $this->currentSpan;

        $span = new OtelSpan($otelSpan, $scope, function () use ($previousSpan): void {
            $this->currentSpan = $previousSpan;
        });

        $this->currentSpan = $span;

        return $span;
    }

    /**
     * @codeCoverageIgnore
     * @param array<string, mixed> $attributes
     */
    #[Override]
    public function trace(string $name, callable $callback, array $attributes = []): mixed
    {
        if ($name === '') {
            return $callback();
        }

        $span = $this->startSpan($name, $attributes);

        try {
            return $callback();
        } catch (Throwable $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $span->end();
        }
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function activeSpan(): SpanInterface|null
    {
        return $this->currentSpan;
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function flush(): void
    {
        $this->tracerProvider->forceFlush();
    }
}
