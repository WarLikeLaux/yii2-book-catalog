<?php

declare(strict_types=1);

namespace app\infrastructure\queries;

use app\domain\specifications\AuthorSpecification;
use app\domain\specifications\BookSpecificationInterface;
use app\domain\specifications\BookSpecificationVisitorInterface;
use app\domain\specifications\CompositeOrSpecification;
use app\domain\specifications\FullTextSpecification;
use app\domain\specifications\IsbnPrefixSpecification;
use app\domain\specifications\YearSpecification;
use app\infrastructure\persistence\Author;
use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\db\Expression;

final readonly class ActiveQueryBookSpecificationVisitor implements BookSpecificationVisitorInterface
{
    public function __construct(
        private ActiveQuery $query,
        private Connection $db,
    ) {
    }

    public function visitYear(YearSpecification $spec): void
    {
        $this->query->andWhere(['year' => $spec->getYear()]);
    }

    public function visitIsbnPrefix(IsbnPrefixSpecification $spec): void
    {
        $this->query->andWhere(['like', 'isbn', $spec->getPrefix() . '%', false]);
    }

    public function visitFullText(FullTextSpecification $spec): void
    {
        $term = $spec->getQuery();

        if ($term === '') {
            return;
        }

        $fulltextExpr = $this->buildBooksFulltextExpression($term);

        if (!($fulltextExpr instanceof Expression)) {
            return;
        }

        $this->query->andWhere($fulltextExpr);
    }

    public function visitAuthor(AuthorSpecification $spec): void
    {
        $this->query->andWhere($this->buildAuthorCondition($spec->getAuthorName()));
    }

    public function visitCompositeOr(CompositeOrSpecification $spec): void
    {
        $conditions = ['or'];

        foreach ($spec->getSpecifications() as $childSpec) {
            $condition = $this->buildConditionFor($childSpec);

            if ($condition === null) {
                continue; // @codeCoverageIgnore
            }

            $conditions[] = $condition;
        }

        if (count($conditions) <= 1) {
            return;
        }

        $this->query->andWhere($conditions);
    }

    /**
     * @return array<int|string, mixed>|Expression|null
     */
    private function buildConditionFor(BookSpecificationInterface $spec): array|Expression|null
    {
        return match (true) {
            $spec instanceof YearSpecification => ['year' => $spec->getYear()],
            $spec instanceof IsbnPrefixSpecification => ['like', 'isbn', $spec->getPrefix() . '%', false],
            $spec instanceof FullTextSpecification => $this->buildBooksFulltextExpression($spec->getQuery()),
            $spec instanceof AuthorSpecification => $this->buildAuthorCondition($spec->getAuthorName()),
            default => null, // @codeCoverageIgnore
        };
    }

    private function buildBooksFulltextExpression(string $term): Expression|null
    {
        return match ($this->db->driverName) {
            'mysql' => $this->buildMysqlFulltext($term, ['title', 'description']),
            'pgsql' => $this->buildPgsqlFulltext($term, "coalesce(title, '') || ' ' || coalesce(description, '')"),
            default => null,
        };
    }

    /** @codeCoverageIgnore */
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
            [':query' => $query],
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
            [':query' => $sanitized],
        );
    }

    private function prepareMysqlFulltextQuery(string $term): string
    {
        $term = (string)preg_replace('/[+\-><()~*\"@]+/', ' ', $term);
        $words = array_filter(explode(' ', trim($term)), static fn($w): bool => $w !== '');

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
}
