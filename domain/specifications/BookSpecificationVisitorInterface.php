<?php

declare(strict_types=1);

namespace app\domain\specifications;

interface BookSpecificationVisitorInterface
{
    public function visitYear(YearSpecification $spec): void;

    public function visitIsbnPrefix(IsbnPrefixSpecification $spec): void;

    public function visitFullText(FullTextSpecification $spec): void;

    public function visitAuthor(AuthorSpecification $spec): void;

    public function visitCompositeOr(CompositeOrSpecification $spec): void;

    public function visitCompositeAnd(CompositeAndSpecification $spec): void;

    public function visitStatus(StatusSpecification $spec): void;
}
