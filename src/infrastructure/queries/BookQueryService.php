<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\books\factories\BookSearchSpecificationFactory;
use app\application\books\queries\BookColumnFilterDto;
use app\application\books\queries\BookReadDto;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\domain\specifications\BookSpecificationInterface;
use app\domain\specifications\CompositeAndSpecification;
use app\domain\specifications\StatusSpecification;
use app\domain\values\BookStatus;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use yii\db\Expression;

final readonly class BookQueryService extends BaseQueryService implements BookQueryServiceInterface
{
    public function findById(int $id): ?BookReadDto
    {
        return $this->findByIdWithAuthors($id);
    }

    public function findByIdWithAuthors(int $id): ?BookReadDto
    {
        $book = Book::find()->byId($id)->withAuthors()->one($this->db);

        if ($book === null) {
            return null;
        }

        return $this->mapToDto($book, BookReadDto::class);
    }

    public function search(string $term, int $page, int $limit): PagedResultInterface
    {
        $factory = new BookSearchSpecificationFactory();
        $specification = $factory->createFromSearchTerm($term);

        return $this->searchBySpecification($specification, $page, $limit);
    }

    public function searchPublished(string $term, int $page, int $limit): PagedResultInterface
    {
        $factory = new BookSearchSpecificationFactory();
        $searchSpec = $factory->createFromSearchTerm($term);
        $publishedSpec = new StatusSpecification(BookStatus::Published);
        $combinedSpec = new CompositeAndSpecification([$publishedSpec, $searchSpec]);

        return $this->searchBySpecification($combinedSpec, $page, $limit);
    }

    public function searchBySpecification(
        BookSpecificationInterface $specification,
        int $page,
        int $limit,
    ): PagedResultInterface {
        $query = Book::find()->withAuthors()->orderedByCreatedAt();

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->db);
        $specification->accept($visitor);

        return $this->getPagedResult($query, $page, $limit, BookReadDto::class);
    }

    public function searchWithFilters(BookColumnFilterDto $filter, int $page, int $limit): PagedResultInterface
    {
        $query = Book::find()->withAuthors()->orderedByCreatedAt();

        if ($filter->id !== null) {
            $query->andWhere(['books.id' => $filter->id]);
        }

        if ($filter->title !== null) {
            $query->andWhere(['like', 'title', $filter->title]);
        }

        if ($filter->year !== null) {
            $query->andWhere(['year' => $filter->year]);
        }

        if ($filter->isbn !== null) {
            $query->andWhere(['like', 'isbn', $filter->isbn . '%', false]);
        }

        if ($filter->status !== null) {
            $query->andWhere(['status' => $filter->status]);
        }

        if ($filter->author !== null) {
            $subQuery = Author::find()
                ->select(new Expression('1'))
                ->innerJoin('book_authors ba', 'authors.id = ba.author_id')
                ->where('ba.book_id = books.id')
                ->andWhere(['like', 'authors.fio', $filter->author]);

            $query->andWhere(['exists', $subQuery]);
        }

        return $this->getPagedResult($query, $page, $limit, BookReadDto::class);
    }
}
