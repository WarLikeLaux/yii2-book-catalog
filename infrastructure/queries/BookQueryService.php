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
use yii\data\ActiveDataProvider;
use yii\db\Connection;

final readonly class BookQueryService implements BookQueryServiceInterface
{
    public function __construct(
        private Connection $db,
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
            version: $book->version,
        );
    }
}
