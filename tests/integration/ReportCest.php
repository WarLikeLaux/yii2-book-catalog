<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\User;

final class ReportCest
{
    public function _before(IntegrationTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
        Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        Yii::$app->db->createCommand()->delete('book_authors')->execute();
        Yii::$app->db->createCommand()->delete('books')->execute();
        Yii::$app->db->createCommand()->delete('authors')->execute();
        Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
    }

    public function testCanViewReportPage(IntegrationTester $I): void
    {
        $I->amOnRoute('report/index');
        $I->seeResponseCodeIs(200);
        $I->see('ТОП-10 авторов', 'h1');
        $I->seeElement('form');
        $I->seeElement('input[name="year"]');
    }

    public function testReportShowsAuthorsWithBooks(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Report Test Author']);
        $currentYear = (int)date('Y');
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Report Test Book',
            'year' => $currentYear,
            'isbn' => '9783161484100',
            'description' => 'Test',
        ]);

        Yii::$app->db->createCommand()->insert('book_authors', [
            'book_id' => $bookId,
            'author_id' => $authorId,
        ])->execute();

        $I->amOnRoute('report/index', ['year' => $currentYear]);
        $I->seeResponseCodeIs(200);
        $I->see('Report Test Author');
        $I->see('1');
    }

    public function testReportFiltersByYear(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Year Filter Author']);
        $book2023 = $I->haveRecord(Book::class, [
            'title' => 'Book 2023',
            'year' => 2023,
            'isbn' => '9783161484101',
            'description' => 'Test',
        ]);
        $book2024 = $I->haveRecord(Book::class, [
            'title' => 'Book 2024',
            'year' => 2024,
            'isbn' => '9783161484102',
            'description' => 'Test',
        ]);

        Yii::$app->db->createCommand()->insert('book_authors', [
            'book_id' => $book2023,
            'author_id' => $authorId,
        ])->execute();
        Yii::$app->db->createCommand()->insert('book_authors', [
            'book_id' => $book2024,
            'author_id' => $authorId,
        ])->execute();

        $I->amOnRoute('report/index', ['year' => 2024]);
        $I->seeResponseCodeIs(200);
        $I->see('Year Filter Author');
        $I->see('1');

        $I->amOnRoute('report/index', ['year' => 2023]);
        $I->seeResponseCodeIs(200);
        $I->see('Year Filter Author');
        $I->see('1');
    }

    public function testReportShowsMultipleBooksCount(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Multiple Books Author']);
        $currentYear = (int)date('Y');

        $book1 = $I->haveRecord(Book::class, [
            'title' => 'Book 1',
            'year' => $currentYear,
            'isbn' => '9783161484103',
            'description' => 'Test',
        ]);
        $book2 = $I->haveRecord(Book::class, [
            'title' => 'Book 2',
            'year' => $currentYear,
            'isbn' => '9783161484104',
            'description' => 'Test',
        ]);
        $book3 = $I->haveRecord(Book::class, [
            'title' => 'Book 3',
            'year' => $currentYear,
            'isbn' => '9783161484105',
            'description' => 'Test',
        ]);

        Yii::$app->db->createCommand()->insert('book_authors', [
            'book_id' => $book1,
            'author_id' => $authorId,
        ])->execute();
        Yii::$app->db->createCommand()->insert('book_authors', [
            'book_id' => $book2,
            'author_id' => $authorId,
        ])->execute();
        Yii::$app->db->createCommand()->insert('book_authors', [
            'book_id' => $book3,
            'author_id' => $authorId,
        ])->execute();

        $I->amOnRoute('report/index', ['year' => $currentYear]);
        $I->seeResponseCodeIs(200);
        $I->see('Multiple Books Author');
        $I->see('3');
    }

    public function testReportShowsEmptyMessageWhenNoData(IntegrationTester $I): void
    {
        $I->amOnRoute('report/index', ['year' => 1999]);
        $I->seeResponseCodeIs(200);
        $I->see('Нет данных о книгах за 1999 год');
    }

    public function testReportLimitsToTenAuthors(IntegrationTester $I): void
    {
        $currentYear = (int)date('Y');

        for ($i = 1; $i <= 11; $i++) {
            $authorId = $I->haveRecord(Author::class, ['fio' => "Author Rank $i"]);
            $bookId = $I->haveRecord(Book::class, [
                'title' => "Book of Author $i",
                'year' => $currentYear,
                'isbn' => '978316148' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'description' => 'Test',
            ]);
            Yii::$app->db->createCommand()->insert('book_authors', [
                'book_id' => $bookId,
                'author_id' => $authorId,
            ])->execute();
        }

        $I->amOnRoute('report/index', ['year' => $currentYear]);
        $I->seeResponseCodeIs(200);
        $I->seeNumberOfElements('table tbody tr', 10);
    }
}
