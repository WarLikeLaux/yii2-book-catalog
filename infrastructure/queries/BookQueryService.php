<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\books\queries\BookReadDto;
use app\application\common\dto\PaginationDto;
use app\application\common\dto\QueryResult;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\domain\specifications\BookSearchSpecificationFactory;
use app\domain\specifications\BookSpecificationInterface;
use app\infrastructure\persistence\Book;
use AutoMapper\AutoMapperInterface;
use yii\data\ActiveDataProvider;
use yii\db\Connection;

final readonly class BookQueryService implements BookQueryServiceInterface
{
    public function __construct(
        private Connection $db,
        private AutoMapperInterface $autoMapper,
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
        $factory = new BookSearchSpecificationFactory();
        $specification = $factory->createFromSearchTerm($term);

        return $this->searchBySpecification($specification, $page, $pageSize);
    }

    public function searchBySpecification(
        BookSpecificationInterface $specification,
        int $page,
        int $pageSize,
    ): PagedResultInterface {
        $query = Book::find()->withAuthors()->orderedByCreatedAt();

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->db);
        $specification->accept($visitor);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);

        $models = array_map(
            $this->mapToDto(...),
            $dataProvider->getModels(),
        );

        $totalCount = $dataProvider->getTotalCount();
        $totalPages = (int)ceil($totalCount / $pageSize);

        $pagination = new PaginationDto(
            page: $page,
            pageSize: $pageSize,
            totalCount: $totalCount,
            totalPages: $totalPages,
        );

        return new QueryResult(
            models: $models,
            totalCount: $totalCount,
            pagination: $pagination,
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

    private function mapToDto(Book $book): BookReadDto
    {
        $dto = $this->autoMapper->map($book, BookReadDto::class);
        assert($dto instanceof BookReadDto);

        return $dto;
    }
}
