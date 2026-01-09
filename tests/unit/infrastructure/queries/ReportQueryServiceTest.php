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
    private static int $isbnCounter = 0;
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
        $currentYear = (int)date('Y');
        $criteria = new ReportCriteria();
        $result = $this->service->getTopAuthorsReport($criteria);

        $this->assertSame($currentYear, $result->year);
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

        $firstAuthor = $result->topAuthors[0];
        $this->assertSame('Author One', $firstAuthor['fio']);
        $this->assertSame(2, (int)$firstAuthor['books_count']);
        $this->assertArrayHasKey('id', $firstAuthor);

        $secondAuthor = $result->topAuthors[1];
        $this->assertSame('Author Two', $secondAuthor['fio']);
        $this->assertSame(1, (int)$secondAuthor['books_count']);
    }

    public function testGetTopAuthorsReportLimitsToTen(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $authorId = $this->createAuthor("Author $i");
            $this->createPublishedBookForAuthor($authorId, "Book $i", 2024);
        }

        $criteria = new ReportCriteria(2024);
        $result = $this->service->getTopAuthorsReport($criteria);

        $this->assertCount(10, $result->topAuthors);
    }

    public function testGetTopAuthorsReportExcludesUnpublishedBooks(): void
    {
        $authorId = $this->createAuthor('Author');
        $this->createPublishedBookForAuthor($authorId, 'Published', 2024);

        $book = new Book();
        $book->title = 'Unpublished';
        $book->year = 2024;
        $book->isbn = '9783161484999';
        $book->description = 'Test description';
        $book->is_published = false;
        $book->save(false);

        Yii::$app->db->createCommand()
            ->insert('{{%book_authors}}', ['book_id' => $book->id, 'author_id' => $authorId])
            ->execute();

        $criteria = new ReportCriteria(2024);
        $result = $this->service->getTopAuthorsReport($criteria);

        $this->assertCount(1, $result->topAuthors);
        $this->assertSame(1, (int)$result->topAuthors[0]['books_count']);
    }

    public function testGetTopAuthorsReportCountsBooksForMultipleAuthors(): void
    {
        $authorId1 = $this->createAuthor('Author One');
        $authorId2 = $this->createAuthor('Author Two');

        $bookId = $this->createPublishedBookForAuthor($authorId1, 'Shared Book', 2024);

        Yii::$app->db->createCommand()
            ->insert('{{%book_authors}}', ['book_id' => $bookId, 'author_id' => $authorId2])
            ->execute();

        $criteria = new ReportCriteria(2024);
        $result = $this->service->getTopAuthorsReport($criteria);

        $this->assertCount(2, $result->topAuthors);

        $firstAuthor = $result->topAuthors[0];
        $this->assertSame('Author One', $firstAuthor['fio']);
        $this->assertSame(1, (int)$firstAuthor['books_count']);
        $this->assertSame($authorId1, (int)$firstAuthor['id']);

        $secondAuthor = $result->topAuthors[1];
        $this->assertSame('Author Two', $secondAuthor['fio']);
        $this->assertSame(1, (int)$secondAuthor['books_count']);
        $this->assertSame($authorId2, (int)$secondAuthor['id']);
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
        $currentYear = (int)date('Y');
        $result = $this->service->getEmptyTopAuthorsReport();

        $this->assertSame($currentYear, $result->year);
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
        $book->isbn = '978' . str_pad((string)++self::$isbnCounter, 10, '0', STR_PAD_LEFT);
        $book->description = 'Test description';
        $book->is_published = true;
        $book->save(false);

        Yii::$app->db->createCommand()
            ->insert('{{%book_authors}}', ['book_id' => $book->id, 'author_id' => $authorId])
            ->execute();

        return $book->id;
    }
}
