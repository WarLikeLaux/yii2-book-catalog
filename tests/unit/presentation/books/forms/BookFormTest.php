<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\forms;

use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookQueryServiceInterface;
use app\presentation\books\forms\BookForm;
use Codeception\Test\Unit;

final class BookFormTest extends Unit
{
    public function testValidateIsbnUniqueIgnoresNonStringIsbn(): void
    {
        $bookQueryService = $this->createMock(BookQueryServiceInterface::class);
        $bookQueryService->expects($this->never())->method('existsByIsbn');

        $form = new BookForm(
            $bookQueryService,
            $this->createMock(AuthorQueryServiceInterface::class),
        );
        $form->isbn = 123;

        $form->validateIsbnUnique('isbn');

        $this->assertFalse($form->hasErrors('isbn'));
    }

    public function testValidateAuthorsExistIgnoresNonArrayAuthorIds(): void
    {
        $authorQueryService = $this->createMock(AuthorQueryServiceInterface::class);
        $authorQueryService->expects($this->never())->method('findMissingIds');

        $form = new BookForm(
            $this->createMock(BookQueryServiceInterface::class),
            $authorQueryService,
        );
        $form->authorIds = 'not-an-array';

        $form->validateAuthorsExist('authorIds');

        $this->assertFalse($form->hasErrors('authorIds'));
    }

    public function testValidateAuthorsExistSkipsNonScalarIds(): void
    {
        $authorQueryService = $this->createMock(AuthorQueryServiceInterface::class);
        $authorQueryService->expects($this->never())->method('findMissingIds');

        $form = new BookForm(
            $this->createMock(BookQueryServiceInterface::class),
            $authorQueryService,
        );
        $form->authorIds = [new \stdClass()];

        $form->validateAuthorsExist('authorIds');

        $this->assertFalse($form->hasErrors('authorIds'));
    }

    public function testGetAuthorInitValueTextReturnsEmptyWhenAuthorIdsIsNull(): void
    {
        $form = new BookForm(
            $this->createMock(BookQueryServiceInterface::class),
            $this->createMock(AuthorQueryServiceInterface::class),
        );
        $form->authorIds = null;

        $result = $form->getAuthorInitValueText([1 => 'Author 1']);

        $this->assertSame([], $result);
    }

    public function testGetAuthorInitValueTextSkipsInvalidAuthorIds(): void
    {
        $form = new BookForm(
            $this->createMock(BookQueryServiceInterface::class),
            $this->createMock(AuthorQueryServiceInterface::class),
        );
        $form->authorIds = ['abc', 0, -2];

        $result = $form->getAuthorInitValueText([1 => 'Author 1']);

        $this->assertSame([], $result);
    }

    public function testValidateAuthorsExistSkipsInvalidIds(): void
    {
        $form = new BookForm(
            $this->createMock(BookQueryServiceInterface::class),
            $this->createMock(AuthorQueryServiceInterface::class),
        );
        $form->authorIds = ['0', '-1', ''];

        $form->validateAuthorsExist('authorIds');

        $this->assertFalse($form->hasErrors('authorIds'));
    }
}
