<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\application\books\queries\BookReadDto;
use app\application\ports\BookQueryServiceInterface;
use app\application\ports\PagedResultInterface;
use app\domain\specifications\BookSearchSpecificationFactory;
use app\domain\specifications\BookSpecificationInterface;
use app\infrastructure\persistence\Book;

final readonly class BookQueryService extends BaseQueryService implements BookQueryServiceInterface
{
    /**
     * Retrieve a book by its identifier.
     *
     * @return BookReadDto|null The DTO representing the book with the given identifier, or `null` if no book exists.
     */
    public function findById(int $id): ?BookReadDto
    {
        $book = Book::find()->byId($id)->one($this->db);

        if ($book === null) {
            return null;
        }

        return $this->mapToDto($book, BookReadDto::class);
    }

    /**
     * Retrieve a book by its ID including associated authors and map it to a BookReadDto.
     *
     * @param int $id The book's ID.
     * @return BookReadDto|null The DTO representing the book with its authors, or `null` if no book was found.
     */
    public function findByIdWithAuthors(int $id): ?BookReadDto
    {
        $book = Book::find()->byId($id)->withAuthors()->one($this->db);

        if ($book === null) {
            return null;
        }

        return $this->mapToDto($book, BookReadDto::class);
    }

    /**
     * Searches books matching a free-text term and returns a paginated result.
     *
     * @param string $term The search term used to build the book search specification.
     * @param int $page The 1-based page number to retrieve.
     * @param int $pageSize The number of items per page.
     * @return PagedResultInterface A paged result containing BookReadDto items for the requested page.
     */
    public function search(string $term, int $page, int $pageSize): PagedResultInterface
    {
        $factory = new BookSearchSpecificationFactory();
        $specification = $factory->createFromSearchTerm($term);

        return $this->searchBySpecification($specification, $page, $pageSize);
    }

    /**
     * Searches for books that satisfy the given specification and returns a paginated set of BookReadDto results.
     *
     * @param BookSpecificationInterface $specification Specification used to filter the book query.
     * @param int $page Page number.
     * @param int $pageSize Number of items per page.
     * @return PagedResultInterface Paged result containing instances of BookReadDto for the requested page.
     */
    public function searchBySpecification(
        BookSpecificationInterface $specification,
        int $page,
        int $pageSize,
    ): PagedResultInterface {
        $query = Book::find()->withAuthors()->orderedByCreatedAt();

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->db);
        $specification->accept($visitor);

        return $this->getPagedResult($query, $page, $pageSize, BookReadDto::class);
    }

    / **
     * Determines whether a book with the given ISBN exists, optionally excluding a specific book ID.
     *
     * @param string $isbn The ISBN to check for.
     * @param int|null $excludeId ID to exclude from the check, or null to include all records.
     * @return bool `true` if a book with the ISBN exists (excluding `$excludeId` when provided), `false` otherwise.
     */
    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool
    {
        return $this->exists(Book::find()->where(['isbn' => $isbn]), $excludeId);
    }
}