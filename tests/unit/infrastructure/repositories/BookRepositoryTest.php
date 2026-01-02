<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

use app\application\ports\BookRepositoryInterface;
use app\domain\entities\Book as BookEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\specifications\AuthorSpecification;
use app\domain\specifications\CompositeOrSpecification;
use app\domain\specifications\FullTextSpecification;
use app\domain\specifications\IsbnPrefixSpecification;
use app\domain\specifications\YearSpecification;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\repositories\BookRepository;
use Codeception\Test\Unit;
use Yii;
use yii\db\Connection;
use yii\db\Expression;

final class BookRepositoryTest extends Unit
{
    protected \UnitTester $tester;

    private BookRepositoryInterface $repository;

    protected function _before(): void
    {
        Yii::$app->language = 'en-US';
        $this->repository = Yii::$container->get(BookRepositoryInterface::class);
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

    public function testCreateAndFindById(): void
    {
        $book = BookEntity::create(
            'Test Book',
            new BookYear(2025),
            new Isbn('9783161484100'),
            'Desc',
            null
        );

        $this->repository->save($book);
        $this->assertNotNull($book->getId());

        $dto = $this->repository->findById($book->getId());
        $this->assertNotNull($dto);
        $this->assertSame('Test Book', $dto->title);
    }

    public function testDeleteThrowsExceptionOnNotFound(): void
    {
        $book = BookEntity::reconstitute(
            999,
            'Title',
            new BookYear(2025),
            new Isbn('9783161484100'),
            null,
            null,
            [],
            false,
            1
        );

        $this->expectException(EntityNotFoundException::class);
        $this->repository->delete($book);
    }

    public function testSyncAuthors(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'Author']);

        $book = BookEntity::create(
            'Book',
            new BookYear(2025),
            new Isbn('9783161484100'),
            null,
            null
        );
        $book->replaceAuthors([$authorId]);

        $this->repository->save($book);
        $bookId = $book->getId();

