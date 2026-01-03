<?php

declare(strict_types=1);

namespace tests\unit\domain\specifications;

use app\domain\specifications\AuthorSpecification;
use app\domain\specifications\BookSearchSpecificationFactory;
use app\domain\specifications\CompositeOrSpecification;
use app\domain\specifications\FullTextSpecification;
use app\domain\specifications\IsbnPrefixSpecification;
use app\domain\specifications\YearSpecification;
use Codeception\Test\Unit;

final class BookSpecificationsTest extends Unit
{
    public function testYearSpecificationReturnsCorrectCriteria(): void
    {
        $spec = new YearSpecification(2024);

        $criteria = $spec->toSearchCriteria();

        $this->assertSame('year', $criteria['type']);
        $this->assertSame(2024, $criteria['value']);
    }

    public function testIsbnPrefixSpecificationReturnsCorrectCriteria(): void
    {
        $spec = new IsbnPrefixSpecification('978-3');

        $criteria = $spec->toSearchCriteria();

        $this->assertSame('isbn_prefix', $criteria['type']);
        $this->assertSame('978-3', $criteria['value']);
    }

    public function testFullTextSpecificationReturnsCorrectCriteria(): void
    {
        $spec = new FullTextSpecification('clean code');

        $criteria = $spec->toSearchCriteria();

        $this->assertSame('fulltext', $criteria['type']);
        $this->assertSame('clean code', $criteria['value']);
    }

    public function testAuthorSpecificationReturnsCorrectCriteria(): void
    {
        $spec = new AuthorSpecification('Martin Fowler');

        $criteria = $spec->toSearchCriteria();

        $this->assertSame('author', $criteria['type']);
        $this->assertSame('Martin Fowler', $criteria['value']);
    }

    public function testCompositeOrSpecificationCombinesMultipleSpecs(): void
    {
        $yearSpec = new YearSpecification(2024);
        $authorSpec = new AuthorSpecification('Kent Beck');
        $composite = new CompositeOrSpecification([$yearSpec, $authorSpec]);

        $criteria = $composite->toSearchCriteria();

        $this->assertSame('or', $criteria['type']);
        $this->assertCount(2, $criteria['value']);
        $this->assertSame('year', $criteria['value'][0]['type']);
        $this->assertSame(2024, $criteria['value'][0]['value']);
        $this->assertSame('author', $criteria['value'][1]['type']);
        $this->assertSame('Kent Beck', $criteria['value'][1]['value']);
    }

    public function testFactoryCreatesYearSpecForFourDigitNumber(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('2024');
        $criteria = $spec->toSearchCriteria();

        $this->assertSame('or', $criteria['type']);
        $this->assertCount(4, $criteria['value']);
        $this->assertSame('year', $criteria['value'][0]['type']);
    }

    public function testFactoryDoesNotTreatSuffixAsYear(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('2024a');
        $criteria = $spec->toSearchCriteria();

        $this->assertSame('or', $criteria['type']);
        $this->assertCount(3, $criteria['value']);
        $this->assertSame('isbn_prefix', $criteria['value'][0]['type']);
    }

    public function testFactoryDoesNotTreatPrefixAsYear(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('a2024');
        $criteria = $spec->toSearchCriteria();

        $this->assertSame('or', $criteria['type']);
        $this->assertCount(3, $criteria['value']);
        $this->assertSame('isbn_prefix', $criteria['value'][0]['type']);
    }

    public function testFactoryCreatesCompositeForRegularTerm(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('clean');
        $criteria = $spec->toSearchCriteria();

        $this->assertSame('or', $criteria['type']);
        $this->assertCount(3, $criteria['value']);
        $this->assertSame('isbn_prefix', $criteria['value'][0]['type']);
        $this->assertSame('fulltext', $criteria['value'][1]['type']);
        $this->assertSame('author', $criteria['value'][2]['type']);
    }

    public function testFactoryReturnsEmptyFullTextForEmptyTerm(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('');
        $criteria = $spec->toSearchCriteria();

        $this->assertSame('fulltext', $criteria['type']);
        $this->assertSame('', $criteria['value']);
    }

    public function testFactoryReturnsEmptyFullTextForWhitespaceTerm(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('   ');
        $criteria = $spec->toSearchCriteria();

        $this->assertSame('fulltext', $criteria['type']);
        $this->assertSame('', $criteria['value']);
    }
}
