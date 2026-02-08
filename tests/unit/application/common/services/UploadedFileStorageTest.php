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
}
