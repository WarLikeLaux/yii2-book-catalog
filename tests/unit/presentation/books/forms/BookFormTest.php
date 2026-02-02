<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\forms;

use app\presentation\books\forms\BookForm;
use Codeception\Test\Unit;

final class BookFormTest extends Unit
{
    public function testGetAuthorInitValueTextReturnsEmptyWhenAuthorIdsIsNull(): void
    {
        $form = new BookForm();
        $form->authorIds = null;

        $result = $form->getAuthorInitValueText([1 => 'Author 1']);

        $this->assertSame([], $result);
    }

    public function testGetAuthorInitValueTextSkipsInvalidAuthorIds(): void
    {
        $form = new BookForm();
        $form->authorIds = ['abc', 0, -2];

        $result = $form->getAuthorInitValueText([1 => 'Author 1']);

        $this->assertSame([], $result);
    }

    public function testGetAuthorInitValueTextReturnsAuthorNamesForValidIds(): void
    {
        $form = new BookForm();
        $form->authorIds = [1, 2];

        $result = $form->getAuthorInitValueText([
            1 => 'Author 1',
            2 => 'Author 2',
        ]);

        $this->assertSame(['Author 1', 'Author 2'], $result);
    }

    public function testGetAuthorInitValueTextUsesIdStringWhenAuthorNotFound(): void
    {
        $form = new BookForm();
        $form->authorIds = [1, 999];

        $result = $form->getAuthorInitValueText([1 => 'Author 1']);

        $this->assertSame(['Author 1', '999'], $result);
    }
}
