<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\exceptions\DomainException;
use app\domain\values\FileKey;
use Codeception\Test\Unit;

final class FileKeyTest extends Unit
{
    private const string VALID_HASH = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    public static function validHashProvider(): array
    {
        return [
            'lowercase-sha256' => [
                'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            ],
            'uppercase-sha256' => [
                'E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855',
                'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            ],
            'mixed-case-sha256' => [
                'E3b0C44298fc1C149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            ],
        ];
    }

    public static function invalidHashProvider(): array
    {
        return [
            'too-short' => ['e3b0c44298fc1c149afbf4c8996fb924'],
            'too-long' => ['e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b8550'],
            'invalid-characters' => ['g3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'],
            'with-spaces' => ['e3b0 c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'],
            'empty-string' => [''],
        ];
    }

    public static function extendedPathProvider(): array
    {
        return [
            'no-extension' => [
                'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                '',
                'e3/b0/e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            ],
            'with-jpg-extension' => [
                'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                'jpg',
                'e3/b0/e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855.jpg',
            ],
            'with-png-extension' => [
                'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890',
                'png',
                'ab/cd/abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890.png',
            ],
        ];
    }

    /**
     * @dataProvider validHashProvider
     */
    public function testCanCreateWithValidHash(string $input, string $expected): void
    {
        $key = new FileKey($input);
        $this->assertSame($expected, $key->value);
    }

    /**
     * @dataProvider invalidHashProvider
     */
    public function testThrowsOnInvalidHash(string $invalidHash): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('file.error.key_invalid_format');

        $key = new FileKey($invalidHash);
        $this->assertInstanceOf(FileKey::class, $key);
    }

    public function testFromStreamCreatesValidKey(): void
    {
        $content = 'test content for hashing';
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $content);
        rewind($stream);

        $key = FileKey::fromStream($stream);

        $expectedHash = hash('sha256', $content);
        $this->assertSame($expectedHash, $key->value);

        fclose($stream);
    }

    public function testFromStreamRewindsStream(): void
    {
        $content = 'test content';
        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $content);
        rewind($stream);

        FileKey::fromStream($stream);

        $this->assertSame(0, ftell($stream));

        fclose($stream);
    }

    /**
     * @dataProvider extendedPathProvider
     */
    public function testGetExtendedPathReturnsCorrectFormat(
        string $hash,
        string $extension,
        string $expected,
    ): void {
        $key = new FileKey($hash);
        $this->assertSame($expected, $key->getExtendedPath($extension));
    }

    public function testEqualsReturnsTrueForSameHash(): void
    {
        $key1 = new FileKey(self::VALID_HASH);
        $key2 = new FileKey(strtoupper(self::VALID_HASH));

        $this->assertTrue($key1->equals($key2));
    }

    public function testEqualsReturnsFalseForDifferentHash(): void
    {
        $key1 = new FileKey(self::VALID_HASH);
        $key2 = new FileKey('abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890');

        $this->assertFalse($key1->equals($key2));
    }

    public function testToStringReturnsValue(): void
    {
        $key = new FileKey(self::VALID_HASH);
        $this->assertSame(self::VALID_HASH, (string)$key);
    }
}
