<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\storage;

use app\application\common\dto\TemporaryFile;
use app\infrastructure\services\storage\LocalFileStorage;
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
            $this->tempDir,
            '/uploads',
            $this->tempDir . '/tmp'
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

        $this->assertStringStartsWith('/uploads/', $file->url);
        $this->assertStringEndsWith('.txt', $file->url);

        $this->assertFileExists($file->tempPath);
        $this->assertSame('test content', file_get_contents($file->tempPath));
    }

    public function testMoveToPermanentMovesFile(): void
    {
        $tempFile = $this->createTempFile('content to delete');
        $file = $this->storage->saveTemporary($tempFile, 'txt');

        $this->storage->moveToPermanent($file);

        $savedFile = $this->tempDir . '/' . basename($file->url);
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
            '/uploads/missing.txt'
        );

        $this->storage->deleteTemporary($file);

        $this->assertTrue(true);
    }

    public function testDeleteRemovesFile(): void
    {
        $tempFile = $this->createTempFile('content to delete');
        $file = $this->storage->saveTemporary($tempFile, 'txt');
        $this->storage->moveToPermanent($file);

        $savedFile = $this->tempDir . '/' . basename($file->url);
        $this->assertFileExists($savedFile);

        $this->storage->delete($file->url);

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
