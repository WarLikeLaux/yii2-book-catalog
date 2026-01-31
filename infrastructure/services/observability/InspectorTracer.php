<?php

declare(strict_types=1);

namespace app\infrastructure\services\observability;

use app\application\ports\SpanInterface;
use app\application\ports\TracerInterface;
use Inspector\Configuration;
use Inspector\Inspector;
use Inspector\Models\Partials\Http;
use Inspector\Models\Partials\Socket;
use Inspector\Models\Transaction;
use Override;
use Throwable;

/**
 * @codeCoverageIgnore Инфраструктурный адаптер для Inspector SDK
 */
final class InspectorTracer implements TracerInterface
{
    private readonly Inspector $inspector;
    private InspectorSpan|null $currentSpan = null;

    public function __construct(
        string $ingestionKey,
        string $url,
    ) {
        $configuration = new Configuration($ingestionKey);
        $configuration->setUrl($url);
        $configuration->setEnabled(true);
        $this->inspector = new Inspector($configuration);
    }

    #[Override]
    /** @param array<string, mixed> $attributes */
    public function startSpan(string $name, array $attributes = []): SpanInterface
    {
        if (!$this->inspector->hasTransaction()) {
            return $this->startRootSpan($name, $attributes);
        }

        return $this->startChildSpan($name, $attributes);
    }

    /** @param array<string, mixed> $attributes */
    private function startRootSpan(string $name, array $attributes): SpanInterface
    {
        /** @var Transaction $item */
        $item = $this->inspector->startTransaction($name);

        $item->markAsRequest();

        if ($item->http instanceof Http) {
            $item->http->request->cookies = [];
        }

        $this->fillTransactionData($item, $attributes);

        $span = new InspectorSpan($item);
        $this->currentSpan = $span;
        return $span;
    }

    /** @param array<string, mixed> $attributes */
    private function fillTransactionData(Transaction $transaction, array $attributes): void
    {
        $url = $this->asString($attributes['http.url'] ?? '');
        $method = $this->asString($attributes['http.method'] ?? 'GET');
        $ip = $this->asString($attributes['http.client_ip'] ?? '127.0.0.1');

        $cleanHeaders = $this->buildCleanHeaders($attributes);
        $this->applyHttpData($transaction, $url, $method, $ip, $attributes, $cleanHeaders);

        $transaction->addContext('URL', ['Full' => $url, 'Method' => $method]);
        $transaction->addContext('Request', $this->buildRequestTable($attributes, $ip));
        $transaction->addContext('Headers', $cleanHeaders);

        $customData = $this->buildCustomData($attributes);

        if ($customData === []) {
            return;
        }

        $transaction->addContext('Custom', $customData);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function buildCleanHeaders(array $attributes): array
    {
        $decodedHeaders = json_decode($this->asString($attributes['http.headers'] ?? '[]'), true);

        if (!is_array($decodedHeaders)) {
            return [];
        }

        $cleanHeaders = [];

        foreach ($decodedHeaders as $key => $values) {
            $keyString = $this->asString($key);

            if ($this->isUnsafeKey($keyString)) {
                continue;
            }

            $cleanHeaders[$keyString] = is_array($values) ? implode(', ', $values) : $this->asString($values);
        }

        return $cleanHeaders;
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $cleanHeaders
     */
    private function applyHttpData(
        Transaction $transaction,
        string $url,
        string $method,
        string $ip,
        array $attributes,
        array $cleanHeaders,
    ): void {
        if (!$transaction->http instanceof Http) {
            return;
        }

        $transaction->http->url->full = $url;
        $transaction->http->url->path = $this->asString($attributes['http.target'] ?? parse_url($url, PHP_URL_PATH));
        $transaction->http->request->method = $method;
        $transaction->http->request->headers = $cleanHeaders;

        if (!($transaction->http->request->socket instanceof Socket)) {
            return;
        }

        $transaction->http->request->socket->remote_address = $ip;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function buildCustomData(array $attributes): array
    {
        $customData = [];

        foreach ($attributes as $key => $value) {
            if ($this->isUnsafeKey($this->asString($key))) {
                continue;
            }

            $customData[$key] = $value;
        }

        return $customData;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function buildRequestTable(array $attributes, string $ip): array
    {
        $table = [
            'IP' => $ip,
            'Agent' => $this->asString($attributes['http.user_agent'] ?? ''),
        ];

        if (!isset($attributes['query_params'])) {
            return $table;
        }

        $query = json_decode($this->asString($attributes['query_params']), true);
        return is_array($query) ? array_merge($table, $query) : $table;
    }

    /** @param array<string, mixed> $attributes */
    private function startChildSpan(string $name, array $attributes): SpanInterface
    {
        $item = $this->inspector->startSegment('database', $name);

        $cleanAttributes = [];

        foreach ($attributes as $k => $v) {
            if ($this->isUnsafeKey($this->asString($k))) {
                continue;
            }

            $cleanAttributes[$k] = $v;
        }

        $item->addContext('Custom', $cleanAttributes);

        $span = new InspectorSpan($item);
        $this->currentSpan = $span;
        return $span;
    }

    private function asString(mixed $value): string
    {
        return is_scalar($value) || $value === null ? (string)$value : '';
    }

    private function isUnsafeKey(string $key): bool
    {
        $k = strtolower($key);
        return str_contains($k, 'cookie') || str_contains($k, 'header');
    }

    #[Override]
    /** @param array<string, mixed> $attributes */
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

    #[Override]
    public function activeSpan(): SpanInterface|null
    {
        return $this->currentSpan;
    }

    #[Override]
    public function flush(): void
    {
    }
}
