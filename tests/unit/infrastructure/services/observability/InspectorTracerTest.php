<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\observability;

use app\infrastructure\services\observability\InspectorTracer;
use Codeception\Test\Unit;
use Inspector\Models\Transaction;
use ReflectionClass;

final class InspectorTracerTest extends Unit
{
    private const CONTENT_TYPE_JSON = 'application/json';
    private const CONTENT_TYPE_HTML = 'text/html';
    private const QUERY_SELECT_1 = 'SELECT 1';
    private const IP_PRIVATE = '10.0.0.1';
    private const IP_LOOPBACK = '127.0.0.1';
    private const API_URL = 'http://localhost/api';
    private InspectorTracer $tracer;
    private ReflectionClass $reflection;

    protected function _before(): void
    {
        $this->reflection = new ReflectionClass(InspectorTracer::class);
        $this->tracer = $this->reflection->newInstanceWithoutConstructor();
    }

    public function testAsStringWithScalar(): void
    {
        $this->assertSame('hello', $this->invokeAsString('hello'));
        $this->assertSame('42', $this->invokeAsString(42));
        $this->assertSame('1', $this->invokeAsString(true));
        $this->assertSame('3.14', $this->invokeAsString(3.14));
    }

    public function testAsStringWithNull(): void
    {
        $this->assertSame('', $this->invokeAsString(null));
    }

    public function testAsStringWithNonScalar(): void
    {
        $this->assertSame('', $this->invokeAsString(['array']));
        $this->assertSame('', $this->invokeAsString(new \stdClass()));
    }

    public function testIsUnsafeKeyDetectsCookie(): void
    {
        $this->assertTrue($this->invokeIsUnsafeKey('session_cookie'));
        $this->assertTrue($this->invokeIsUnsafeKey('Cookie'));
        $this->assertTrue($this->invokeIsUnsafeKey('COOKIE_DATA'));
    }

    public function testIsUnsafeKeyDetectsHeader(): void
    {
        $this->assertTrue($this->invokeIsUnsafeKey('X-Header-Auth'));
        $this->assertTrue($this->invokeIsUnsafeKey('HEADER'));
        $this->assertTrue($this->invokeIsUnsafeKey('my-header-value'));
    }

    public function testIsUnsafeKeyAllowsSafeKeys(): void
    {
        $this->assertFalse($this->invokeIsUnsafeKey('db.query'));
        $this->assertFalse($this->invokeIsUnsafeKey('http.method'));
        $this->assertFalse($this->invokeIsUnsafeKey('user_id'));
    }

    public function testBuildCleanHeadersFiltersUnsafe(): void
    {
        $headers = json_encode([
            'Content-Type' => ['application/json'],
            'Cookie' => ['session=abc'],
            'X-Request-Id' => ['123'],
        ], JSON_THROW_ON_ERROR);

        $result = $this->invokeBuildCleanHeaders(['http.headers' => $headers]);

        $this->assertArrayNotHasKey('Cookie', $result);
        $this->assertArrayHasKey('X-Request-Id', $result);
        $this->assertSame('123', $result['X-Request-Id']);
    }

    public function testBuildCleanHeadersJoinsArrayValues(): void
    {
        $headers = json_encode([
            'Accept' => ['text/html', 'application/json'],
        ], JSON_THROW_ON_ERROR);

        $result = $this->invokeBuildCleanHeaders(['http.headers' => $headers]);

        $this->assertSame('text/html, application/json', $result['Accept']);
    }

    public function testBuildCleanHeadersHandlesInvalidJson(): void
    {
        $result = $this->invokeBuildCleanHeaders(['http.headers' => 'not-json']);

        $this->assertSame([], $result);
    }

    public function testBuildCleanHeadersHandlesMissingKey(): void
    {
        $result = $this->invokeBuildCleanHeaders([]);

        $this->assertSame([], $result);
    }

    public function testBuildCleanHeadersHandlesStringValues(): void
    {
        $headers = json_encode([
            'Accept' => 'text/html',
        ], JSON_THROW_ON_ERROR);

        $result = $this->invokeBuildCleanHeaders(['http.headers' => $headers]);

        $this->assertSame('text/html', $result['Accept']);
    }

