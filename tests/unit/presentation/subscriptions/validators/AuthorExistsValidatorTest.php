<?php

declare(strict_types=1);

namespace tests\unit\presentation\subscriptions\validators;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorRepositoryInterface;
use app\presentation\subscriptions\forms\SubscriptionForm;
use app\presentation\subscriptions\validators\AuthorExistsValidator;
use Codeception\Test\Unit;

final class AuthorExistsValidatorTest extends Unit
{
    public function testValidatePassesWhenAuthorExists(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('findById')
            ->with(1)
            ->willReturn(new AuthorReadDto(1, 'Author Name'));

        $validator = new AuthorExistsValidator($repository);

        $form = new SubscriptionForm();
        $form->authorId = 1;

        $validator->validateAttribute($form, 'authorId');

        $this->assertFalse($form->hasErrors('authorId'));
    }

    public function testValidateFailsWhenAuthorNotExists(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('findById')
            ->with(999)
            ->willReturn(null);

        $validator = new AuthorExistsValidator($repository);

        $form = new SubscriptionForm();
        $form->authorId = 999;

        $validator->validateAttribute($form, 'authorId');

        $this->assertTrue($form->hasErrors('authorId'));
    }

    public function testValidateHandlesStringIds(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('findById')
            ->with(1)
            ->willReturn(new AuthorReadDto(1, 'Author Name'));

        $validator = new AuthorExistsValidator($repository);

        $form = new SubscriptionForm();
        $form->authorId = '1';

        $validator->validateAttribute($form, 'authorId');

        $this->assertFalse($form->hasErrors('authorId'));
    }

    public function testValidateFailsOnInvalidIdType(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects($this->never())->method('findById');

        $validator = new AuthorExistsValidator($repository);

        $form = new SubscriptionForm();
        $form->authorId = ['array'];

        $validator->validateAttribute($form, 'authorId');

        $this->assertTrue($form->hasErrors('authorId'));
    }

    public function testValidateFailsOnZeroId(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects($this->never())->method('findById');

        $validator = new AuthorExistsValidator($repository);

        $form = new SubscriptionForm();
        $form->authorId = 0;

        $validator->validateAttribute($form, 'authorId');

        $this->assertTrue($form->hasErrors('authorId'));
    }

    public function testValidateFailsOnNegativeId(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects($this->never())->method('findById');

        $validator = new AuthorExistsValidator($repository);

        $form = new SubscriptionForm();
        $form->authorId = -5;

        $validator->validateAttribute($form, 'authorId');

        $this->assertTrue($form->hasErrors('authorId'));
    }
}
