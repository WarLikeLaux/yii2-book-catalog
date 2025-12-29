<?php

declare(strict_types=1);

namespace tests\unit\presentation\validators;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorRepositoryInterface;
use app\application\ports\BookRepositoryInterface;
use app\presentation\authors\validators\UniqueFioValidator;
use app\presentation\books\validators\AuthorExistsValidator as BookAuthorExistsValidator;
use app\presentation\books\validators\IsbnValidator;
use app\presentation\books\validators\UniqueIsbnValidator;
use app\presentation\subscriptions\validators\AuthorExistsValidator;
use Codeception\Test\Unit;
use yii\base\Model;

final class ValidatorEdgeCasesTest extends Unit
{
    public function testUniqueIsbnValidatorSkipsNonStringValue(): void
    {
        $repository = $this->createMock(BookRepositoryInterface::class);
        $repository->expects($this->never())->method('existsByIsbn');

        $validator = new UniqueIsbnValidator($repository);

        $model = new class extends Model {
            public $isbn = null;
        };

        $validator->validateAttribute($model, 'isbn');

        $this->assertFalse($model->hasErrors('isbn'));
    }

    public function testUniqueIsbnValidatorCastsStringExcludeId(): void
    {
        $repository = $this->createMock(BookRepositoryInterface::class);
        $repository->method('existsByIsbn')
            ->with('9783161484100', 42)
            ->willReturn(false);

        $validator = new UniqueIsbnValidator($repository);

        $model = new class extends Model {
            public $id = '42';

            public $isbn = '9783161484100';
        };

        $validator->validateAttribute($model, 'isbn');

        $this->assertFalse($model->hasErrors('isbn'));
    }

    public function testUniqueFioValidatorSkipsNonStringValue(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects($this->never())->method('existsByFio');

        $validator = new UniqueFioValidator($repository);

        $model = new class extends Model {
            public $fio = null;
        };

        $validator->validateAttribute($model, 'fio');

        $this->assertFalse($model->hasErrors('fio'));
    }

    public function testUniqueFioValidatorCastsStringExcludeId(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('existsByFio')
            ->with('Test Author', 42)
            ->willReturn(false);

        $validator = new UniqueFioValidator($repository);

        $model = new class extends Model {
            public $id = '42';

            public $fio = 'Test Author';
        };

        $validator->validateAttribute($model, 'fio');

        $this->assertFalse($model->hasErrors('fio'));
    }

    public function testIsbnValidatorAddsErrorForNonStringValue(): void
    {
        $validator = new IsbnValidator();

        $model = new class extends Model {
            public $isbn = null;
        };

        $validator->validateAttribute($model, 'isbn');

        $this->assertTrue($model->hasErrors('isbn'));
    }

    public function testIsbnValidatorAddsErrorForInvalidIsbn(): void
    {
        $validator = new IsbnValidator();

        $model = new class extends Model {
            public $isbn = 'invalid-isbn';
        };

        $validator->validateAttribute($model, 'isbn');

        $this->assertTrue($model->hasErrors('isbn'));
    }

    public function testAuthorExistsValidatorAddsErrorForNonIntNonStringValue(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects($this->never())->method('findById');

        $validator = new AuthorExistsValidator($repository);

        $model = new class extends Model {
            public $authorId = null;
        };

        $validator->validateAttribute($model, 'authorId');

        $this->assertTrue($model->hasErrors('authorId'));
    }

    public function testAuthorExistsValidatorAddsErrorForZeroId(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects($this->never())->method('findById');

        $validator = new AuthorExistsValidator($repository);

        $model = new class extends Model {
            public $authorId = 0;
        };

        $validator->validateAttribute($model, 'authorId');

        $this->assertTrue($model->hasErrors('authorId'));
    }

    public function testAuthorExistsValidatorAddsErrorForNonExistentAuthor(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('findById')->with(999)->willReturn(null);

        $validator = new AuthorExistsValidator($repository);

        $model = new class extends Model {
            public $authorId = 999;
        };

        $validator->validateAttribute($model, 'authorId');

        $this->assertTrue($model->hasErrors('authorId'));
    }

    public function testAuthorExistsValidatorPassesForExistingAuthor(): void
    {
        $dto = new AuthorReadDto(1, 'Test Author');
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('findById')->with(1)->willReturn($dto);

        $validator = new AuthorExistsValidator($repository);

        $model = new class extends Model {
            public $authorId = 1;
        };

        $validator->validateAttribute($model, 'authorId');

        $this->assertFalse($model->hasErrors('authorId'));
    }

    public function testBookAuthorExistsValidatorSkipsNonArrayValue(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects($this->never())->method('findById');

        $validator = new BookAuthorExistsValidator($repository);

        $model = new class extends Model {
            public $authorIds = 'not-an-array';
        };

        $validator->validateAttribute($model, 'authorIds');

        $this->assertFalse($model->hasErrors('authorIds'));
    }

    public function testBookAuthorExistsValidatorSkipsNonIntNonStringInArray(): void
    {
        $dto = new AuthorReadDto(1, 'Test Author');
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('findById')->with(1)->willReturn($dto);

        $validator = new BookAuthorExistsValidator($repository);

        $model = new class extends Model {
            public $authorIds = [1, null, false];
        };

        $validator->validateAttribute($model, 'authorIds');

        $this->assertFalse($model->hasErrors('authorIds'));
    }

    public function testBookAuthorExistsValidatorCastsStringAuthorId(): void
    {
        $dto = new AuthorReadDto(42, 'Test Author');
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('findById')->with(42)->willReturn($dto);

        $validator = new BookAuthorExistsValidator($repository);

        $model = new class extends Model {
            public $authorIds = ['42'];
        };

        $validator->validateAttribute($model, 'authorIds');

        $this->assertFalse($model->hasErrors('authorIds'));
    }

    public function testBookAuthorExistsValidatorAddsErrorForNonExistentAuthor(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('findById')->with(999)->willReturn(null);

        $validator = new BookAuthorExistsValidator($repository);

        $model = new class extends Model {
            public $authorIds = [999];
        };

        $validator->validateAttribute($model, 'authorIds');

        $this->assertTrue($model->hasErrors('authorIds'));
    }
}
