<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\validators;

use app\application\ports\AuthorRepositoryInterface;
use app\presentation\authors\forms\AuthorForm;
use app\presentation\authors\validators\UniqueFioValidator;
use Codeception\Test\Unit;

final class UniqueFioValidatorTest extends Unit
{
    public function testValidatePassesWhenFioNotExists(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('existsByFio')->willReturn(false);
        
        $validator = new UniqueFioValidator($repository);
        
        $form = new \app\presentation\authors\forms\AuthorForm();
        $form->fio = 'Unique Author Name';
        
        $validator->validateAttribute($form, 'fio');
        
        $this->assertFalse($form->hasErrors('fio'));
    }

    public function testValidateFailsWhenFioExists(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('existsByFio')->willReturn(true);
        
        $validator = new UniqueFioValidator($repository);
        
        $form = new \app\presentation\authors\forms\AuthorForm();
        $form->fio = 'Existing Author';
        
        $validator->validateAttribute($form, 'fio');
        
        $this->assertTrue($form->hasErrors('fio'));
    }

    public function testValidateSkipsNonStringValues(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects($this->never())->method('existsByFio');
        
        $validator = new UniqueFioValidator($repository);
        
        $form = new \app\presentation\authors\forms\AuthorForm();
        $form->fio = 123;
        
        $validator->validateAttribute($form, 'fio');
    }

    public function testValidateExcludesCurrentAuthorId(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('existsByFio')
            ->with('Author Name', 42)
            ->willReturn(false);
        
        $validator = new UniqueFioValidator($repository);
        
        $form = new \app\presentation\authors\forms\AuthorForm();
        $form->id = 42;
        $form->fio = 'Author Name';
        
        $validator->validateAttribute($form, 'fio');
        
        $this->assertFalse($form->hasErrors('fio'));
    }

    public function testValidateCastsStringIdToInt(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('existsByFio')
            ->with('Author Name', 42)
            ->willReturn(false);

        $validator = new UniqueFioValidator($repository);

        $form = new \app\presentation\authors\forms\AuthorForm();
        $form->id = '42';
        $form->fio = 'Author Name';

        $validator->validateAttribute($form, 'fio');

        $this->assertFalse($form->hasErrors('fio'));
    }
}
