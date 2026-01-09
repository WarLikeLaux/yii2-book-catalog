<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\exceptions\DomainException;
use app\domain\values\FileContent;
use app\domain\values\FileKey;
use Codeception\Test\Unit;
use RuntimeException;
use tests\_support\RemovesDirectoriesTrait;

final class FileContentTest extends Unit
{
    use RemovesDirectoriesTrait;

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
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, 'test content');
        rewind($stream);

        $content = new FileContent($stream, 'txt', 'text/plain');

        $this->assertSame('txt', $content->extension);
        $this->assertSame('text/plain', $content->mimeType);
        $this->assertSame($stream, $content->getStream());
    }

    public function testThrowsOnInvalidResource(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('file.error.content_invalid_stream');

        $content = new FileContent('not a stream', 'txt', 'text/plain');
        $this->assertInstanceOf(FileContent::class, $content);
    }

    public function testThrowsOnClosedStream(): void
    {
        $stream = fopen('php://memory', 'r+b');
        fclose($stream);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('file.error.content_invalid_stream');

        $content = new FileContent($stream, 'txt', 'text/plain');
        $this->assertInstanceOf(FileContent::class, $content);
    }

    public function testFromPathCreatesValidContent(): void
    {
        $filePath = $this->tempDir . '/test.txt';
        file_put_contents($filePath, 'test content');

        $content = FileContent::fromPath($filePath);

        $this->assertSame('txt', $content->extension);
        $this->assertIsResource($content->getStream());
    }

    public function testFromPathThrowsOnMissingFile(): void
    {
        $this->expectException(RuntimeException::class);

        FileContent::fromPath($this->tempDir . '/non-existent.txt');
    }

    public function testFromPathDetectsMimeType(): void
    {
        $filePath = $this->tempDir . '/test.txt';
        file_put_contents($filePath, 'plain text content');

        $content = FileContent::fromPath($filePath);

        $this->assertSame('text/plain', $content->mimeType);
    }

    public function testComputeKeyReturnsFileKey(): void
    {
        $textContent = 'test content for hashing';
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $textContent);
        rewind($stream);

        $content = new FileContent($stream, 'txt', 'text/plain');
        $key = $content->computeKey();

        $this->assertInstanceOf(FileKey::class, $key);
        $this->assertSame(hash('sha256', $textContent), $key->value);
    }

    public function testComputeKeyIsDeterministic(): void
    {
        $textContent = 'same content';

        $stream1 = fopen('php://memory', 'r+b');
        fwrite($stream1, $textContent);
        rewind($stream1);

        $stream2 = fopen('php://memory', 'r+b');
        fwrite($stream2, $textContent);
        rewind($stream2);

        $content1 = new FileContent($stream1, 'txt', 'text/plain');
        $content2 = new FileContent($stream2, 'txt', 'text/plain');

        $this->assertTrue($content1->computeKey()->equals($content2->computeKey()));
    }
}
