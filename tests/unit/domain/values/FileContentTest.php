<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\DomainException;
use app\domain\exceptions\ValidationException;
use app\domain\values\FileContent;
use app\domain\values\FileKey;
use Codeception\Test\Unit;
use tests\_support\RemovesDirectoriesTrait;

final class FileContentTest extends Unit
{
    use RemovesDirectoriesTrait;

    private const string MEMORY_STREAM = 'php://memory';
    private const string MIME_TYPE = 'text/plain';
    private const string EXTENSION = 'txt';

    private string $tempDir;

    protected function _before(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/file-content-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function _after(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testCanCreateWithValidStream(): void
    {
        $stream = fopen(self::MEMORY_STREAM, 'r+b');
        fwrite($stream, 'test content');
        rewind($stream);

        $content = new FileContent($stream, self::EXTENSION, self::MIME_TYPE);

        $this->assertSame(self::EXTENSION, $content->extension);
        $this->assertSame(self::MIME_TYPE, $content->mimeType);
        $this->assertSame($stream, $content->getStream());
    }

    public function testThrowsOnInvalidResource(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('file.error.content_invalid_stream');

        $_ = new FileContent('not a stream', self::EXTENSION, self::MIME_TYPE);
    }

    public function testThrowsOnClosedStream(): void
    {
        $stream = fopen(self::MEMORY_STREAM, 'r+b');
        fclose($stream);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('file.error.content_invalid_stream');

        $_ = new FileContent($stream, self::EXTENSION, self::MIME_TYPE);
    }

    public function testFromPathCreatesValidContent(): void
    {
        $filePath = $this->tempDir . '/test.txt';
        file_put_contents($filePath, 'test content');

        $content = FileContent::fromPath($filePath, null, self::MIME_TYPE);

        $this->assertSame(self::EXTENSION, $content->extension);
        $this->assertIsResource($content->getStream());
    }

    public function testFromPathThrowsOnMissingFile(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::FileNotFound->value);

        FileContent::fromPath($this->tempDir . '/non-existent.txt', null, self::MIME_TYPE);
    }

    public function testFromPathThrowsOnDirectory(): void
    {
        $dirPath = $this->tempDir . '/dir';
        mkdir($dirPath, 0777, true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::FileNotFound->value);

        FileContent::fromPath($dirPath, null, self::MIME_TYPE);
    }

    public function testFromPathUsesProvidedMimeType(): void
    {
        $filePath = $this->tempDir . '/test.txt';
        file_put_contents($filePath, 'plain text content');

        $content = FileContent::fromPath($filePath, null, self::MIME_TYPE);

        $this->assertSame(self::MIME_TYPE, $content->mimeType);
    }

    public function testFromPathThrowsOnUnreadableFile(): void
    {
        $filePath = $this->tempDir . '/unreadable.txt';
        file_put_contents($filePath, 'content');
        chmod($filePath, 0000);

        if (is_readable($filePath)) {
            chmod($filePath, 0644);
            $this->markTestSkipped('Файл остался читаемым после chmod.');
        }

        try {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage(DomainErrorCode::FileNotFound->value);

            FileContent::fromPath($filePath, null, self::MIME_TYPE);
        } finally {
            chmod($filePath, 0644);
        }
    }

    public function testComputeKeyReturnsFileKey(): void
    {
        $textContent = 'test content for hashing';
        $stream = fopen(self::MEMORY_STREAM, 'r+b');
        fwrite($stream, $textContent);
        rewind($stream);

        $content = new FileContent($stream, self::EXTENSION, self::MIME_TYPE);
        $key = $content->computeKey();

        $this->assertInstanceOf(FileKey::class, $key);
        $this->assertSame(hash('sha256', $textContent), $key->value);
    }

    public function testComputeKeyIsDeterministic(): void
    {
        $textContent = 'same content';

        $stream1 = fopen(self::MEMORY_STREAM, 'r+b');
        fwrite($stream1, $textContent);
        rewind($stream1);

        $stream2 = fopen(self::MEMORY_STREAM, 'r+b');
        fwrite($stream2, $textContent);
        rewind($stream2);

        $content1 = new FileContent($stream1, self::EXTENSION, self::MIME_TYPE);
        $content2 = new FileContent($stream2, self::EXTENSION, self::MIME_TYPE);

        $this->assertTrue($content1->computeKey()->equals($content2->computeKey()));
    }

    public function testDestructorClosesStream(): void
    {
        $stream = fopen(self::MEMORY_STREAM, 'r+b');
        fwrite($stream, 'content');
        rewind($stream);

        $content = new FileContent($stream, self::EXTENSION, self::MIME_TYPE);
        unset($content);
        gc_collect_cycles();

        $this->assertFalse(is_resource($stream));
    }
}
