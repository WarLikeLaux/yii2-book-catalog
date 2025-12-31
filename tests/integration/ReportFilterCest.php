<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\User;

final class ReportFilterCest
{
    public function _before(IntegrationTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testReportWithYearFilter(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Report Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Book 2023',
            'year' => 2023,
            'isbn' => '9783161484100',
            'description' => 'Test',
            'is_published' => 1,
        ]);
        Yii::$app->db->createCommand()
            ->insert('book_authors', ['book_id' => $bookId, 'author_id' => $authorId])
            ->execute();

        $I->amOnRoute('report/index', ['year' => 2023]);
        $I->seeResponseCodeIs(200);
        $I->see('Report Author');
    }

    public function testReportWithoutFilter(IntegrationTester $I): void
    {
        $I->amOnRoute('report/index');
        $I->seeResponseCodeIs(200);
    }
}
