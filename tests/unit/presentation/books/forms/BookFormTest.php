<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\forms;

use app\presentation\books\forms\BookForm;
use Codeception\Test\Unit;

final class BookFormTest extends Unit
{
    private const AUTHOR_NAME = 'Author 1';
    public function testGetAuthorInitValueTextReturnsEmptyWhenAuthorIdsIsNull(): void
    {
        $form = new BookForm();
        $form->authorIds = null;

        $result = $form->getAuthorInitValueText([1 => self::AUTHOR_NAME]);

        $this->assertSame([], $result);
    }

    public function testGetAuthorInitValueTextSkipsInvalidAuthorIds(): void
    {
        $form = new BookForm();
        $form->authorIds = ['abc', 0, -2];

        $result = $form->getAuthorInitValueText([1 => self::AUTHOR_NAME]);

        $this->assertSame([], $result);
    }

    public function testGetAuthorInitValueTextReturnsAuthorNamesForValidIds(): void
    {
        $form = new BookForm();
        $form->authorIds = [1, 2];

        $result = $form->getAuthorInitValueText([
            1 => self::AUTHOR_NAME,
            2 => 'Author 2',
        ]);

        $this->assertSame([self::AUTHOR_NAME, 'Author 2'], $result);
    }

    public function testGetAuthorInitValueTextUsesIdStringWhenAuthorNotFound(): void
    {
        $form = new BookForm();
        $form->authorIds = [1, 999];

        $result = $form->getAuthorInitValueText([1 => self::AUTHOR_NAME]);

        $this->assertSame([self::AUTHOR_NAME, '999'], $result);
    }
}