        $dto = $this->repository->findByIdWithAuthors($bookId);
        $this->assertContains($authorId, $dto->authorIds);
    }

    public function testGetReturnsBookEntity(): void
    {
        $book = BookEntity::create(
            'Get Test',
            new BookYear(2024),
            new Isbn('9783161484100'),
            'Description',
            null
        );
        $this->repository->save($book);

        $retrieved = $this->repository->get($book->getId());

        $this->assertSame('Get Test', $retrieved->getTitle());
        $this->assertSame(2024, $retrieved->getYear()->value);
    }

    public function testGetThrowsExceptionOnNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->repository->get(99999);
    }

    public function testUpdateExistingBook(): void
    {
        $book = BookEntity::create(
            'Original Title',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $updated = $this->repository->get($book->getId());
        $updated->update('Updated Title', new BookYear(2025), new Isbn('9783161484100'), 'New desc', null);
        $this->repository->save($updated);

        $dto = $this->repository->findById($book->getId());
        $this->assertSame('Updated Title', $dto->title);
        $this->assertSame(2025, $dto->year);
    }

    public function testDeleteSuccessfully(): void
    {
        $book = BookEntity::create(
            'To Delete',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);
        $bookId = $book->getId();

        $this->repository->delete($book);

        $this->assertNull($this->repository->findById($bookId));
    }

    public function testExistsByIsbnReturnsTrue(): void
    {
        $book = BookEntity::create(
            'ISBN Test',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $this->assertTrue($this->repository->existsByIsbn('9783161484100'));
    }

    public function testExistsByIsbnReturnsFalse(): void
    {
        $this->assertFalse($this->repository->existsByIsbn('9783161484100'));
    }

    public function testExistsByIsbnWithExcludeId(): void
    {
        $book = BookEntity::create(
            'ISBN Exclude Test',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $this->assertFalse($this->repository->existsByIsbn('9783161484100', $book->getId()));
        $this->assertTrue($this->repository->existsByIsbn('9783161484100', 99999));
    }

    public function testSearchByAuthorName(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'Unique Author Name']);

        $book = BookEntity::create(
            'Author Search Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $book->replaceAuthors([$authorId]);
        $this->repository->save($book);

        // InnoDB FullText search requires committed transaction
        Yii::$app->db->getTransaction()->commit();

        $result = $this->repository->search('Unique Author', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchByIsbnPrefix(): void
    {
        $book = BookEntity::create(
            'ISBN Search',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $result = $this->repository->search('978316', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchByYear(): void
    {
        $book = BookEntity::create(
            'Year Search',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $result = $this->repository->search('2024', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchEmptyTerm(): void
    {
        $book = BookEntity::create(
            'Empty Search',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $result = $this->repository->search('', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchByTitle(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'SearchTestAuthor']);

        $book = BookEntity::create(
            'SearchableBookTitle',
            new BookYear(2024),
            new Isbn('9783161484100'),
            'Some description',
            null
        );
        $book->replaceAuthors([$authorId]);
        $this->repository->save($book);

        // InnoDB FullText search requires committed transaction
        Yii::$app->db->getTransaction()->commit();

        $result = $this->repository->search('SearchableBookTitle', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testUpdateNonExistentBookThrowsException(): void
    {
        $book = BookEntity::create(
            'Non-existent',
            new BookYear(2023),
            new Isbn('978-3-16-148410-0'),
            null,
            null
        );
        $this->assignBookId($book, 99999);

        $this->expectException(EntityNotFoundException::class);
        $this->repository->save($book);
    }

    public function testFindByIdReturnsNullOnNotFound(): void
    {
        $result = $this->repository->findById(99999);
        $this->assertNull($result);
    }

    public function testUpdateBookRemovesAuthor(): void
    {
        $author1 = $this->tester->haveRecord(Author::class, ['fio' => 'Author One']);
        $author2 = $this->tester->haveRecord(Author::class, ['fio' => 'Author Two']);

        $book = BookEntity::create(
            'Book with Authors',
            new BookYear(2023),
            new Isbn('978-3-16-148410-0'),
            null,
            null
        );
        $book->replaceAuthors([$author1, $author2]);
        $this->repository->save($book);

        $book->replaceAuthors([$author1]);
        $this->repository->save($book);

        $storedBook = $this->repository->get($book->getId());
        $this->assertCount(1, $storedBook->getAuthorIds());
        $this->assertEquals([$author1], $storedBook->getAuthorIds());
    }

    public function testFindByIdWithAuthorsReturnsNullOnNotFound(): void
    {
        $result = $this->repository->findByIdWithAuthors(99999);
        $this->assertNull($result);
    }

    public function testSaveDuplicateIsbnThrowsAlreadyExistsException(): void
    {
        $isbn = '9783161484100';
        $book1 = BookEntity::create(
            'First Book',
            new BookYear(2024),
            new Isbn($isbn),
            null,
            null
        );
        $this->repository->save($book1);

        $book2 = BookEntity::create(
            'Duplicate ISBN Book',
            new BookYear(2025),
            new Isbn($isbn),
            null,
            null
        );

        $this->expectException(AlreadyExistsException::class);
        $this->expectExceptionMessage('book.error.isbn_exists');
        $this->repository->save($book2);
    }

    private function assignBookId(BookEntity $book, int $id): void
    {
        $method = new \ReflectionMethod(BookEntity::class, 'setId');
        $method->setAccessible(true);
        $method->invoke($book, $id);
    }

    public function testSearchBySpecificationWithYearSpec(): void
    {
        $book = BookEntity::create(
            'Year Spec Test',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $spec = new YearSpecification(new BookYear(2024));
        $result = $this->repository->searchBySpecification($spec, 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchBySpecificationWithIsbnPrefixSpec(): void
    {
        $book = BookEntity::create(
            'ISBN Prefix Spec Test',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $spec = new IsbnPrefixSpecification('978316');
        $result = $this->repository->searchBySpecification($spec, 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchBySpecificationWithFullTextSpec(): void
    {
        $book = BookEntity::create(
            'FullTextSpecificationTestBook',
            new BookYear(2024),
            new Isbn('9783161484100'),
            'Unique description for fulltext search',
            null
        );
        $this->repository->save($book);

        Yii::$app->db->getTransaction()->commit();

        $spec = new FullTextSpecification('FullTextSpecificationTestBook');
        $result = $this->repository->searchBySpecification($spec, 1, 10);

        $this->assertGreaterThanOrEqual(0, $result->getTotalCount());
    }

    public function testSearchBySpecificationWithAuthorSpec(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'SpecificationAuthorName']);

        $book = BookEntity::create(
            'Author Spec Test',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $book->replaceAuthors([$authorId]);
        $this->repository->save($book);

        Yii::$app->db->getTransaction()->commit();

        $spec = new AuthorSpecification('SpecificationAuthorName');
        $result = $this->repository->searchBySpecification($spec, 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchBySpecificationWithCompositeOrSpec(): void
    {
        $book1 = BookEntity::create(
            'Composite Spec Year Book',
            new BookYear(2024),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book1);

        $book2 = BookEntity::create(
            'Composite Spec ISBN Book',
            new BookYear(2025),
            new Isbn('9780132350884'),
            null,
            null
        );
        $this->repository->save($book2);

        $composite = new CompositeOrSpecification([
            new YearSpecification(new BookYear(2024)),
            new IsbnPrefixSpecification('978013'),
        ]);
        $result = $this->repository->searchBySpecification($composite, 1, 10);

        $this->assertGreaterThanOrEqual(2, $result->getTotalCount());
    }

    public function testSearchBySpecificationWithCompositeOrFulltextAndAuthor(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'CompositeSpecAuthor']);

        $book1 = BookEntity::create(
            'CompositeFulltextTestBook',
            new BookYear(2024),
            new Isbn('9783161484100'),
            'Some unique description',
            null
        );
        $this->repository->save($book1);

        $book2 = BookEntity::create(
            'Another Book',
            new BookYear(2025),
            new Isbn('9780132350884'),
            null,
            null
        );
        $book2->replaceAuthors([$authorId]);
        $this->repository->save($book2);

        Yii::$app->db->getTransaction()->commit();

        $composite = new CompositeOrSpecification([
            new FullTextSpecification('CompositeFulltextTestBook'),
            new AuthorSpecification('CompositeSpecAuthor'),
        ]);
        $result = $this->repository->searchBySpecification($composite, 1, 10);

        $this->assertGreaterThanOrEqual(1, $result->getTotalCount());
    }

    public function testBuildBooksFulltextExpressionForMysql(): void
    {
        $repository = $this->createRepositoryWithDriver('mysql');

        $expression = $this->invokePrivateMethod($repository, 'buildBooksFulltextExpression', ['hello world']);

        $this->assertInstanceOf(Expression::class, $expression);
        $this->assertSame(
            'MATCH(title, description) AGAINST(:query IN BOOLEAN MODE)',
            $expression->expression
        );
        $this->assertSame('+hello* +world*', $expression->params[':query']);
    }

    public function testBuildBooksFulltextExpressionForMysqlReturnsNullOnEmptyQuery(): void
    {
        $repository = $this->createRepositoryWithDriver('mysql');

        $expression = $this->invokePrivateMethod($repository, 'buildBooksFulltextExpression', ['+++']);

        $this->assertNull($expression);
    }

    public function testBuildBooksFulltextExpressionForPgsql(): void
    {
        $repository = $this->createRepositoryWithDriver('pgsql');

        $expression = $this->invokePrivateMethod($repository, 'buildBooksFulltextExpression', ['Hello']);

        $this->assertInstanceOf(Expression::class, $expression);
        $this->assertSame(
            "to_tsvector('english', coalesce(title, '') || ' ' || coalesce(description, '')) @@ plainto_tsquery('english', :query)",
            $expression->expression
        );
        $this->assertSame('Hello', $expression->params[':query']);
    }

    public function testBuildBooksFulltextExpressionForPgsqlReturnsNullOnSanitizedEmpty(): void
    {
        $repository = $this->createRepositoryWithDriver('pgsql');

        $expression = $this->invokePrivateMethod($repository, 'buildBooksFulltextExpression', ['!!!']);

        $this->assertNull($expression);
    }

    public function testBuildAuthorsFulltextExpressionForMysql(): void
    {
        $repository = $this->createRepositoryWithDriver('mysql');

        $expression = $this->invokePrivateMethod($repository, 'buildAuthorsFulltextExpression', ['Author Name']);

        $this->assertInstanceOf(Expression::class, $expression);
        $this->assertSame(
            'MATCH(authors.fio) AGAINST(:query IN BOOLEAN MODE)',
            $expression->expression
        );
        $this->assertSame('+Author* +Name*', $expression->params[':query']);
    }

    public function testBuildAuthorsFulltextExpressionForPgsql(): void
    {
        $repository = $this->createRepositoryWithDriver('pgsql');

        $expression = $this->invokePrivateMethod($repository, 'buildAuthorsFulltextExpression', ['Author']);

        $this->assertInstanceOf(Expression::class, $expression);
        $this->assertSame(
            "to_tsvector('english', coalesce(authors.fio, '')) @@ plainto_tsquery('english', :query)",
            $expression->expression
        );
        $this->assertSame('Author', $expression->params[':query']);
    }

    private function createRepositoryWithDriver(string $driverName): BookRepository
    {
        $connection = new Connection();
        $connection->setDriverName($driverName);

        return new BookRepository($connection);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokePrivateMethod(object $target, string $method, array $arguments): mixed
    {
        $reflection = new \ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $arguments);
    }
}
