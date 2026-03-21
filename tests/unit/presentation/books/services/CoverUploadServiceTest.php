<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\services;

use app\application\common\dto\UploadedFilePayload;
use app\application\common\services\UploadedFileStorage;
use app\presentation\books\services\CoverUploadService;
use app\presentation\common\adapters\UploadedFileAdapter;
use PHPUnit\Framework\TestCase;
use yii\web\UploadedFile;

final class CoverUploadServiceTest extends TestCase
{
    public function testUploadReturnsStoredPath(): void
    {
        $file = new UploadedFile(['name' => 'cover.jpg', 'tempName' => '/tmp/cover.jpg']);
        $payload = new UploadedFilePayload('/tmp/cover.jpg', 'jpg', 'image/jpeg');

        $adapter = $this->createStub(UploadedFileAdapter::class);
        $adapter->method('toPayload')->willReturn($payload);

        $storage = $this->createStub(UploadedFileStorage::class);
        $storage->method('store')->willReturn('covers/abc123.jpg');

        $service = new CoverUploadService($storage, $adapter);

        $this->assertSame('covers/abc123.jpg', $service->upload($file));
    }
}
