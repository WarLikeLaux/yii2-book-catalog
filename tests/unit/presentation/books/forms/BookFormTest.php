<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\forms;

use app\application\ports\AuthorQueryServiceInterface;
use app\application\ports\BookQueryServiceInterface;
use app\presentation\books\forms\BookForm;
use Codeception\Test\Unit;

final class BookFormTest extends Unit
{
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
