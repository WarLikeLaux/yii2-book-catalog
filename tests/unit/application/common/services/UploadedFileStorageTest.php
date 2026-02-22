<?php

declare(strict_types=1);

namespace tests\unit\application\common\services;

use app\application\common\dto\UploadedFilePayload;
use app\application\common\exceptions\ApplicationException;
use app\application\common\services\UploadedFileStorage;
use app\application\ports\ContentStorageInterface;
use app\domain\values\FileContent;
use app\domain\values\FileKey;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class UploadedFileStorageTest extends Unit
{
    private ContentStorageInterface&MockObject $contentStorage;
    private UploadedFileStorage $service;

    protected function _before(): void
    {
        $this->contentStorage = $this->createMock(ContentStorageInterface::class);
        $this->service = new UploadedFileStorage($this->contentStorage);
    }

    public function testStoreReturnsExtendedPath(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload-');

        if ($tempFile === false) {
            $this->fail('Failed to create temp file');
        }

        $filePath = $tempFile;
        file_put_contents($filePath, 'content');

        $payload = new UploadedFilePayload($filePath, 'txt', 'text/plain');
        $hash = str_repeat('a', 64);
        $fileKey = new FileKey($hash);

        $this->contentStorage->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(FileContent::class))
            ->willReturn($fileKey);

        $result = $this->service->store($payload);

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->assertSame('aa/aa/' . $hash . '.txt', $result);
    }

    public function testStoreThrowsApplicationExceptionWhenFileMissing(): void
    {
        $payload = new UploadedFilePayload('/tmp/missing-file-' . uniqid('', true), 'txt', 'text/plain');

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('file.error.not_found');

        $this->service->store($payload);
    }

    public function testStoreThrowsApplicationExceptionWhenPathIsDirectory(): void
    {
        $tempDir = sys_get_temp_dir() . '/upload-storage-dir-' . uniqid('', true);
        mkdir($tempDir, 0777, true);

        try {
            $payload = new UploadedFilePayload($tempDir, 'txt', 'text/plain');

            $this->expectException(ApplicationException::class);
            $this->expectExceptionMessage('file.error.not_found');

            $this->service->store($payload);
        } finally {
            rmdir($tempDir);
        }
    }

    public function testStoreUsesPathinfoExtensionWhenExtensionEmpty(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload-');

        if ($tempFile === false) {
            $this->fail('Failed to create temp file');
        }

        $filePath = $tempFile . '.jpg';
        rename($tempFile, $filePath);
        file_put_contents($filePath, 'content');

        $payload = new UploadedFilePayload($filePath, '', 'image/jpeg');
        $hash = str_repeat('b', 64);
        $fileKey = new FileKey($hash);

        $this->contentStorage->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (FileContent $content): bool => $content->extension === 'jpg'))
            ->willReturn($fileKey);

        $result = $this->service->store($payload);

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->assertSame('bb/bb/' . $hash . '.jpg', $result);
    }
}
