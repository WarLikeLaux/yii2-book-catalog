<?php

declare(strict_types=1);

namespace app\infrastructure\services\observability;

use app\application\ports\SpanInterface;
use Closure;
use OpenTelemetry\API\Trace\SpanInterface as OtelApiSpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;
use Override;
use Throwable;

final readonly class OtelSpan implements SpanInterface
{
    public function __construct(
        private OtelApiSpanInterface $span,
        private ScopeInterface|null $scope = null,
        private Closure|null $onEnd = null,
    ) {
    }

    #[Override]
    public function setAttribute(string $key, string|int|float|bool $value): self
    {
        $this->span->setAttribute($key, $value);
        return $this;
    }

    #[Override]
    public function setStatus(bool $ok, string $description = ''): self
    {
        $status = $ok ? StatusCode::STATUS_OK : StatusCode::STATUS_ERROR;
        $this->span->setStatus($status, $description);
        return $this;
    }

    #[Override]
    public function recordException(Throwable $exception): self
    {
        $this->span->recordException($exception);
        $this->span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        return $this;
    }

    #[Override]
    public function end(): void
    {
        $this->span->end();
        $this->scope?->detach();

        if (!$this->onEnd instanceof Closure) {
            return;
        }

        ($this->onEnd)();
    }
}
