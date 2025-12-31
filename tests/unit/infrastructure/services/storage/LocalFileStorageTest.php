<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\storage;

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

        $this->storage = new LocalFileStorage($this->tempDir, '/uploads');
    }

    protected function _after(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testSaveCreatesFileAndReturnsUrl(): void
    {
        $tempFile = $this->createTempFile('test content');

        $url = $this->storage->save($tempFile, 'txt');

        $this->assertStringStartsWith('/uploads/', $url);
        $this->assertStringEndsWith('.txt', $url);

        $savedFile = $this->tempDir . '/' . basename($url);
        $this->assertFileExists($savedFile);
        $this->assertSame('test content', file_get_contents($savedFile));
    }

    public function testDeleteRemovesFile(): void
    {
        $tempFile = $this->createTempFile('content to delete');
        $url = $this->storage->save($tempFile, 'txt');

        $savedFile = $this->tempDir . '/' . basename($url);
        $this->assertFileExists($savedFile);

        $this->storage->delete($url);

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