    public function testBuildCustomDataFiltersUnsafe(): void
    {
        $attributes = [
            'db.query' => self::QUERY_SELECT_1,
            'cookie_data' => 'secret',
            'user_id' => 42,
        ];

        $result = $this->invokeBuildCustomData($attributes);

        $this->assertArrayHasKey('db.query', $result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayNotHasKey('cookie_data', $result);
    }

    public function testBuildCustomDataReturnsEmptyForAllUnsafe(): void
    {
        $result = $this->invokeBuildCustomData([
            'header_val' => 'x',
            'cookie_val' => 'y',
        ]);

        $this->assertSame([], $result);
    }

    public function testBuildRequestTableBasic(): void
    {
        $attributes = [
            'http.user_agent' => 'TestBot/1.0',
        ];

        $result = $this->invokeBuildRequestTable($attributes, self::IP_PRIVATE);

        $this->assertSame(self::IP_PRIVATE, $result['IP']);
        $this->assertSame('TestBot/1.0', $result['Agent']);
    }

    public function testBuildRequestTableWithQueryParams(): void
    {
        $attributes = [
            'http.user_agent' => 'Bot',
            'query_params' => json_encode(['page' => 1, 'sort' => 'name'], JSON_THROW_ON_ERROR),
        ];

        $result = $this->invokeBuildRequestTable($attributes, self::IP_LOOPBACK);

        $this->assertSame(1, $result['page']);
        $this->assertSame('name', $result['sort']);
        $this->assertSame(self::IP_LOOPBACK, $result['IP']);
    }

    public function testBuildRequestTableWithInvalidQueryParams(): void
    {
        $attributes = [
            'http.user_agent' => '',
            'query_params' => 'not-json',
        ];

        $result = $this->invokeBuildRequestTable($attributes, self::IP_LOOPBACK);

        $this->assertSame(self::IP_LOOPBACK, $result['IP']);
        $this->assertSame('', $result['Agent']);
        $this->assertCount(2, $result);
    }

    public function testFillTransactionDataSetsContexts(): void
    {
        $transaction = new Transaction('test');
        $transaction->start();
        $transaction->markAsRequest();

        $attributes = [
            'http.url' => 'http://localhost/api/books',
            'http.method' => 'POST',
            'http.client_ip' => '192.168.1.1',
            'http.target' => '/api/books',
            'http.user_agent' => 'TestBot',
            'http.headers' => json_encode(['Accept' => ['application/json']], JSON_THROW_ON_ERROR),
            'db.query' => self::QUERY_SELECT_1,
        ];

        $this->invokeFillTransactionData($transaction, $attributes);

        /** @var array<string, mixed> $urlContext */
        $urlContext = $transaction->getContext('URL');
        $this->assertSame('http://localhost/api/books', $urlContext['Full']);
        $this->assertSame('POST', $urlContext['Method']);

        /** @var array<string, mixed> $requestContext */
        $requestContext = $transaction->getContext('Request');
        $this->assertSame('192.168.1.1', $requestContext['IP']);

        /** @var array<string, mixed> $headersContext */
        $headersContext = $transaction->getContext('Headers');
        $this->assertSame('application/json', $headersContext['Accept']);

        /** @var array<string, mixed> $customContext */
        $customContext = $transaction->getContext('Custom');
        $this->assertSame(self::QUERY_SELECT_1, $customContext['db.query']);
    }

    public function testFillTransactionDataWithoutCustomData(): void
    {
        $transaction = new Transaction('test');
        $transaction->start();
        $transaction->markAsRequest();

        $this->invokeFillTransactionData($transaction, []);

        $this->assertArrayNotHasKey('Custom', $transaction->context);
    }

    public function testApplyHttpDataSetsProperties(): void
    {
        $transaction = new Transaction('test');
        $transaction->start();
        $transaction->markAsRequest();

        $this->invokeApplyHttpData(
            $transaction,
            self::API_URL,
            'PUT',
            self::IP_PRIVATE,
            ['http.target' => '/api'],
            ['Accept' => 'text/html'],
        );

        $this->assertSame(self::API_URL, $transaction->http->url->full);
        $this->assertSame('/api', $transaction->http->url->path);
        $this->assertSame('PUT', $transaction->http->request->method);
        $this->assertSame(['Accept' => 'text/html'], $transaction->http->request->headers);
        $this->assertSame(self::IP_PRIVATE, $transaction->http->request->socket->remote_address);
    }

    public function testApplyHttpDataWithoutHttp(): void
    {
        $transaction = new Transaction('test');
        $transaction->start();

        $this->invokeApplyHttpData($transaction, '', '', '', [], []);

        $this->assertNull($transaction->http);
    }

    public function testApplyHttpDataWithoutSocket(): void
    {
        $transaction = new Transaction('test');
        $transaction->start();
        $transaction->markAsRequest();
        $transaction->http->request->socket = null;

        $this->invokeApplyHttpData(
            $transaction,
            self::API_URL,
            'GET',
            self::IP_PRIVATE,
            [],
            [],
        );

        $this->assertSame(self::API_URL, $transaction->http->url->full);
        $this->assertNull($transaction->http->request->socket);
    }

    public function testApplyHttpDataParsesUrlPathWhenTargetMissing(): void
    {
        $transaction = new Transaction('test');
        $transaction->start();
        $transaction->markAsRequest();

        $this->invokeApplyHttpData(
            $transaction,
            'http://localhost/books?page=1',
            'GET',
            self::IP_LOOPBACK,
            [],
            [],
        );

        $this->assertSame('/books', $transaction->http->url->path);
    }

    private function invokeAsString(mixed $value): string
    {
        $method = $this->reflection->getMethod('asString');

        /** @var string */
        return $method->invoke($this->tracer, $value);
    }

    private function invokeIsUnsafeKey(string $key): bool
    {
        $method = $this->reflection->getMethod('isUnsafeKey');

        /** @var bool */
        return $method->invoke($this->tracer, $key);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function invokeBuildCleanHeaders(array $attributes): array
    {
        $method = $this->reflection->getMethod('buildCleanHeaders');

        /** @var array<string, mixed> */
        return $method->invoke($this->tracer, $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function invokeBuildCustomData(array $attributes): array
    {
        $method = $this->reflection->getMethod('buildCustomData');

        /** @var array<string, mixed> */
        return $method->invoke($this->tracer, $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function invokeBuildRequestTable(array $attributes, string $ip): array
    {
        $method = $this->reflection->getMethod('buildRequestTable');

        /** @var array<string, mixed> */
        return $method->invoke($this->tracer, $attributes, $ip);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function invokeFillTransactionData(Transaction $transaction, array $attributes): void
    {
        $method = $this->reflection->getMethod('fillTransactionData');
        $method->invoke($this->tracer, $transaction, $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $cleanHeaders
     */
    private function invokeApplyHttpData(
        Transaction $transaction,
        string $url,
        string $method,
        string $ip,
        array $attributes,
        array $cleanHeaders,
    ): void {
        $m = $this->reflection->getMethod('applyHttpData');
        $m->invoke($this->tracer, $transaction, $url, $method, $ip, $attributes, $cleanHeaders);
    }
}
