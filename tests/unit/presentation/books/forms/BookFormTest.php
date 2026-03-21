<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\forms;

use app\presentation\books\forms\BookForm;
use PHPUnit\Framework\TestCase;

final class BookFormTest extends TestCase
{
    private const AUTHOR_NAME = 'Author 1';
    public function testGetAuthorInitValueTextReturnsEmptyWhenAuthorIdsIsNull(): void
    {
        $form = new BookForm();
        $form->authorIds = null;

        $result = $form->getAuthorInitValueText([1 => self::AUTHOR_NAME]);

        $this->assertSame([], $result);
    }

    public function testGetAuthorInitValueTextReturnsEmptyWhenAuthorIdsIsEmpty(): void
    {
        $form = new BookForm();
        $form->authorIds = [];

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
