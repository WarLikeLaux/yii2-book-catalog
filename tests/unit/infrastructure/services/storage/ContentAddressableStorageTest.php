<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\storage;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\OperationFailedException;
use app\domain\exceptions\ValidationException;
use app\domain\values\FileContent;
use app\domain\values\FileKey;
use app\infrastructure\services\storage\ContentAddressableStorage;
use app\infrastructure\services\storage\StorageConfig;
use Codeception\Test\Unit;
use tests\_support\RemovesDirectoriesTrait;

final class ContentAddressableStorageTest extends Unit
{
    use RemovesDirectoriesTrait;

    private const string TEST_CONTENT = 'test content';
    private const string SAME_CONTENT = 'same content';
    private const string ORIGINAL_CONTENT = 'original content';
    private const string EXTENSION = 'txt';
    private const string VALID_HASH = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
    private const string UPLOAD_URL = '/uploads';


    private string $tempDir;
    private ContentAddressableStorage $storage;

    protected function _before(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cas-test-' . uniqid('', true);

        if (!mkdir($this->tempDir, 0777, true) && !is_dir($this->tempDir)) {
            $this->fail('Failed to create temp directory');
        }

        $this->storage = new ContentAddressableStorage(
            new StorageConfig($this->tempDir, self::UPLOAD_URL),
        );
    }

    protected function _after(): void
    {
        if (!isset($this->tempDir) || !is_dir($this->tempDir)) {
            return;
        }

        $this->removeDir($this->tempDir);
    }

    public function testSaveCreatesFileWithCorrectPath(): void
    {
        $content = $this->createFileContent(self::TEST_CONTENT);
        $key = $content->computeKey();

        $returnedKey = $this->storage->save($content);

        $this->assertTrue($returnedKey->equals($key));

        $expectedPath = $this->tempDir . DIRECTORY_SEPARATOR . $key->getExtendedPath(self::EXTENSION);
        $this->assertFileExists($expectedPath);
        $this->assertSame(self::TEST_CONTENT, file_get_contents($expectedPath));

        unset($content);
    }

    public function testSaveIsIdempotent(): void
    {
        $content1 = $this->createFileContent(self::SAME_CONTENT);
        $content2 = $this->createFileContent(self::SAME_CONTENT);

        $key1 = $this->storage->save($content1);
        $key2 = $this->storage->save($content2);

        $this->assertTrue($key1->equals($key2));
    }

    public function testSaveDoesNotOverwriteExistingFile(): void
    {
        $content1 = $this->createFileContent(self::ORIGINAL_CONTENT);
        $key = $this->storage->save($content1);

        $path = $this->tempDir . DIRECTORY_SEPARATOR . $key->getExtendedPath(self::EXTENSION);
        $pastMtime = time() - 10;
        touch($path, $pastMtime);
        clearstatcache();

        $content2 = $this->createFileContent(self::ORIGINAL_CONTENT);
        $this->storage->save($content2);

        clearstatcache();
        $this->assertLessThanOrEqual($pastMtime + 1, filemtime($path));
        $this->assertSame(self::ORIGINAL_CONTENT, file_get_contents($path));
    }

    public function testExistsReturnsTrueForExistingFile(): void
    {
        $content = $this->createFileContent(self::TEST_CONTENT);
        $key = $this->storage->save($content);

        $this->assertTrue($this->storage->exists($key, self::EXTENSION));
    }

    public function testExistsReturnsFalseForNonExistingFile(): void
    {
        $key = new FileKey(self::VALID_HASH);

        $this->assertFalse($this->storage->exists($key, self::EXTENSION));
    }

    public function testGetUrlReturnsCorrectFormat(): void
    {
        $key = new FileKey(self::VALID_HASH);

        $url = $this->storage->getUrl($key, 'jpg');

        $this->assertSame(
            '/uploads/e3/b0/e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855.jpg',
            $url,
        );
    }

    public function testGetModificationTimeReturnsTimestamp(): void
    {
        $content = $this->createFileContent(self::TEST_CONTENT);
        $key = $this->storage->save($content);

        $mtime = $this->storage->getModificationTime($key, self::EXTENSION);

        $this->assertIsInt($mtime);
        $this->assertLessThanOrEqual(time(), $mtime);
        $this->assertGreaterThan(time() - 60, $mtime);
    }

