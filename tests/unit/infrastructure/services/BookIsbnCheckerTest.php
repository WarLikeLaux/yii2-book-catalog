<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services;

use app\application\ports\BookIsbnCheckerInterface;
use app\domain\entities\Book;
use app\domain\repositories\BookRepositoryInterface;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\infrastructure\persistence\Book as BookAR;
use Codeception\Test\Unit;
use Yii;

final class BookIsbnCheckerTest extends Unit
{
    private BookIsbnCheckerInterface $checker;
    private BookRepositoryInterface $repository;

    protected function _before(): void
    {
        $this->checker = Yii::$container->get(BookIsbnCheckerInterface::class);
        $this->repository = Yii::$container->get(BookRepositoryInterface::class);
        BookAR::deleteAll();
    }

    public function testExistsByIsbnReturnsTrue(): void
    {
        $book = Book::create(
            'ISBN Test',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null,
        );
        $this->repository->save($book);

        $this->assertTrue($this->checker->existsByIsbn('9783161484100'));
    }

    public function testExistsByIsbnReturnsFalse(): void
    {
        $this->assertFalse($this->checker->existsByIsbn('9783161484100'));
    }

    public function testExistsByIsbnWithExcludeId(): void
    {
        $book = Book::create(
            'ISBN Exclude Test',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null,
        );
        $this->repository->save($book);

        $this->assertFalse($this->checker->existsByIsbn('9783161484100', $book->id));
        $this->assertTrue($this->checker->existsByIsbn('9783161484100', 99999));
    }
}
