<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services;

use app\infrastructure\services\BuggregatorLogTarget;
use Codeception\Test\Unit;
use ReflectionClass;
use RuntimeException;
use yii\log\Logger;

final class BuggregatorLogTargetTest extends Unit
{
    private BuggregatorLogTarget $target;
    private ReflectionClass $reflection;

    protected function _before(): void
    {
        $this->target = new BuggregatorLogTarget();
        $this->reflection = new ReflectionClass($this->target);
    }

    public function testExtractMessageFromString(): void
    {
        $result = $this->invokeExtractMessage('hello world');

        $this->assertSame('hello world', $result);
    }

    public function testExtractMessageFromException(): void
    {
        $exception = new RuntimeException('something broke');

        $result = $this->invokeExtractMessage($exception);

        $this->assertSame('[RuntimeException] something broke', $result);
    }

    public function testExtractMessageFromArray(): void
    {
        $data = ['key' => 'value'];

        $result = $this->invokeExtractMessage($data);

        $this->assertSame('{"key":"value"}', $result);
    }

    public function testExtractMessageFromInt(): void
    {
        $result = $this->invokeExtractMessage(42);

        $this->assertSame('42', $result);
    }

    public function testFormatTraceReturnsFrames(): void
    {
        $exception = new RuntimeException('test');

        $result = $this->invokeFormatTrace($exception);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $frame = $result[0];
        $this->assertArrayHasKey('file', $frame);
        $this->assertArrayHasKey('line', $frame);
        $this->assertArrayHasKey('function', $frame);
        $this->assertArrayHasKey('class', $frame);
    }

    public function testFormatTraceHandlesMissingFileAndLine(): void
    {
        $exception = new RuntimeException('test');

        $ref = new \ReflectionClass(\Exception::class);
        $traceProp = $ref->getProperty('trace');
        $traceProp->setValue($exception, [['function' => 'testFunc']]);

        $result = $this->invokeFormatTrace($exception);

        $this->assertSame('unknown', $result[0]['file']);
        $this->assertSame(0, $result[0]['line']);
        $this->assertSame('testFunc', $result[0]['function']);
        $this->assertNull($result[0]['class']);
    }

    public function testExtractExceptionContextReturnsStructure(): void
    {
        $exception = new RuntimeException('test error', 42);

        $result = $this->invokeExtractExceptionContext($exception);

        $this->assertSame(RuntimeException::class, $result['class']);
        $this->assertSame('test error', $result['message']);
        $this->assertSame(42, $result['code']);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('line', $result);
        $this->assertArrayHasKey('trace', $result);
        $this->assertArrayNotHasKey('previous', $result);
    }

    public function testExtractExceptionContextWithPrevious(): void
    {
        $previous = new RuntimeException('root cause');
        $exception = new RuntimeException('wrapper', 0, $previous);

        $result = $this->invokeExtractExceptionContext($exception);

        $this->assertArrayHasKey('previous', $result);
        $this->assertSame(RuntimeException::class, $result['previous']['class']);
        $this->assertSame('root cause', $result['previous']['message']);
    }

    public function testGetMonologLevelError(): void
    {
        $this->assertSame(400, $this->invokeGetMonologLevel(Logger::LEVEL_ERROR));
    }

    public function testGetMonologLevelWarning(): void
    {
        $this->assertSame(300, $this->invokeGetMonologLevel(Logger::LEVEL_WARNING));
    }

    public function testGetMonologLevelInfo(): void
    {
        $this->assertSame(200, $this->invokeGetMonologLevel(Logger::LEVEL_INFO));
    }

    public function testGetMonologLevelTrace(): void
    {
        $this->assertSame(100, $this->invokeGetMonologLevel(Logger::LEVEL_TRACE));
    }

    public function testGetMonologLevelProfile(): void
    {
        $this->assertSame(100, $this->invokeGetMonologLevel(Logger::LEVEL_PROFILE));
    }

    public function testGetMonologLevelDefaultFallback(): void
    {
        $this->assertSame(200, $this->invokeGetMonologLevel(999));
    }

    private function invokeExtractMessage(mixed $text): string
    {
        $method = $this->reflection->getMethod('extractMessage');

        /** @var string */
        return $method->invoke($this->target, $text);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function invokeFormatTrace(\Throwable $exception): array
    {
        $method = $this->reflection->getMethod('formatTrace');

        /** @var array<int, array<string, mixed>> */
        return $method->invoke($this->target, $exception);
    }

    /**
     * @return array<string, mixed>
     */
    private function invokeExtractExceptionContext(\Throwable $exception): array
    {
        $method = $this->reflection->getMethod('extractExceptionContext');

        /** @var array<string, mixed> */
        return $method->invoke($this->target, $exception);
    }

    private function invokeGetMonologLevel(int $level): int
    {
        $method = $this->reflection->getMethod('getMonologLevel');

        /** @var int */
        return $method->invoke($this->target, $level);
    }
}
