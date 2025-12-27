<?php

declare(strict_types=1);

namespace tests\unit\presentation\validators;

use app\application\ports\BookRepositoryInterface;
use app\presentation\forms\BookForm;
use app\presentation\validators\UniqueIsbnValidator;
use Codeception\Test\Unit;

final class UniqueIsbnValidatorTest extends Unit
{
    public function testValidatePassesWhenIsbnNotExists(): void
    {
        $repository = $this->createMock(BookRepositoryInterface::class);
        $repository->method('existsByIsbn')->willReturn(false);
        
        $validator = new UniqueIsbnValidator($repository);
        
        $form = new BookForm();
        $form->isbn = '9783161484100';
        
        $validator->validateAttribute($form, 'isbn');
        
        $this->assertFalse($form->hasErrors('isbn'));
    }

    public function testValidateFailsWhenIsbnExists(): void
    {
        $repository = $this->createMock(BookRepositoryInterface::class);
        $repository->method('existsByIsbn')->willReturn(true);
        
        $validator = new UniqueIsbnValidator($repository);
        
        $form = new BookForm();
        $form->isbn = '9783161484100';
        
        $validator->validateAttribute($form, 'isbn');
        
        $this->assertTrue($form->hasErrors('isbn'));
    }

    public function testValidateSkipsNonStringValues(): void
    {
        $repository = $this->createMock(BookRepositoryInterface::class);
        $repository->expects($this->never())->method('existsByIsbn');
        
        $validator = new UniqueIsbnValidator($repository);
        
        $form = new BookForm();
        $form->isbn = 123;
        
        $validator->validateAttribute($form, 'isbn');
    }

    public function testValidateExcludesCurrentBookId(): void
    {
        $repository = $this->createMock(BookRepositoryInterface::class);
        $repository->method('existsByIsbn')
            ->with('9783161484100', 42)
            ->willReturn(false);
        
        $validator = new UniqueIsbnValidator($repository);
        
        $form = new BookForm();
        $form->id = 42;
        $form->isbn = '9783161484100';
        
        $validator->validateAttribute($form, 'isbn');
        
        $this->assertFalse($form->hasErrors('isbn'));
    }

    public function testValidateCastsStringIdToInt(): void
    {
        $repository = $this->createMock(BookRepositoryInterface::class);
        $repository->method('existsByIsbn')
            ->with('9783161484100', 42)
            ->willReturn(false);

        $validator = new UniqueIsbnValidator($repository);

        $form = new BookForm();
        $form->id = '42';
        $form->isbn = '9783161484100';

        $validator->validateAttribute($form, 'isbn');

        $this->assertFalse($form->hasErrors('isbn'));
    }
}
