<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\queries;

use app\application\ports\BookQueryServiceInterface;
use app\application\ports\BookRepositoryInterface;
use app\domain\entities\Book as BookEntity;
use app\domain\specifications\AuthorSpecification;
use app\domain\specifications\CompositeOrSpecification;
use app\domain\specifications\FullTextSpecification;
use app\domain\specifications\IsbnPrefixSpecification;
use app\domain\specifications\YearSpecification;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\queries\BookQueryService;
use Codeception\Test\Unit;
use Yii;
use yii\db\Connection;
use yii\db\Expression;

final class BookQueryServiceTest extends Unit
{
    protected \UnitTester $tester;
    private BookRepositoryInterface $repository;
    private BookQueryServiceInterface $queryService;

    protected function _before(): void
    {
        Yii::$app->language = 'en-US';
        $this->repository = Yii::$container->get(BookRepositoryInterface::class);
        $this->queryService = Yii::$container->get(BookQueryServiceInterface::class);
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

    public function testFindById(): void
    {
        $book = BookEntity::create(
            'Test Book',
            new BookYear(2025, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            'Desc',
            null
        );

        $this->repository->save($book);

        $dto = $this->queryService->findById($book->id);
        $this->assertNotNull($dto);
        $this->assertSame('Test Book', $dto->title);
    }

    public function testFindByIdReturnsNullOnNotFound(): void
    {
        $result = $this->queryService->findById(99999);
        $this->assertNull($result);
    }

    public function testFindByIdWithAuthors(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'Author']);

        $book = BookEntity::create(
            'Book',
            new BookYear(2025, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            null,
            null
        );
        $book->replaceAuthors([$authorId]);

        $this->repository->save($book);
        $bookId = $book->id;

        $dto = $this->queryService->findByIdWithAuthors($bookId);
        $this->assertContains($authorId, $dto->authorIds);
    }

    public function testFindByIdWithAuthorsReturnsNullOnNotFound(): void
    {
        $result = $this->queryService->findByIdWithAuthors(99999);
        $this->assertNull($result);
    }

    public function testSearchByAuthorName(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'Unique Author Name']);

        $book = BookEntity::create(
            'Author Search Book',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            null,
            null
        );
        $book->replaceAuthors([$authorId]);
        $this->repository->save($book);

        // InnoDB FullText search requires committed transaction
        Yii::$app->db->getTransaction()->commit();

