<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\User;

final class BookCrudCest
{
    public function _before(IntegrationTester $I): void
    {
        DbCleaner::clear(['book_authors', 'books', 'authors']);
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testUpdateBookPage(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Book To Update',
            'year' => 2024,
            'isbn' => '9783161484100',
            'description' => 'Test',
        ]);
        Yii::$app->db->createCommand()
            ->insert('book_authors', ['book_id' => $bookId, 'author_id' => $authorId])
            ->execute();

        $I->amOnRoute('book/update', ['id' => $bookId]);
        $I->seeResponseCodeIs(200);
        $I->see('Обновить книгу');
        $I->seeInField('BookForm[title]', 'Book To Update');
    }

    public function testUpdateBookSuccess(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Update Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Original Title',
            'year' => 2024,
            'isbn' => '9783161484100',
        ]);

        $I->amOnRoute('book/update', ['id' => $bookId]);
        $I->fillField('BookForm[title]', 'Updated Title');
        $I->fillField('BookForm[year]', '2025');

        $I->sendPost('/index-test.php?r=book/update&id=' . $bookId, [
            'BookForm' => [
                'title' => 'Updated Title',
                'year' => '2025',
                'isbn' => '9783161484100',
                'authorIds' => [$authorId],
            ],
        ]);

        $I->seeInCurrentUrl('book');
        $I->seeInCurrentUrl('view');
        $I->see('Updated Title');
        $I->seeRecord(Book::class, ['id' => $bookId, 'title' => 'Updated Title', 'year' => 2025]);
    }

    public function testDeleteBook(IntegrationTester $I): void
    {
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Book To Delete',
            'year' => 2024,
            'isbn' => '9780134685991',
            'description' => 'Will be deleted',
        ]);

        $I->sendAjaxPostRequest('/index-test.php?r=book/delete&id=' . $bookId);
        $I->dontSeeRecord(Book::class, ['id' => $bookId]);
    }
}
