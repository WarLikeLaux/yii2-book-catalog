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
    public function findById(int $id): ?BookReadDto
    {
        $book = Book::find()->byId($id)->one($this->db);

        if ($book === null) {
            return null;
        }

        return $this->mapToDto($book, BookReadDto::class);
    }

    public function findByIdWithAuthors(int $id): ?BookReadDto
    {
        $book = Book::find()->byId($id)->withAuthors()->one($this->db);

        if ($book === null) {
            return null;
        }

        return $this->mapToDto($book, BookReadDto::class);
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

        return $this->getPagedResult($query, $page, $pageSize, BookReadDto::class);
    }

    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool
    {
        return $this->exists(Book::find()->where(['isbn' => $isbn]), $excludeId);
    }

    /**
     * @return string[]
     */
    public function getReferencedCoverKeys(): array
    {
        $urls = Book::find()
            ->select('cover_url')
            ->where(['IS NOT', 'cover_url', null])
            ->column($this->db);

        $keys = array_map(
            $this->extractCoverKeyFromUrl(...),
            $urls,
        );

        $keys = array_filter($keys, static fn(string $key): bool => $key !== '');

        return array_values(array_unique($keys));
    }

    private function extractCoverKeyFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        $target = is_string($path) && $path !== '' ? $path : $url;

        return pathinfo($target, PATHINFO_FILENAME);
    }
}
