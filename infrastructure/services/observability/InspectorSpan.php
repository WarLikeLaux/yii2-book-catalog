<?php

declare(strict_types=1);

namespace app\infrastructure\services\observability;

use app\application\ports\SpanInterface;
use Inspector\Models\Partials\Host;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;
use Throwable;

/**
 * @codeCoverageIgnore Инфраструктурный адаптер для Inspector SDK
 */
final readonly class InspectorSpan implements SpanInterface
{
    public function __construct(
        private Transaction|Segment $item,
    ) {
    }

    /**
     * Adds a custom attribute to the underlying span unless the attribute name indicates sensitive data.
     *
     * If the attribute name contains "header" or "cookie" (case-insensitive), the attribute is ignored.
     * The attribute is stored in the span's Custom context using the original key casing.
     *
     * @param string $key The attribute name (original casing is preserved when stored).
     * @param string|int|float|bool $value The attribute value.
     * @return self The span instance for method chaining.
     */
    #[\Override]
    public function setAttribute(string $key, string|int|float|bool $value): self
    {
        $k = strtolower($key);

        if (str_contains($k, 'header') || str_contains($k, 'cookie')) {
            return $this;
        }

        /** @var array<string, mixed> $currentContext */
        $currentContext = $this->item->getContext('Custom');
        $data = $currentContext;

        $data[$key] = $value;
        $this->item->addContext('Custom', $data);

        return $this;
    }

    #[\Override]
    public function setStatus(bool $ok, string $description = ''): self
    {
        if ($this->item instanceof Segment) {
            return $this;
        }

        if (!$ok) {
            $this->item->setResult('error');

            if ($description !== '') {
                $this->item->addContext('Status', ['description' => $description]);
            }

            return $this;
        }

        $this->item->setResult('success');
        return $this;
    }

    #[\Override]
    public function recordException(Throwable $exception): self
    {
        if ($this->item instanceof Transaction) {
            $this->item->setResult('error');
        }

        $this->item->addContext('Exception', [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        return $this;
    }

    #[\Override]
    public function end(): void
    {
        $this->item->end();

        $this->item->timestamp *= 1000;

        if ($this->item instanceof Segment) {
            $data = $this->item->transaction;

            if (
                is_array($data)
                && isset($data['timestamp'])
                && is_numeric($data['timestamp'])
                && $data['timestamp'] < 9999999999
            ) {
                $timestamp = (float)$data['timestamp'];
                $data['timestamp'] = $timestamp * 1000;
                $this->item->transaction = $data;
            }
        }

        if (!($this->item instanceof Transaction)) {
            return;
        }

        if (!$this->item->host instanceof Host) {
            return;
        }

        $this->item->host->hostname = (string)gethostname();
    }
}