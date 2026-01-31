<?php

declare(strict_types=1);

use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\User;

final class BookCest
{
    public function _before(IntegrationTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testCanViewBooksIndex(IntegrationTester $I): void
    {
        $I->amOnRoute('book/index');
        $I->seeResponseCodeIs(200);
        $I->see(Yii::t('app', 'ui.books'), 'h1');
    }

    public function testCanViewBookCreatePage(IntegrationTester $I): void
    {
        $I->amOnRoute('book/create');
        $I->seeResponseCodeIs(200);
        $I->see(Yii::t('app', 'ui.book_create'), 'h1');
        $I->seeElement('form');
        $I->seeElement('input[name="BookForm[title]"]');
    }

    public function testCanViewBookDetails(IntegrationTester $I): void
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
