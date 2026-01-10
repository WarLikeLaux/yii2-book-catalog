<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\adapters;

use app\domain\exceptions\OperationFailedException;
use app\domain\services\NativeMimeTypeDetector;
use app\domain\values\FileContent;
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
        $this->tempDir = sys_get_temp_dir() . '/adapter-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->adapter = new UploadedFileAdapter(new NativeMimeTypeDetector());
    }

    protected function _after(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testToFileContentCreatesValidFileContent(): void
    {
        $tempFile = $this->tempDir . '/test-upload.txt';
        file_put_contents($tempFile, 'uploaded content');

        $uploadedFile = $this->createUploadedFile($tempFile, 'test.txt');

        $content = $this->adapter->toFileContent($uploadedFile);

        $this->assertInstanceOf(FileContent::class, $content);
        $this->assertSame('txt', $content->extension);
    }

    public function testToFileContentPreservesStreamContent(): void
    {
        $originalContent = 'this is the uploaded file content';
        $tempFile = $this->tempDir . '/test-upload.txt';
        file_put_contents($tempFile, $originalContent);

        $uploadedFile = $this->createUploadedFile($tempFile, 'document.txt');

        $content = $this->adapter->toFileContent($uploadedFile);
        $stream = $content->getStream();

        $this->assertSame($originalContent, stream_get_contents($stream));
    }

    public function testToFileContentUsesOriginalExtensionEvenIfTempFileHasNone(): void
    {
        $tempFile = $this->tempDir . '/phpTMP123';
        file_put_contents($tempFile, 'content');

        $uploadedFile = $this->createUploadedFile($tempFile, 'document.pdf');

        $content = $this->adapter->toFileContent($uploadedFile);

        $this->assertSame('pdf', $content->extension);
    }

    public function testToFileContentThrowsOperationFailedWhenTempFileNotFound(): void
    {
        $nonExistentPath = $this->tempDir . '/non-existent-file.tmp';

        $uploadedFile = new UploadedFile([
            'name' => 'test.txt',
            'tempName' => $nonExistentPath,
            'type' => 'text/plain',
            'size' => 0,
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->expectException(OperationFailedException::class);

        $this->adapter->toFileContent($uploadedFile);
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
