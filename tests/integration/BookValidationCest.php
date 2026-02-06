<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\User;

final class BookValidationCest
{
    public function _before(IntegrationTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testCreateBookWithDuplicateIsbn(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Validation Author']);
        $I->haveRecord(Book::class, [
            'title' => 'Existing Book',
            'year' => 2024,
            'isbn' => '9783161484100',
        ]);

        $I->amOnRoute('book/create');
        $I->sendPost('/index-test.php?r=book/create', [
            'BookForm' => [
                'title' => 'New Book',
                'year' => '2024',
                'isbn' => '9783161484100',
                'authorIds' => [$authorId],
            ],
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Книга с таким ISBN уже существует');
    }

    public function testCreateBookWithInvalidIsbn(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'ISBN Test Author']);

        $I->amOnRoute('book/create');
        $I->sendPost('/index-test.php?r=book/create', [
            'BookForm' => [
                'title' => 'Book with Invalid ISBN',
                'year' => '2024',
                'isbn' => 'invalid-isbn-123',
                'authorIds' => [$authorId],
            ],
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Некорректный ISBN');
    }

    public function testCreateBookWithEmptyIsbn(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Empty ISBN Author']);

        $I->amOnRoute('book/create');
        $I->sendPost('/index-test.php?r=book/create', [
            'BookForm' => [
                'title' => 'Book without ISBN',
                'year' => '2024',
                'isbn' => '',
                'authorIds' => [$authorId],
            ],
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Необходимо заполнить');
    }

    public function testUpdateBookWithSameIsbnAllowed(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Same ISBN Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Book to Update',
            'year' => 2024,
            'isbn' => '9783161484100',
        ]);

        $I->sendPost('/index-test.php?r=book/update&id=' . $bookId, [
            'BookForm' => [
                'title' => 'Updated Book Title',
                'year' => '2024',
                'isbn' => '9783161484100',
                'authorIds' => [$authorId],
            ],
        ]);

        $I->seeInCurrentUrl('view');
        $I->seeRecord(Book::class, ['id' => $bookId, 'title' => 'Updated Book Title']);
    }

    public function testCreateBookWithInvalidYear(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Year Test Author']);

        $I->amOnRoute('book/create');
        $I->sendPost('/index-test.php?r=book/create', [
            'BookForm' => [
                'title' => 'Book with Invalid Year',
                'year' => '999',
                'isbn' => '9783161484100',
                'authorIds' => [$authorId],
            ],
        ]);

        $I->seeResponseCodeIs(200);
    }

    public function testCreateBookWithFutureYear(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Future Year Author']);

        $I->amOnRoute('book/create');
        $I->sendPost('/index-test.php?r=book/create', [
            'BookForm' => [
                'title' => 'Book from Future',
                'year' => (string)(date('Y') + 2),
                'isbn' => '9783161484100',
                'authorIds' => [$authorId],
            ],
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Год не может быть в будущем');
    }

    public function testCreateBookWithNonExistentAuthor(IntegrationTester $I): void
    {
        $I->amOnRoute('book/create');
        $I->sendPost('/index-test.php?r=book/create', [
            'BookForm' => [
                'title' => 'Book with Invalid Author',
                'year' => '2024',
                'isbn' => '9783161484100',
                'authorIds' => [99999],
            ],
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('не существует');
    }
}
