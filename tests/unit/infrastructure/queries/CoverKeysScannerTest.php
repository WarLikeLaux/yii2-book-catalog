<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queries;

use app\application\ports\CoverKeysScannerInterface;
use app\domain\entities\Book as BookEntity;
use app\domain\repositories\BookRepositoryInterface;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use Codeception\Test\Unit;
use Yii;

final class CoverKeysScannerTest extends Unit
{
    private BookRepositoryInterface $repository;
    private CoverKeysScannerInterface $scanner;

    protected function _before(): void
    {
        Yii::$app->language = 'en-US';
        $this->repository = Yii::$container->get(BookRepositoryInterface::class);
        $this->scanner = Yii::$container->get(CoverKeysScannerInterface::class);
        $this->cleanup();
    }

    protected function _after(): void
    {
        $this->cleanup();
    }

    private function cleanup(): void
    {
        Book::deleteAll();
        Author::deleteAll();
    }

    public function testGetReferencedCoverKeysReturnsKeys(): void
    {
        $book1 = BookEntity::create(
            'Book With Cover 1',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            new StoredFileReference('/uploads/abc123.jpg'),
        );
        $this->repository->save($book1);

        $book2 = BookEntity::create(
            'Book With Cover 2',
            new BookYear(2024),
            new Isbn('9780132350884'),
            null,
            new StoredFileReference('/uploads/def456.png'),
        );
        $this->repository->save($book2);

        $keys = $this->scanner->getReferencedCoverKeys();

        $this->assertContains('abc123', $keys);
        $this->assertContains('def456', $keys);
    }

    public function testGetReferencedCoverKeysReturnsEmptyForNullCovers(): void
    {
        $book = BookEntity::create(
            'Book Without Cover',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null,
        );
        $this->repository->save($book);

        $keys = $this->scanner->getReferencedCoverKeys();

        $this->assertSame([], $keys);
    }
}