    public function testGetModificationTimeThrowsOnMissingFile(): void
    {
        $key = new FileKey(str_repeat('f', 64));

        $this->expectException(OperationFailedException::class);
        $this->expectExceptionMessage('file.error.storage_operation_failed');

        $this->storage->getModificationTime($key, self::EXTENSION);
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
        $nonExistentPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nonexistent_' . uniqid('', true);
        $storage = new ContentAddressableStorage(
            new StorageConfig($nonExistentPath, self::UPLOAD_URL),
        );

        $keys = iterator_to_array($storage->listAllKeys());

        $this->assertEmpty($keys);
    }

    public function testListAllKeysIgnoresInvalidFiles(): void
    {
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'ab' . DIRECTORY_SEPARATOR . 'cd', 0777, true);
        file_put_contents($this->tempDir . DIRECTORY_SEPARATOR . 'ab' . DIRECTORY_SEPARATOR . 'cd' . DIRECTORY_SEPARATOR . 'invalid.txt', 'not a hash');

        $content = $this->createFileContent('valid content');
        $key = $this->storage->save($content);

        $keys = iterator_to_array($this->storage->listAllKeys());
        $keyValues = array_map(static fn(FileKey $k): string => $k->value, $keys);

        $this->assertCount(1, $keys);
        $this->assertContains($key->value, $keyValues);
    }

    public function testListAllKeysFiltersDuplicates(): void
    {
        $hash = 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890';
        $dir = $this->tempDir . DIRECTORY_SEPARATOR . 'ab' . DIRECTORY_SEPARATOR . 'cd';
        mkdir($dir, 0777, true);

        file_put_contents($dir . DIRECTORY_SEPARATOR . $hash . '.txt', 'content');
        file_put_contents($dir . DIRECTORY_SEPARATOR . $hash . '.json', 'content');

        $keys = iterator_to_array($this->storage->listAllKeys());

        $this->assertCount(1, $keys);
        $this->assertSame($hash, $keys[0]->value);
    }

    public function testValidateExtensionAllowEmpty(): void
    {
        $content = $this->createFileContent(self::TEST_CONTENT);
        $key = $this->storage->save($content);

        // Should not throw exception
        $this->assertFalse($this->storage->exists($key, ''));
    }

    public function testValidateExtensionThrowsException(): void
    {
        $key = new FileKey(self::VALID_HASH);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::FileKeyInvalidFormat->value);

        $this->storage->exists($key, '../invalid');
    }

    public function testDeleteRemovesFile(): void
    {
        $content = $this->createFileContent(self::TEST_CONTENT);
        $key = $this->storage->save($content);

        $this->assertTrue($this->storage->exists($key, self::EXTENSION));

        $this->storage->delete($key, self::EXTENSION);

        $this->assertFalse($this->storage->exists($key, self::EXTENSION));
    }

    public function testDeleteIgnoresNonExistingFile(): void
    {
        $key = new FileKey(self::VALID_HASH);

        $this->storage->delete($key, self::EXTENSION);

        $this->assertFalse($this->storage->exists($key, self::EXTENSION));
    }

    public function testDeleteCleansUpEmptyDirectories(): void
    {
        $content = $this->createFileContent(self::TEST_CONTENT);
        $key = $this->storage->save($content);

        $dir1 = $this->tempDir . DIRECTORY_SEPARATOR . substr($key->value, 0, 2);
        $dir2 = $dir1 . DIRECTORY_SEPARATOR . substr($key->value, 2, 2);

        $this->assertDirectoryExists($dir2);

        $this->storage->delete($key, self::EXTENSION);

        $this->assertDirectoryDoesNotExist($dir2);
        $this->assertDirectoryDoesNotExist($dir1);
    }

    private function createFileContent(string $textContent): FileContent
    {
        $stream = fopen('php://memory', 'r+b');

        if ($stream === false) {
            $this->fail('Failed to open memory stream');
        }

        if (fwrite($stream, $textContent) === false) {
            $this->fail('Failed to write to memory stream');
        }

        rewind($stream);

        return new FileContent($stream, self::EXTENSION, 'text/plain');
    }
}
