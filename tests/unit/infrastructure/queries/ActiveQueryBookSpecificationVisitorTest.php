<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\queries;

use app\domain\specifications\CompositeAndSpecification;
use app\domain\specifications\CompositeOrSpecification;
use app\domain\specifications\FullTextSpecification;
use app\domain\specifications\IsbnPrefixSpecification;
use app\domain\specifications\StatusSpecification;
use app\domain\specifications\YearSpecification;
use app\domain\values\BookStatus;
use app\infrastructure\queries\ActiveQueryBookSpecificationVisitor;
use Codeception\Test\Unit;
use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\db\Expression;

final class ActiveQueryBookSpecificationVisitorTest extends Unit
{
    public function testVisitYearAddsWhereCondition(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with(['year' => 2024]);

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitYear(new YearSpecification(2024));
    }

    public function testVisitIsbnPrefixAddsLikeCondition(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with(['like', 'isbn', '978-3%', false]);

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitIsbnPrefix(new IsbnPrefixSpecification('978-3'));
    }

    public function testVisitFullTextWithEmptyQueryDoesNothing(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->never())->method('andWhere');

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitFullText(new FullTextSpecification(''));
    }

    public function testVisitFullTextMysqlAddsMatchExpression(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with($this->callback(static fn ($conditions): bool => is_array($conditions)
                    && $conditions[0] === 'or'
                    && $conditions[1] instanceof Expression
                    && str_contains($conditions[1]->expression, 'MATCH')));

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitFullText(new FullTextSpecification('clean code'));
    }

    public function testVisitFullTextPgsqlAddsTsVectorExpression(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with($this->callback(static fn ($conditions): bool => is_array($conditions)
                    && $conditions[0] === 'or'
                    && $conditions[1] instanceof Expression
                    && str_contains($conditions[1]->expression, 'to_tsvector')));

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('pgsql'));
        $visitor->visitFullText(new FullTextSpecification('clean code'));
    }

    public function testVisitFullTextPgsqlWithSpecialCharsUsesLikeFallback(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with($this->callback(static fn ($conditions): bool => is_array($conditions)
                    && $conditions[0] === 'or'
                    && isset($conditions[1][0]) && $conditions[1][0] === 'like'));

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('pgsql'));
        $visitor->visitFullText(new FullTextSpecification('!!!'));
    }

    public function testVisitFullTextSqliteUsesLikeFallback(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with($this->callback(static fn ($conditions): bool => is_array($conditions)
                    && $conditions[0] === 'or'
                    && isset($conditions[1][0]) && $conditions[1][0] === 'like'));

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('sqlite'));
        $visitor->visitFullText(new FullTextSpecification('hello'));
    }

    public function testVisitCompositeOrWithMultipleSpecsAddsOrCondition(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with($this->callback(static fn ($conditions): bool => is_array($conditions)
                    && $conditions[0] === 'or'
                    && count($conditions) >= 3));

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitCompositeOr(new CompositeOrSpecification([
            new YearSpecification(2024),
            new IsbnPrefixSpecification('978'),
        ]));
    }

    public function testVisitCompositeOrWithSingleSpecDoesNothing(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with($this->callback(static fn ($conditions): bool => is_array($conditions) && $conditions[0] === 'or' && count($conditions) === 2));

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitCompositeOr(new CompositeOrSpecification([
            new YearSpecification(2024),
        ]));
    }

    public function testVisitCompositeOrWithEmptySpecsDoesNotAddCondition(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->never())->method('andWhere');

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitCompositeOr(new CompositeOrSpecification([]));
    }

    public function testVisitFullTextMysqlWithOnlySpecialCharsUsesLikeFallback(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with($this->callback(static fn ($conditions): bool => is_array($conditions)
                    && $conditions[0] === 'or'
                    && isset($conditions[1][0]) && $conditions[1][0] === 'like'));

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitFullText(new FullTextSpecification('+++'));
    }

    public function testVisitStatusAddsWhereCondition(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with(['status' => BookStatus::Published->value]);

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitStatus(new StatusSpecification(BookStatus::Published));
    }

    public function testVisitCompositeAndDelegatesAllChildren(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->exactly(2))
            ->method('andWhere');

        $composite = new CompositeAndSpecification([
            new StatusSpecification(BookStatus::Published),
            new YearSpecification(2024),
        ]);

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitCompositeAnd($composite);
    }

    public function testVisitCompositeOrWithStatusSpecification(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with($this->callback(static fn ($conditions): bool => is_array($conditions)
                    && $conditions[0] === 'or'
                    && count($conditions) >= 3));

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitCompositeOr(new CompositeOrSpecification([
            new StatusSpecification(BookStatus::Published),
            new YearSpecification(2024),
        ]));
    }

    private function createConnection(string $driverName): Connection
    {
        $connection = new Connection();
        $connection->setDriverName($driverName);

        return $connection;
    }
}
