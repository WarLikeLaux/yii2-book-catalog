<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

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
            static fn(string $path): string|false => $path === '' ? '' : 'application/x-finfo',
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
            static fn(string $path): string|false => $path === '' ? '' : 'application/x-finfo',
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
            static fn(string $path): string|false => $path === '' ? '' : 'application/x-finfo',
        );

        $result = $detector->detect('path');

        $this->assertSame('application/octet-stream', $result);
    }
}
