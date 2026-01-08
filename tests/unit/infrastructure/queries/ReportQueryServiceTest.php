<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queries;

use app\application\reports\queries\ReportCriteria;
use app\application\reports\queries\ReportDto;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\queries\ReportQueryService;
use Codeception\Test\Unit;
use DbCleaner;
use Yii;

final class ReportQueryServiceTest extends Unit
{
    private ReportQueryService $service;

    protected function _before(): void
    {
        DbCleaner::clear(['book_authors', 'books', 'authors']);
        $this->service = new ReportQueryService(Yii::$app->db);
    }

    public function testGetTopAuthorsReportReturnsReportDto(): void
    {
        $criteria = new ReportCriteria(2024);
        $result = $this->service->getTopAuthorsReport($criteria);

        $this->assertInstanceOf(ReportDto::class, $result);
        $this->assertSame(2024, $result->year);
        $this->assertIsArray($result->topAuthors);
    }

    public function testGetTopAuthorsReportUsesCurrentYearByDefault(): void
    {
        $criteria = new ReportCriteria(null);
        $result = $this->service->getTopAuthorsReport($criteria);

        $this->assertSame((int)date('Y'), $result->year);
    }

    public function testGetTopAuthorsReportReturnsSortedByBookCount(): void
    {
        $authorId1 = $this->createAuthor('Author One');
        $authorId2 = $this->createAuthor('Author Two');

        $this->createPublishedBookForAuthor($authorId1, 'Book 1', 2024);
        $this->createPublishedBookForAuthor($authorId1, 'Book 2', 2024);
        $this->createPublishedBookForAuthor($authorId2, 'Book 3', 2024);

        $criteria = new ReportCriteria(2024);
        $result = $this->service->getTopAuthorsReport($criteria);

        $this->assertCount(2, $result->topAuthors);
        $this->assertSame('Author One', $result->topAuthors[0]['fio']);
        $this->assertSame('2', (string)$result->topAuthors[0]['books_count']);
    }

    public function testGetEmptyTopAuthorsReport(): void
    {
        $result = $this->service->getEmptyTopAuthorsReport(2020);

        $this->assertInstanceOf(ReportDto::class, $result);
        $this->assertSame(2020, $result->year);
        $this->assertSame([], $result->topAuthors);
    }

    public function testGetEmptyTopAuthorsReportUsesCurrentYearByDefault(): void
    {
        $result = $this->service->getEmptyTopAuthorsReport();

        $this->assertSame((int)date('Y'), $result->year);
    }

    private function createAuthor(string $fio): int
    {
        $author = new Author();
        $author->fio = $fio;
        $author->save(false);

        return $author->id;
    }

    private function createPublishedBookForAuthor(int $authorId, string $title, int $year): int
    {
        $book = new Book();
        $book->title = $title;
        $book->year = $year;
        $book->isbn = '978316148410' . random_int(0, 9);
        $book->description = 'Test description';
        $book->is_published = true;
        $book->save(false);

        Yii::$app->db->createCommand()
            ->insert('book_authors', ['book_id' => $book->id, 'author_id' => $authorId])
            ->execute();

        return $book->id;
    }
}
