<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\storage;

use app\application\common\dto\TemporaryFile;
use app\domain\values\StoredFileReference;
use app\infrastructure\services\storage\LocalFileStorage;
use app\infrastructure\services\storage\StorageConfig;
use Codeception\Test\Unit;

final class LocalFileStorageTest extends Unit
{
    private string $tempDir;
    private LocalFileStorage $storage;

    protected function _before(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/test-storage-' . uniqid();
        mkdir($this->tempDir, 0777, true);

        $this->storage = new LocalFileStorage(
            new StorageConfig(
                $this->tempDir,
                '/uploads',
                $this->tempDir . '/tmp',
                '/tmp_uploads',
            ),
        );
    }

    protected function _after(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testSaveTemporaryCreatesFileAndReturnsUrl(): void
    {
        $tempFile = $this->createTempFile('test content');

        $file = $this->storage->saveTemporary($tempFile, 'txt');

        $this->assertStringEndsWith('.txt', $file->filename);
        $this->assertStringContainsString('test-storage', $file->tempPath);

        $this->assertFileExists($file->tempPath);
        $this->assertSame('test content', file_get_contents($file->tempPath));
    }

    public function testMoveToPermanentMovesFileAndReturnsUrl(): void
    {
        $tempFile = $this->createTempFile('content to delete');
        $file = $this->storage->saveTemporary($tempFile, 'txt');

        $permanentRef = $this->storage->moveToPermanent($file);

        $this->assertInstanceOf(StoredFileReference::class, $permanentRef);
        $savedFile = $this->tempDir . '/' . (string)$permanentRef;
        $this->assertFileExists($savedFile);
        $this->assertFileDoesNotExist($file->tempPath);
    }

    public function testDeleteTemporaryRemovesFile(): void
    {
        $tempFile = $this->createTempFile('content to delete');
        $file = $this->storage->saveTemporary($tempFile, 'txt');

        $this->storage->deleteTemporary($file);

        $this->assertFileDoesNotExist($file->tempPath);
    }

    public function testDeleteTemporaryIgnoresMissingFile(): void
    {
        $file = new TemporaryFile(
            $this->tempDir . '/missing.txt',
            'missing.txt',
        );

        $this->storage->deleteTemporary($file);

        $this->assertTrue(true);
    }

    public function testDeleteRemovesFile(): void
    {
        $tempFile = $this->createTempFile('content to delete');
        $file = $this->storage->saveTemporary($tempFile, 'txt');
        $ref = $this->storage->moveToPermanent($file);

        $savedFile = $this->tempDir . '/' . (string)$ref;
        $this->assertFileExists($savedFile);

        $this->storage->delete((string)$ref);

        $this->assertFileDoesNotExist($savedFile);
    }

    public function testDeleteIgnoresNonExistentFile(): void
    {
        $this->storage->delete('/uploads/non-existent-file.txt');

        $this->assertTrue(true);
    }

    private function createTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($path, $content);
        return $path;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
