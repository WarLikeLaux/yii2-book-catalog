<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\storage;

use app\domain\values\FileContent;
use app\domain\values\FileKey;
use app\infrastructure\services\storage\ContentAddressableStorage;
use app\infrastructure\services\storage\StorageConfig;
use Codeception\Test\Unit;

final class ContentAddressableStorageTest extends Unit
{
    private string $tempDir;
    private ContentAddressableStorage $storage;

    protected function _before(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/cas-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);

        $this->storage = new ContentAddressableStorage(
            new StorageConfig($this->tempDir, '/uploads'),
        );
    }

    protected function _after(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testSaveCreatesFileWithCorrectPath(): void
    {
        $content = $this->createFileContent('test content');
        $key = $content->computeKey();

        $returnedKey = $this->storage->save($content);

        $this->assertTrue($returnedKey->equals($key));

        $expectedPath = $this->tempDir . '/' . $key->getExtendedPath('txt');
        $this->assertFileExists($expectedPath);
        $this->assertSame('test content', file_get_contents($expectedPath));
    }

    public function testSaveIsIdempotent(): void
    {
        $content1 = $this->createFileContent('same content');
        $content2 = $this->createFileContent('same content');

        $key1 = $this->storage->save($content1);
        $key2 = $this->storage->save($content2);

        $this->assertTrue($key1->equals($key2));
    }

    public function testSaveDoesNotOverwriteExistingFile(): void
    {
        $content1 = $this->createFileContent('original content');
        $key = $this->storage->save($content1);

        $path = $this->tempDir . '/' . $key->getExtendedPath('txt');
        $originalMtime = filemtime($path);

        sleep(1);

        $content2 = $this->createFileContent('original content');
        $this->storage->save($content2);

        clearstatcache();
        $this->assertSame($originalMtime, filemtime($path));
    }

    public function testExistsReturnsTrueForExistingFile(): void
    {
        $content = $this->createFileContent('test content');
        $key = $this->storage->save($content);

        $this->assertTrue($this->storage->exists($key, 'txt'));
    }

    public function testExistsReturnsFalseForNonExistingFile(): void
    {
        $key = new FileKey('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855');

        $this->assertFalse($this->storage->exists($key, 'txt'));
    }

    public function testGetUrlReturnsCorrectFormat(): void
    {
        $key = new FileKey('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855');

        $url = $this->storage->getUrl($key, 'jpg');

        $this->assertSame(
            '/uploads/e3/b0/e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855.jpg',
            $url,
        );
    }

    public function testListAllKeysReturnsStoredKeys(): void
    {
        $content1 = $this->createFileContent('content one');
        $content2 = $this->createFileContent('content two');

        $key1 = $this->storage->save($content1);
        $key2 = $this->storage->save($content2);

        $keys = iterator_to_array($this->storage->listAllKeys());
        $keyValues = array_map(static fn(FileKey $k): string => $k->value, $keys);

        $this->assertContains($key1->value, $keyValues);
        $this->assertContains($key2->value, $keyValues);
    }

    public function testListAllKeysReturnsEmptyForNonExistingDirectory(): void
    {
        $storage = new ContentAddressableStorage(
            new StorageConfig('/non/existing/path', '/uploads'),
        );

        $keys = iterator_to_array($storage->listAllKeys());

        $this->assertEmpty($keys);
    }

    public function testListAllKeysIgnoresInvalidFiles(): void
    {
        mkdir($this->tempDir . '/ab/cd', 0777, true);
        file_put_contents($this->tempDir . '/ab/cd/invalid.txt', 'not a hash');

        $content = $this->createFileContent('valid content');
        $key = $this->storage->save($content);

        $keys = iterator_to_array($this->storage->listAllKeys());
        $keyValues = array_map(static fn(FileKey $k): string => $k->value, $keys);

        $this->assertCount(1, $keys);
        $this->assertContains($key->value, $keyValues);
    }

    public function testDeleteRemovesFile(): void
    {
        $content = $this->createFileContent('test content');
        $key = $this->storage->save($content);

        $this->assertTrue($this->storage->exists($key, 'txt'));

        $this->storage->delete($key, 'txt');

        $this->assertFalse($this->storage->exists($key, 'txt'));
    }

    public function testDeleteIgnoresNonExistingFile(): void
    {
        $key = new FileKey('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855');

        $this->storage->delete($key, 'txt');

        $this->assertFalse($this->storage->exists($key, 'txt'));
    }

    public function testDeleteCleansUpEmptyDirectories(): void
    {
        $content = $this->createFileContent('test content');
        $key = $this->storage->save($content);

        $dir1 = $this->tempDir . '/' . substr($key->value, 0, 2);
        $dir2 = $dir1 . '/' . substr($key->value, 2, 2);

        $this->assertDirectoryExists($dir2);

        $this->storage->delete($key, 'txt');

        $this->assertDirectoryDoesNotExist($dir2);
        $this->assertDirectoryDoesNotExist($dir1);
    }

    private function createFileContent(string $textContent): FileContent
    {
        $tempFile = $this->tempDir . '/source-' . uniqid() . '.txt';
        file_put_contents($tempFile, $textContent);

        return FileContent::fromPath($tempFile);
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
