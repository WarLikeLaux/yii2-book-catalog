<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\validators;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorRepositoryInterface;
use app\presentation\books\forms\BookForm;
use app\presentation\books\validators\AuthorExistsValidator;
use Codeception\Test\Unit;

final class AuthorExistsValidatorTest extends Unit
{
    public function testValidatePassesWhenAllAuthorsExist(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('findById')->willReturn(new AuthorReadDto(1, 'Author'));

        $validator = new AuthorExistsValidator($repository);

        $form = new BookForm();
        $form->authorIds = [1, 2, 3];

        $validator->validateAttribute($form, 'authorIds');

        $this->assertFalse($form->hasErrors('authorIds'));
    }

    public function testValidateFailsWhenAuthorNotExists(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);

        $validator = new AuthorExistsValidator($repository);

        $form = new BookForm();
        $form->authorIds = [999];

        $validator->validateAttribute($form, 'authorIds');

        $this->assertTrue($form->hasErrors('authorIds'));
    }

    public function testValidateSkipsNonArrayValues(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects($this->never())->method('findById');

        $validator = new AuthorExistsValidator($repository);

        $form = new BookForm();
        $form->authorIds = 'not-an-array';

        $validator->validateAttribute($form, 'authorIds');
    }

    public function testValidateHandlesStringIds(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('findById')
            ->with(1)
            ->willReturn(new AuthorReadDto(1, 'Author'));

        $validator = new AuthorExistsValidator($repository);

        $form = new BookForm();
        $form->authorIds = ['1'];

        $validator->validateAttribute($form, 'authorIds');

        $this->assertFalse($form->hasErrors('authorIds'));
    }
}
