<?php

declare(strict_types=1);

namespace tests\unit\application\common\middleware;

use app\application\common\exceptions\DomainErrorMappingRegistry;
use app\application\common\exceptions\OperationFailedException;
use app\application\common\middleware\DomainExceptionTranslationMiddleware;
use app\application\ports\CommandInterface;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class DomainExceptionTranslationMiddlewareTest extends TestCase
{
    private DomainExceptionTranslationMiddleware $middleware;
    private DomainErrorMappingRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new DomainErrorMappingRegistry();
        $this->middleware = new DomainExceptionTranslationMiddleware($this->registry);
    }

    public function testProcessReturnsResultWhenNoException(): void
    {
        $command = $this->createStub(CommandInterface::class);

        $result = $this->middleware->process(
            $command,
            static fn(CommandInterface $_cmd): string => 'ok',
        );

        $this->assertSame('ok', $result);
    }

    public function testProcessTranslatesDomainExceptionWhenMappingExists(): void
    {
        $this->registry->register(
            DomainErrorCode::BookTitleEmpty,
            OperationFailedException::class,
            'title',
        );

        $command = $this->createStub(CommandInterface::class);

        $this->expectException(OperationFailedException::class);
        $this->expectExceptionMessage(DomainErrorCode::BookTitleEmpty->value);

        $this->middleware->process(
            $command,
            static function (CommandInterface $_cmd): never {
                throw new ValidationException(DomainErrorCode::BookTitleEmpty);
            },
        );
    }

    public function testProcessRethrowsDomainExceptionWhenNoMapping(): void
    {
        $command = $this->createStub(CommandInterface::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::BookTitleEmpty->value);

        $this->middleware->process(
            $command,
            static function (CommandInterface $_cmd): never {
                throw new ValidationException(DomainErrorCode::BookTitleEmpty);
            },
        );
    }

    public function testTranslatedExceptionPreservesOriginalAsPrevious(): void
    {
        $this->registry->register(
            DomainErrorCode::BookNotFound,
            OperationFailedException::class,
        );

        $command = $this->createStub(CommandInterface::class);
        $original = new ValidationException(DomainErrorCode::BookNotFound);

        try {
            $this->middleware->process(
                $command,
                static function (CommandInterface $_cmd) use ($original): never {
                    throw $original;
                },
            );
            $this->fail('Expected exception was not thrown');
        } catch (OperationFailedException $e) {
            $this->assertSame($original, $e->getPrevious());
            $this->assertSame(DomainErrorCode::BookNotFound->value, $e->errorCode);
        }
    }

    public function testTranslatedExceptionContainsMappedField(): void
    {
        $this->registry->register(
            DomainErrorCode::BookIsbnExists,
            OperationFailedException::class,
            'isbn',
        );

        $command = $this->createStub(CommandInterface::class);

        try {
            $this->middleware->process(
                $command,
                static function (CommandInterface $_cmd): never {
                    throw new ValidationException(DomainErrorCode::BookIsbnExists);
                },
            );
            $this->fail('Expected exception was not thrown');
        } catch (OperationFailedException $e) {
            $this->assertSame('isbn', $e->field);
        }
    }
}
