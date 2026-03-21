<?php

declare(strict_types=1);

namespace app\application\books\factories;

use app\domain\specifications\AuthorSpecification;
use app\domain\specifications\BookSpecificationInterface;
use app\domain\specifications\CompositeOrSpecification;
use app\domain\specifications\FullTextSpecification;
use app\domain\specifications\IsbnPrefixSpecification;
use app\domain\specifications\YearSpecification;

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
}
