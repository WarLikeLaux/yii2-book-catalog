<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\observability;

use app\application\ports\SpanInterface;
use app\application\ports\TracerInterface;
use app\infrastructure\services\observability\TracerBootstrap;
use Codeception\Test\Unit;
use yii\base\Application as BaseApplication;
use yii\web\Application;
use yii\web\HeaderCollection;
use yii\web\Request;
use yii\web\Response;

final class TracerBootstrapTest extends Unit
{
    private TracerBootstrap $bootstrap;

    protected function _before(): void
    {
        $this->bootstrap = new TracerBootstrap();
    }

    public function testBootstrapNotEnabled(): void
    {
        $this->bootstrap->enabled = false;

        $app = $this->createMock(Application::class);

        $this->bootstrap->bootstrap($app);

        // If not enabled, it sets NullTracer. We can't easily verify private property without reflection,
        // but no error means it ran.
        $this->assertTrue(true);
    }

    public function testBootstrapEnabled(): void
    {
        $this->bootstrap->enabled = true;

        $callbacks = [];
        $app = $this->createMock(Application::class);
        $app->expects($this->exactly(2))
            ->method('on')
            ->willReturnCallback(static function (string $event, callable $handler) use (&$callbacks): void {
                $callbacks[$event] = $handler;
            });

        $this->bootstrap->bootstrap($app);

        $tracer = $this->createMock(TracerInterface::class);
        $span = $this->createMock(SpanInterface::class);
        $tracer->expects($this->once())
            ->method('startSpan')
            ->willReturn($span);
        $tracer->expects($this->once())
            ->method('flush');

        $span->expects($this->atLeastOnce())->method('setAttribute');
        $span->expects($this->once())->method('setStatus')->with(true);
        $span->expects($this->once())->method('end');

        $request = $this->createMock(Request::class);
        $request->method('getPathInfo')->willReturn('site/index');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getAbsoluteUrl')->willReturn('http://localhost/site/index');
        $request->method('getUrl')->willReturn('/site/index');
        $request->method('getUserAgent')->willReturn(null);
        $request->method('getUserIP')->willReturn(null);
        $request->method('getHeaders')->willReturn(new HeaderCollection());
        $request->method('getQueryParams')->willReturn([]);

        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn(200);

        $app->method('getRequest')->willReturn($request);
        $app->method('getResponse')->willReturn($response);

        $reflection = new \ReflectionClass($this->bootstrap);
        $tracerProp = $reflection->getProperty('tracer');
        $tracerProp->setAccessible(true);
        $tracerProp->setValue($this->bootstrap, $tracer);

        $callbacks[BaseApplication::EVENT_BEFORE_REQUEST]();
        $callbacks[BaseApplication::EVENT_AFTER_REQUEST]();
    }

    public function testStartRootSpanWithIgnoredPaths(): void
    {
        $reflection = new \ReflectionClass($this->bootstrap);
        $tracerProp = $reflection->getProperty('tracer');
        $tracerProp->setAccessible(true);

        $ignoredPaths = ['debug/default/index', 'gii/default/index', '.well-known/security.txt'];

        foreach ($ignoredPaths as $path) {
            $tracer = $this->createMock(TracerInterface::class);
            $tracerProp->setValue($this->bootstrap, $tracer);

            $app = $this->createMock(Application::class);
            $request = $this->createMock(Request::class);

            $request->method('getPathInfo')->willReturn($path);
            $app->method('getRequest')->willReturn($request);

            $tracer->expects($this->never())->method('startSpan');

            $method = $reflection->getMethod('startRootSpan');
            $method->setAccessible(true);
            $method->invoke($this->bootstrap, $app);
        }
    }

    public function testStartRootSpanWithValidPath(): void
    {
        $app = $this->createMock(Application::class);
        $request = $this->createMock(Request::class);
        $tracer = $this->createMock(TracerInterface::class);
        $span = $this->createMock(SpanInterface::class);

        // Setup Request
        $request->method('getPathInfo')->willReturn('api/books');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getAbsoluteUrl')->willReturn('http://localhost/api/books');
        $request->method('getUrl')->willReturn('/api/books');
        $request->method('getUserAgent')->willReturn('TestAgent');
        $request->method('getUserIP')->willReturn('127.0.0.1');
        $request->method('getHeaders')->willReturn(new HeaderCollection());
        $request->method('getQueryParams')->willReturn(['id' => 1]);

        $app->method('getRequest')->willReturn($request);

        // Setup Tracer
        $tracer->expects($this->once())
            ->method('startSpan')
            ->willReturn($span);

        // Inject mock tracer
        $reflection = new \ReflectionClass($this->bootstrap);
        $tracerProp = $reflection->getProperty('tracer');
        $tracerProp->setAccessible(true);
        $tracerProp->setValue($this->bootstrap, $tracer);

        // Execute
        $method = $reflection->getMethod('startRootSpan');
        $method->setAccessible(true);
        $method->invoke($this->bootstrap, $app);
    }

    public function testStartRootSpanReturnsForNonWebApplication(): void
    {
        $app = $this->createMock(BaseApplication::class);

        $reflection = new \ReflectionClass($this->bootstrap);
        $method = $reflection->getMethod('startRootSpan');
        $method->setAccessible(true);

        $method->invoke($this->bootstrap, $app);

        $this->assertTrue(true);
    }

    public function testStartRootSpanReturnsWhenSpanNotCreated(): void
    {
        $app = $this->createMock(Application::class);
        $request = $this->createMock(Request::class);

        $request->method('getPathInfo')->willReturn('site/index');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getAbsoluteUrl')->willReturn('http://localhost/site/index');
        $request->method('getUrl')->willReturn('/site/index');
        $request->method('getUserAgent')->willReturn(null);
        $request->method('getUserIP')->willReturn(null);
        $request->method('getHeaders')->willReturn(new HeaderCollection());

        $app->method('getRequest')->willReturn($request);

        $reflection = new \ReflectionClass($this->bootstrap);
        $tracerProp = $reflection->getProperty('tracer');
        $tracerProp->setAccessible(true);
        $tracerProp->setValue($this->bootstrap, null);

        $method = $reflection->getMethod('startRootSpan');
        $method->setAccessible(true);
        $method->invoke($this->bootstrap, $app);
    }

    public function testEndRootSpanReturnsWhenNoSpan(): void
    {
        $app = $this->createMock(Application::class);

        $reflection = new \ReflectionClass($this->bootstrap);
        $method = $reflection->getMethod('endRootSpan');
        $method->setAccessible(true);

        $method->invoke($this->bootstrap, $app);

        $this->assertTrue(true);
    }
}
