<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\domain\specifications\BookSpecificationInterface;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\db\Expression;

final readonly class BookQueryService implements BookQueryServiceInterface
{
    public function __construct(
        private Connection $db
    ) {
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
            "MATCH($columnList) AGAINST(:query IN BOOLEAN MODE)",
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
            "to_tsvector('english', $columnExpression) @@ plainto_tsquery('english', :query)",
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
