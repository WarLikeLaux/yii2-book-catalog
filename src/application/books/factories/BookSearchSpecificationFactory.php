<?php

declare(strict_types=1);

namespace app\application\books\factories;

use app\domain\specifications\AuthorSpecification;
use app\domain\specifications\BookSpecificationInterface;
use app\domain\specifications\CompositeAndSpecification;
use app\domain\specifications\CompositeOrSpecification;
use app\domain\specifications\FullTextSpecification;
use app\domain\specifications\IsbnPrefixSpecification;
use app\domain\specifications\StatusSpecification;
use app\domain\specifications\YearSpecification;
use app\domain\values\BookStatus;

final readonly class BookSearchSpecificationFactory
{
    public function createFromSearchTerm(string $term): BookSpecificationInterface
    {
        $term = trim($term);

        if ($term === '') {
            return new FullTextSpecification('');
        }

        $specs = [];

        if (preg_match('/^\d{4}$/', $term) === 1) {
            $specs[] = new YearSpecification((int)$term);
        }

        $specs[] = new IsbnPrefixSpecification($term);
        $specs[] = new FullTextSpecification($term);
        $specs[] = new AuthorSpecification($term);

        return new CompositeOrSpecification($specs);
    }

    public function createFromColumnFilters(
        ?string $title,
        ?int $year,
        ?string $isbn,
        ?string $status,
        ?string $author,
    ): BookSpecificationInterface {
        $specs = [];

        if ($title !== null && $title !== '') {
            $specs[] = new FullTextSpecification($title);
        }

        if ($year !== null) {
            $specs[] = new YearSpecification($year);
        }

        if ($isbn !== null && $isbn !== '') {
            $specs[] = new IsbnPrefixSpecification($isbn);
        }

        if ($status !== null && $status !== '') {
            $bookStatus = BookStatus::tryFrom($status);

            if ($bookStatus !== null) {
                $specs[] = new StatusSpecification($bookStatus);
            }
        }

        if ($author !== null && $author !== '') {
            $specs[] = new AuthorSpecification($author);
        }

        if ($specs === []) {
            return new FullTextSpecification('');
        }

        if (count($specs) === 1) {
            return $specs[0];
        }

        return new CompositeAndSpecification($specs);
    }
}
