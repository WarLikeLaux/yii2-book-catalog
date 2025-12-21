<?php

declare(strict_types=1);

use app\models\Book;
use app\models\User;

final class BookCest
{
    public function _before(\FunctionalTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testCanViewBooksIndex(\FunctionalTester $I): void
    {
        $I->amOnRoute('book/index');
        $I->seeResponseCodeIs(200);
        $I->see('Книги', 'h1');
    }

    public function testCanViewBookCreatePage(\FunctionalTester $I): void
    {
        $I->amOnRoute('book/create');
        $I->seeResponseCodeIs(200);
        $I->see('Создать книгу', 'h1');
        $I->seeElement('form');
        $I->seeElement('input[name="BookForm[title]"]');
    }

    public function testCanViewBookDetails(\FunctionalTester $I): void
    {
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Test Book',
            'year' => 2024,
            'isbn' => '9783161484100',
            'description' => 'Test description',
        ]);

        $I->amOnRoute('book/view', ['id' => $bookId]);
        $I->seeResponseCodeIs(200);
        $I->see('Test Book');
    }
}
