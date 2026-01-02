<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\PagedResultInterface;
use app\domain\entities\Book as BookEntity;
use app\domain\exceptions\AlreadyExistsException;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\exceptions\StaleDataException;
use app\domain\specifications\BookSpecificationInterface;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use DateTimeImmutable;
use ReflectionMethod;
use RuntimeException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\IntegrityException;
use yii\db\StaleObjectException;

final readonly class BookRepository implements BookRepositoryInterface, BookQueryServiceInterface
{
    use DatabaseExceptionHandlerTrait;

    public function __construct(
        private Connection $db
    ) {
    }

    public function save(BookEntity $book): void
    {
        $isNew = $book->getId() === null;
        if ($isNew) {
            $ar = new Book();
            $ar->version = $book->getVersion();
        } else {
            $ar = Book::findOne($book->getId());
            if ($ar === null) {
                throw new EntityNotFoundException('book.error.not_found');
            }
            $ar->version = $book->getVersion();
        }

        $ar->title = $book->getTitle();
        $ar->year = $book->getYear()->value;
        $ar->isbn = $book->getIsbn()->value;
        $ar->description = $book->getDescription();
        $ar->cover_url = $book->getCoverUrl();
        $ar->is_published = (int)$book->isPublished();

        if ($this->existsByIsbn($book->getIsbn()->value, $book->getId())) {
            throw new AlreadyExistsException('book.error.isbn_exists', 409);
        }

        $this->persistBook($ar);

        if ($isNew) {
            $this->assignBookId($book, $ar->id);
        } else {
            $book->incrementVersion();
        }

        $this->syncAuthors($book);
    }

    public function get(int $id): BookEntity
    {
        $ar = Book::find()->where(['id' => $id])->with('authors')->one();
        if ($ar === null) {
            throw new EntityNotFoundException('book.error.not_found');
        }

        /** @var Author[] $authors */
        $authors = $ar->authors;
        $authorIds = array_map(fn(Author $a) => $a->id, $authors);

        return BookEntity::reconstitute(
            id: $ar->id,
            title: $ar->title,
            year: new BookYear($ar->year, new DateTimeImmutable()),
            isbn: new Isbn($ar->isbn),
            description: $ar->description,
            coverUrl: $ar->cover_url,
            authorIds: $authorIds,
            published: (bool)$ar->is_published,
            version: $ar->version
        );
    }

    public function delete(BookEntity $book): void
    {
        $ar = Book::findOne($book->getId());
        if ($ar === null) {
            throw new EntityNotFoundException('book.error.not_found');
        }

        if ($ar->delete() === false) {
            throw new RuntimeException('book.error.delete_failed'); // @codeCoverageIgnore
        }
    }

    public function findById(int $id): ?BookReadDto
    {
        $book = Book::findOne($id);
        if ($book === null) {
            return null;
        }

        return $this->mapToDto($book);
    }

    public function findByIdWithAuthors(int $id): ?BookReadDto
    {
        $book = Book::find()->byId($id)->withAuthors()->one();
        if ($book === null) {
            return null;
        }

        return $this->mapToDto($book);
    }

    public function search(string $term, int $page, int $pageSize): PagedResultInterface
    {
        $query = Book::find()->withAuthors()->orderedByCreatedAt();

        if ($term !== '') {
            $this->applySearchConditions($query, $term);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);

        $models = array_map(
            $this->mapToDto(...),
            $dataProvider->getModels()
        );

        $totalCount = $dataProvider->getTotalCount();
        $totalPages = (int)ceil($totalCount / $pageSize);

        $pagination = new PaginationDto(
            page: $page,
            pageSize: $pageSize,
            totalCount: $totalCount,
            totalPages: $totalPages
        );

        return new QueryResult(
            models: $models,
            totalCount: $totalCount,
            pagination: $pagination
        );
    }

    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool
    {
        $query = Book::find()->andWhere(['isbn' => $isbn]);

        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }

    public function searchBySpecification(
        BookSpecificationInterface $specification,
        int $page,
        int $pageSize
    ): PagedResultInterface {
        $query = Book::find()->withAuthors()->orderedByCreatedAt();

        $this->applySpecification($query, $specification->toSearchCriteria());

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);

        $models = array_map(
            $this->mapToDto(...),
            $dataProvider->getModels()
        );

        $totalCount = $dataProvider->getTotalCount();
        $totalPages = (int)ceil($totalCount / $pageSize);

        $pagination = new PaginationDto(
            page: $page,
            pageSize: $pageSize,
            totalCount: $totalCount,
            totalPages: $totalPages
        );

        return new QueryResult(
            models: $models,
            totalCount: $totalCount,
            pagination: $pagination
        );
    }

    /** @codeCoverageIgnore Защитный код (недостижим из-за валидации домена) */
    private function persistBook(Book $ar): void
    {
        try {
            if (!$ar->save()) {
                $errors = $ar->getFirstErrors();
                $message = $errors !== [] ? array_shift($errors) : 'book.error.save_failed';
                throw new RuntimeException($message);
            }
        } catch (StaleObjectException) {
            throw new StaleDataException();
        } catch (IntegrityException $e) {
            if ($this->isDuplicateError($e)) {
                throw new AlreadyExistsException('book.error.isbn_exists', 409, $e);
            }
            throw $e;
        }
    }

    private function assignBookId(BookEntity $book, int $id): void
    {
        $method = new ReflectionMethod(BookEntity::class, 'setId');
        $method->invoke($book, $id);
    }

    private function syncAuthors(BookEntity $book): void
    {
        $bookId = $book->getId();
        if ($bookId === null) {
            return; // @codeCoverageIgnore
        }

        $storedAuthorIds = $this->getStoredAuthorIds($bookId);
        $currentAuthorIds = $book->getAuthorIds();

        $toDelete = array_values(array_diff($storedAuthorIds, $currentAuthorIds));
        $toAdd = array_values(array_diff($currentAuthorIds, $storedAuthorIds));
        sort($toDelete);
        sort($toAdd);

        if ($toDelete !== []) {
            $this->db->createCommand()->delete('book_authors', [
                'and',
                ['book_id' => $bookId],
                ['in', 'author_id', $toDelete],
            ])->execute();
        }

        if ($toAdd === []) {
            return;
        }

        $rows = array_map(
            fn(int $authorId): array => [$bookId, $authorId],
            $toAdd
        );
        $this->db->createCommand()->batchInsert(
            'book_authors',
            ['book_id', 'author_id'],
            $rows
        )->execute();
    }

    /**
     * @return int[]
     */
    private function getStoredAuthorIds(int $bookId): array
    {
        $ids = $this->db->createCommand(
            'SELECT author_id FROM book_authors WHERE book_id = :bookId'
        )->bindValue(':bookId', $bookId)->queryColumn();

        return array_map(intval(...), $ids);
    }

    private function mapToDto(Book $book): BookReadDto
    {
        $authorNames = [];
        foreach ($book->authors as $author) {
            $authorNames[$author->id] = $author->fio;
        }

        return new BookReadDto(
            id: $book->id,
            title: $book->title,
            year: $book->year,
            description: $book->description,
            isbn: $book->isbn,
            authorIds: array_keys($authorNames),
            authorNames: $authorNames,
            coverUrl: $book->cover_url,
            isPublished: (bool)$book->is_published,
            version: $book->version
        );
    }

    private function applySearchConditions(ActiveQuery $query, string $term): void
    {
        $conditions = ['or'];

        if (preg_match('/^\d{4}$/', $term) === 1) {
            $conditions[] = ['year' => (int)$term];
        }

        $conditions[] = ['like', 'isbn', $term . '%', false];
        $conditions[] = ['like', 'title', $term];
        $conditions[] = ['like', 'description', $term];
        $conditions[] = $this->buildAuthorCondition($term);

        $fulltextExpr = $this->buildBooksFulltextExpression($term);
        if ($fulltextExpr instanceof Expression) {
            $conditions[] = $fulltextExpr;
        }

        $query->andWhere($conditions);
    }

    private function buildBooksFulltextExpression(string $term): Expression|null
    {
        return match ($this->db->driverName) {
            'mysql' => $this->buildMysqlFulltext($term, ['title', 'description']),
            'pgsql' => $this->buildPgsqlFulltext($term, "coalesce(title, '') || ' ' || coalesce(description, '')"),
            default => null,
        };
    }

    private function buildAuthorsFulltextExpression(string $term): Expression|null
    {
        return match ($this->db->driverName) {
            'mysql' => $this->buildMysqlFulltext($term, ['authors.fio']),
            'pgsql' => $this->buildPgsqlFulltext($term, "coalesce(authors.fio, '')"),
            default => null,
        };
    }

    /**
     * @param string[] $columns
     */
    private function buildMysqlFulltext(string $term, array $columns): Expression|null
    {
        $query = $this->prepareMysqlFulltextQuery($term);
        if ($query === '') {
            return null;
        }

        $columnList = implode(', ', $columns);

        return new Expression(
            "MATCH({$columnList}) AGAINST(:query IN BOOLEAN MODE)",
            [':query' => $query]
        );
    }

    private function buildPgsqlFulltext(string $term, string $columnExpression): Expression|null
    {
        $sanitized = trim((string)preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $term));
        if ($sanitized === '') {
            return null;
        }

        return new Expression(
            "to_tsvector('english', {$columnExpression}) @@ plainto_tsquery('english', :query)",
            [':query' => $sanitized]
        );
    }

    private function prepareMysqlFulltextQuery(string $term): string
    {
        $term = (string)preg_replace('/[+\-><()~*\"@]+/', ' ', $term);
        $words = array_filter(explode(' ', trim($term)), fn($w): bool => $w !== '');

        return $words === [] ? '' : '+' . implode('* +', $words) . '*';
    }

    /**
     * @return array<mixed>
     */
    private function buildAuthorCondition(string $term): array
    {
        $subQuery = Author::find()
            ->select(new Expression('1'))
            ->innerJoin('book_authors ba', 'authors.id = ba.author_id')
            ->where('ba.book_id = books.id');

        $authorConditions = ['or', ['like', 'authors.fio', $term]];
        $fulltextExpr = $this->buildAuthorsFulltextExpression($term);
        if ($fulltextExpr instanceof Expression) {
            $authorConditions[] = $fulltextExpr;
        }

        $subQuery->andWhere($authorConditions);

        return ['exists', $subQuery];
    }

    /**
     * @param array{type: string, value: mixed} $criteria
     */
    private function applySpecification(ActiveQuery $query, array $criteria): void
    {
        $value = $criteria['value'];
        $stringValue = is_scalar($value) ? (string)$value : '';

        match ($criteria['type']) {
            'year' => $query->andWhere(['year' => $value]),
            'isbn_prefix' => $query->andWhere(['like', 'isbn', $stringValue . '%', false]),
            'fulltext' => $this->applyFulltextSpecification($query, $stringValue),
            'author' => $query->andWhere($this->buildAuthorCondition($stringValue)),
            'or' => $this->applyOrSpecifications($query, is_array($value) ? $value : []),
            default => null, // @codeCoverageIgnore
        };
    }

    private function applyFulltextSpecification(ActiveQuery $query, string $term): void
    {
        $fulltextExpr = $this->buildBooksFulltextExpression($term);
        if (!$fulltextExpr instanceof Expression) {
            return; // @codeCoverageIgnore
        }

        $query->andWhere($fulltextExpr);
    }

    /**
     * @param array<mixed> $specs
     */
    private function applyOrSpecifications(ActiveQuery $query, array $specs): void
    {
        $conditions = ['or'];

        foreach ($specs as $spec) {
            if (!is_array($spec)) {
                continue; // @codeCoverageIgnore
            }

            $type = $spec['type'] ?? null;
            $value = $spec['value'] ?? null;
            $stringValue = is_scalar($value) ? (string)$value : '';

            match ($type) {
                'year' => $conditions[] = ['year' => $value],
                'isbn_prefix' => $conditions[] = ['like', 'isbn', $stringValue . '%', false],
                'fulltext' => $this->addFulltextCondition($conditions, $stringValue),
                'author' => $conditions[] = $this->buildAuthorCondition($stringValue),
                default => null, // @codeCoverageIgnore
            };
        }

        if (count($conditions) <= 1) {
            return; // @codeCoverageIgnore
        }

        $query->andWhere($conditions);
    }

    /**
     * @param array<mixed> $conditions
     */
    private function addFulltextCondition(array &$conditions, string $term): void
    {
        $fulltextExpr = $this->buildBooksFulltextExpression($term);
        if (!$fulltextExpr instanceof Expression) {
            return; // @codeCoverageIgnore
        }

        $conditions[] = $fulltextExpr;
    }
}
