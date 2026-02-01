<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\adapters;

use app\application\common\dto\UploadedFilePayload;
use app\infrastructure\services\NativeMimeTypeDetector;
use app\presentation\common\adapters\UploadedFileAdapter;
use Codeception\Test\Unit;
use tests\_support\RemovesDirectoriesTrait;
use yii\web\UploadedFile;

final class UploadedFileAdapterTest extends Unit
{
    use RemovesDirectoriesTrait;

    private string $tempDir;
    private UploadedFileAdapter $adapter;

    protected function _before(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/adapter-test-' . uniqid('', true);

        if (!mkdir($this->tempDir, 0777, true) && !is_dir($this->tempDir)) {
            $this->fail('Failed to create temp dir: ' . $this->tempDir);
        }

        $this->adapter = new UploadedFileAdapter(new NativeMimeTypeDetector());
    }

    protected function _after(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testToPayloadCreatesValidPayload(): void
    {
        $tempFile = $this->tempDir . '/test-upload.txt';
        file_put_contents($tempFile, 'uploaded content');

        $uploadedFile = $this->createUploadedFile($tempFile, 'test.txt');

        $payload = $this->adapter->toPayload($uploadedFile);

        $this->assertInstanceOf(UploadedFilePayload::class, $payload);
        $this->assertSame('txt', $payload->extension);
    }

    public function testToPayloadPreservesTempPath(): void
    {
        $originalContent = 'this is the uploaded file content';
        $tempFile = $this->tempDir . '/test-upload.txt';
        file_put_contents($tempFile, $originalContent);

        $uploadedFile = $this->createUploadedFile($tempFile, 'document.txt');

        $payload = $this->adapter->toPayload($uploadedFile);

        $this->assertSame($tempFile, $payload->path);
    }

    public function testToPayloadUsesOriginalExtensionEvenIfTempFileHasNone(): void
    {
        $tempFile = $this->tempDir . '/phpTMP123';
        file_put_contents($tempFile, 'content');

        $uploadedFile = $this->createUploadedFile($tempFile, 'document.pdf');

        $payload = $this->adapter->toPayload($uploadedFile);

        $this->assertSame('pdf', $payload->extension);
    }

    private function createUploadedFile(string $tempPath, string $originalName): UploadedFile
    {
        return new UploadedFile([
            'name' => $originalName,
            'tempName' => $tempPath,
            'type' => 'text/plain',
            'size' => filesize($tempPath),
            'error' => UPLOAD_ERR_OK,
        ]);
    }
}
