<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\services\FinfoFunctions;
use app\domain\services\NativeMimeTypeDetector;
use Codeception\Test\Unit;

final class NativeMimeTypeDetectorTest extends Unit
{
    public function testDetectUsesMimeContentTypeWhenAvailable(): void
    {
        $detector = new NativeMimeTypeDetector(
            static fn(): bool => true,
            static fn(string $path): string|false => $path === '' ? '' : 'application/x-mime',
            static fn(): bool => true,
            null,
        );

        $result = $detector->detect('path');

        $this->assertSame('application/x-mime', $result);
    }

    public function testDetectUsesFinfoWhenMimeContentTypeUnavailable(): void
    {
        $detector = new NativeMimeTypeDetector(
            static fn(): bool => false,
            static fn(string $path): string|false => $path === '' ? '' : 'application/x-mime',
            static fn(): bool => true,
            new FinfoFunctions(
                static fn(int $option): mixed => $option === 0 ? false : 'handle',
                static fn(mixed $finfo, string $path): string|false => $finfo === 'handle' && $path !== '' ? 'application/x-finfo' : false,
                static fn(mixed $finfo): bool => $finfo === 'handle',
            ),
        );

        $result = $detector->detect('path');

        $this->assertSame('application/x-finfo', $result);
    }

    public function testDetectUsesDefaultFinfoImplementation(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'mime-detector-');
        $filePath = $tempFile !== false ? $tempFile : '';

        if ($filePath === '') {
            $this->fail('Не удалось создать временный файл.');
        }

        file_put_contents($filePath, 'content');

        try {
            $detector = new NativeMimeTypeDetector(
                static fn(): bool => false,
                static fn(string $path): string|false => $path === '' ? '' : 'application/x-mime',
                static fn(): bool => true,
                null,
            );

            $result = $detector->detect($filePath);

            $this->assertNotSame('', $result);
        } finally {
            unlink($filePath);
        }
    }

    public function testDetectFallsBackToDefaultWhenNoDetectorsAvailable(): void
    {
        $detector = new NativeMimeTypeDetector(
            static fn(): bool => false,
            static fn(string $path): string|false => $path === '' ? '' : 'application/x-mime',
            static fn(): bool => false,
            null,
        );

        $result = $detector->detect('path');

        $this->assertSame('application/octet-stream', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDetectReturnsDefaultWhenFinfoOpenFails(): void
    {
        $fileCalled = false;
        $closeCalled = false;
        $finfoFunctions = new FinfoFunctions(
            static fn(int $option): mixed => $option === 0 && false,
            static function (mixed $finfo, string $path) use (&$fileCalled): string|false {
                $fileCalled = true;
                return $finfo === null ? false : ($path === '' ? '' : 'application/x-finfo');
            },
            static function (mixed $finfo) use (&$closeCalled): bool {
                $closeCalled = true;
                return $finfo !== null;
            },
        );

        $detector = new NativeMimeTypeDetector(
            static fn(): bool => false,
            static fn(string $path): string|false => $path === '' ? '' : 'application/x-mime',
            static fn(): bool => true,
            $finfoFunctions,
        );

        $result = $detector->detect('path');

        $this->assertSame('application/octet-stream', $result);
        $this->assertFalse($fileCalled);
        $this->assertFalse($closeCalled);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDetectClosesFinfoHandle(): void
    {
        $fileCalled = false;
        $closeCalled = false;
        $finfoFunctions = new FinfoFunctions(
            static fn(int $option): mixed => $option === 0 ? false : 'handle',
            static function (mixed $finfo, string $path) use (&$fileCalled): string|false {
                $fileCalled = true;
                return $finfo === 'handle' && $path !== '' ? 'application/x-finfo' : false;
            },
            static function (mixed $finfo) use (&$closeCalled): bool {
                $closeCalled = true;
                return $finfo === 'handle';
            },
        );

        $detector = new NativeMimeTypeDetector(
            static fn(): bool => false,
            static fn(string $path): string|false => $path === '' ? '' : 'application/x-mime',
            static fn(): bool => true,
            $finfoFunctions,
        );

        $result = $detector->detect('path');

        $this->assertSame('application/x-finfo', $result);
        $this->assertTrue($fileCalled);
        $this->assertTrue($closeCalled);
    }
}
