<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\queries;

use app\domain\specifications\CompositeOrSpecification;
use app\domain\specifications\FullTextSpecification;
use app\domain\specifications\IsbnPrefixSpecification;
use app\domain\specifications\YearSpecification;
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
            ->with($this->callback(static fn ($expr): bool => $expr instanceof Expression
                    && str_contains($expr->expression, 'MATCH')));

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitFullText(new FullTextSpecification('clean code'));
    }

    public function testVisitFullTextPgsqlAddsTsVectorExpression(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->once())
            ->method('andWhere')
            ->with($this->callback(static fn ($expr): bool => $expr instanceof Expression
                    && str_contains($expr->expression, 'to_tsvector')));

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('pgsql'));
        $visitor->visitFullText(new FullTextSpecification('clean code'));
    }

    public function testVisitFullTextPgsqlWithSpecialCharsOnlyDoesNothing(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->never())->method('andWhere');

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('pgsql'));
        $visitor->visitFullText(new FullTextSpecification('!!!'));
    }

    public function testVisitFullTextSqliteDoesNothing(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->never())->method('andWhere');

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

    public function testVisitFullTextMysqlWithOnlySpecialCharsDoesNothing(): void
    {
        $query = $this->createMock(ActiveQuery::class);
        $query->expects($this->never())->method('andWhere');

        $visitor = new ActiveQueryBookSpecificationVisitor($query, $this->createConnection('mysql'));
        $visitor->visitFullText(new FullTextSpecification('+++'));
    }

    private function createConnection(string $driverName): Connection
    {
        $connection = new Connection();
        $connection->setDriverName($driverName);

        return $connection;
    }
}