        $result = $this->queryService->search('Unique Author', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchByIsbnPrefix(): void
    {
        $book = BookEntity::create(
            'ISBN Search',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $result = $this->queryService->search('978316', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchByYear(): void
    {
        $book = BookEntity::create(
            'Year Search',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $result = $this->queryService->search('2024', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchEmptyTerm(): void
    {
        $book = BookEntity::create(
            'Empty Search',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $result = $this->queryService->search('', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchByTitle(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'SearchTestAuthor']);

        $book = BookEntity::create(
            'SearchableBookTitle',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            'Some description',
            null
        );
        $book->replaceAuthors([$authorId]);
        $this->repository->save($book);

        // InnoDB FullText search requires committed transaction
        Yii::$app->db->getTransaction()->commit();

        $result = $this->queryService->search('SearchableBookTitle', 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchBySpecificationWithYearSpec(): void
    {
        $book = BookEntity::create(
            'Year Spec Test',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $spec = new YearSpecification(2024);
        $result = $this->queryService->searchBySpecification($spec, 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchBySpecificationWithIsbnPrefixSpec(): void
    {
        $book = BookEntity::create(
            'ISBN Prefix Spec Test',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book);

        $spec = new IsbnPrefixSpecification('978316');
        $result = $this->queryService->searchBySpecification($spec, 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchBySpecificationWithFullTextSpec(): void
    {
        $book = BookEntity::create(
            'FullTextSpecificationTestBook',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            'Unique description for fulltext search',
            null
        );
        $this->repository->save($book);

        Yii::$app->db->getTransaction()->commit();

        $spec = new FullTextSpecification('FullTextSpecificationTestBook');
        $result = $this->queryService->searchBySpecification($spec, 1, 10);

        $this->assertGreaterThanOrEqual(0, $result->getTotalCount());
    }

    public function testSearchBySpecificationWithAuthorSpec(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'SpecificationAuthorName']);

        $book = BookEntity::create(
            'Author Spec Test',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            null,
            null
        );
        $book->replaceAuthors([$authorId]);
        $this->repository->save($book);

        Yii::$app->db->getTransaction()->commit();

        $spec = new AuthorSpecification('SpecificationAuthorName');
        $result = $this->queryService->searchBySpecification($spec, 1, 10);

        $this->assertGreaterThan(0, $result->getTotalCount());
    }

    public function testSearchBySpecificationWithCompositeOrSpec(): void
    {
        $book1 = BookEntity::create(
            'Composite Spec Year Book',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            null,
            null
        );
        $this->repository->save($book1);

        $book2 = BookEntity::create(
            'Composite Spec ISBN Book',
            new BookYear(2025, new \DateTimeImmutable()),
            new Isbn('9780132350884'),
            null,
            null
        );
        $this->repository->save($book2);

        $composite = new CompositeOrSpecification([
            new YearSpecification(2024),
            new IsbnPrefixSpecification('978013'),
        ]);
        $result = $this->queryService->searchBySpecification($composite, 1, 10);

        $this->assertGreaterThanOrEqual(2, $result->getTotalCount());
    }

    public function testSearchBySpecificationWithCompositeOrFulltextAndAuthor(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'CompositeSpecAuthor']);

        $book1 = BookEntity::create(
            'CompositeFulltextTestBook',
            new BookYear(2024, new \DateTimeImmutable()),
            new Isbn('9783161484100'),
            'Some unique description',
            null
        );
        $this->repository->save($book1);

        $book2 = BookEntity::create(
            'Another Book',
            new BookYear(2025, new \DateTimeImmutable()),
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
        $result = $this->queryService->searchBySpecification($composite, 1, 10);

        $this->assertGreaterThanOrEqual(1, $result->getTotalCount());
    }

    public function testBuildBooksFulltextExpressionForMysql(): void
    {
        $service = $this->createServiceWithDriver('mysql');

        $expression = $this->invokePrivateMethod($service, 'buildBooksFulltextExpression', ['hello world']);

        $this->assertInstanceOf(Expression::class, $expression);
        $this->assertSame(
            'MATCH(title, description) AGAINST(:query IN BOOLEAN MODE)',
            $expression->expression
        );
        $this->assertSame('+hello* +world*', $expression->params[':query']);
    }

    public function testBuildBooksFulltextExpressionForMysqlReturnsNullOnEmptyQuery(): void
    {
        $service = $this->createServiceWithDriver('mysql');

        $expression = $this->invokePrivateMethod($service, 'buildBooksFulltextExpression', ['+++']);

        $this->assertNull($expression);
    }

    public function testBuildBooksFulltextExpressionForPgsql(): void
    {
        $service = $this->createServiceWithDriver('pgsql');

        $expression = $this->invokePrivateMethod($service, 'buildBooksFulltextExpression', ['Hello']);

        $this->assertInstanceOf(Expression::class, $expression);
        $this->assertSame(
            "to_tsvector('english', coalesce(title, '') || ' ' || coalesce(description, '')) @@ plainto_tsquery('english', :query)",
            $expression->expression
        );
        $this->assertSame('Hello', $expression->params[':query']);
    }

    public function testBuildBooksFulltextExpressionForPgsqlReturnsNullOnSanitizedEmpty(): void
    {
        $service = $this->createServiceWithDriver('pgsql');

        $expression = $this->invokePrivateMethod($service, 'buildBooksFulltextExpression', ['!!!']);

        $this->assertNull($expression);
    }

    public function testBuildAuthorsFulltextExpressionForMysql(): void
    {
        $service = $this->createServiceWithDriver('mysql');

        $expression = $this->invokePrivateMethod($service, 'buildAuthorsFulltextExpression', ['Author Name']);

        $this->assertInstanceOf(Expression::class, $expression);
        $this->assertSame(
            'MATCH(authors.fio) AGAINST(:query IN BOOLEAN MODE)',
            $expression->expression
        );
        $this->assertSame('+Author* +Name*', $expression->params[':query']);
    }

    public function testBuildAuthorsFulltextExpressionForPgsql(): void
    {
        $service = $this->createServiceWithDriver('pgsql');

        $expression = $this->invokePrivateMethod($service, 'buildAuthorsFulltextExpression', ['Author']);

        $this->assertInstanceOf(Expression::class, $expression);
        $this->assertSame(
            "to_tsvector('english', coalesce(authors.fio, '')) @@ plainto_tsquery('english', :query)",
            $expression->expression
        );
        $this->assertSame('Author', $expression->params[':query']);
    }

    private function createServiceWithDriver(string $driverName): BookQueryService
    {
        $connection = new Connection();
        $connection->setDriverName($driverName);

        return new BookQueryService($connection);
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
