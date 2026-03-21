<?php

declare(strict_types=1);

namespace tests\unit\application\books\factories;

use app\application\books\factories\BookSearchSpecificationFactory;
use app\domain\specifications\AuthorSpecification;
use app\domain\specifications\CompositeAndSpecification;
use app\domain\specifications\CompositeOrSpecification;
use app\domain\specifications\FullTextSpecification;
use app\domain\specifications\IsbnPrefixSpecification;
use app\domain\specifications\StatusSpecification;
use app\domain\specifications\YearSpecification;
use app\domain\values\BookStatus;
use PHPUnit\Framework\TestCase;

final class BookSearchSpecificationFactoryTest extends TestCase
{
    public function testCreatesYearSpecForFourDigitNumber(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('2024');

        $this->assertInstanceOf(CompositeOrSpecification::class, $spec);
        $children = $spec->getSpecifications();
        $this->assertCount(4, $children);
        $this->assertInstanceOf(YearSpecification::class, $children[0]);
        $this->assertSame(2024, $children[0]->getYear());
    }

    public function testDoesNotTreatSuffixAsYear(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('2024a');

        $this->assertInstanceOf(CompositeOrSpecification::class, $spec);
        $children = $spec->getSpecifications();
        $this->assertCount(3, $children);
        $this->assertInstanceOf(IsbnPrefixSpecification::class, $children[0]);
    }

    public function testDoesNotTreatPrefixAsYear(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('a2024');

        $this->assertInstanceOf(CompositeOrSpecification::class, $spec);
        $children = $spec->getSpecifications();
        $this->assertCount(3, $children);
        $this->assertInstanceOf(IsbnPrefixSpecification::class, $children[0]);
    }

    public function testCreatesCompositeForRegularTerm(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('clean');

        $this->assertInstanceOf(CompositeOrSpecification::class, $spec);
        $children = $spec->getSpecifications();
        $this->assertCount(3, $children);
        $this->assertInstanceOf(IsbnPrefixSpecification::class, $children[0]);
        $this->assertInstanceOf(FullTextSpecification::class, $children[1]);
        $this->assertInstanceOf(AuthorSpecification::class, $children[2]);
    }

    public function testReturnsEmptyFullTextForEmptyTerm(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('');

        $this->assertInstanceOf(FullTextSpecification::class, $spec);
        $this->assertSame('', $spec->getQuery());
    }

    public function testReturnsEmptyFullTextForWhitespaceTerm(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromSearchTerm('   ');

        $this->assertInstanceOf(FullTextSpecification::class, $spec);
        $this->assertSame('', $spec->getQuery());
    }

    public function testColumnFiltersReturnsEmptyFullTextWhenAllEmpty(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromColumnFilters(null, null, null, null, null);

        $this->assertInstanceOf(FullTextSpecification::class, $spec);
        $this->assertSame('', $spec->getQuery());
    }

    public function testColumnFiltersReturnsEmptyFullTextWhenAllEmptyStrings(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromColumnFilters('', null, '', '', '');

        $this->assertInstanceOf(FullTextSpecification::class, $spec);
        $this->assertSame('', $spec->getQuery());
    }

    public function testColumnFiltersSingleTitleReturnsFullText(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromColumnFilters('clean', null, null, null, null);

        $this->assertInstanceOf(FullTextSpecification::class, $spec);
        $this->assertSame('clean', $spec->getQuery());
    }

    public function testColumnFiltersSingleYearReturnsYear(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromColumnFilters(null, 2024, null, null, null);

        $this->assertInstanceOf(YearSpecification::class, $spec);
        $this->assertSame(2024, $spec->getYear());
    }

    public function testColumnFiltersSingleIsbnReturnsIsbnPrefix(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromColumnFilters(null, null, '978', null, null);

        $this->assertInstanceOf(IsbnPrefixSpecification::class, $spec);
        $this->assertSame('978', $spec->getPrefix());
    }

    public function testColumnFiltersSingleStatusReturnsStatus(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromColumnFilters(null, null, null, 'published', null);

        $this->assertInstanceOf(StatusSpecification::class, $spec);
        $this->assertSame(BookStatus::Published, $spec->getStatus());
    }

    public function testColumnFiltersSingleAuthorReturnsAuthor(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromColumnFilters(null, null, null, null, 'Martin');

        $this->assertInstanceOf(AuthorSpecification::class, $spec);
        $this->assertSame('Martin', $spec->getAuthorName());
    }

    public function testColumnFiltersInvalidStatusIsIgnored(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromColumnFilters(null, null, null, 'invalid_status', null);

        $this->assertInstanceOf(FullTextSpecification::class, $spec);
        $this->assertSame('', $spec->getQuery());
    }

    public function testColumnFiltersMultipleFieldsReturnsCompositeAnd(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromColumnFilters('clean', 2024, null, 'published', null);

        $this->assertInstanceOf(CompositeAndSpecification::class, $spec);
        $children = $spec->getSpecifications();
        $this->assertCount(3, $children);
        $this->assertInstanceOf(FullTextSpecification::class, $children[0]);
        $this->assertInstanceOf(YearSpecification::class, $children[1]);
        $this->assertInstanceOf(StatusSpecification::class, $children[2]);
    }

    public function testColumnFiltersAllFieldsReturnsCompositeAndWithFive(): void
    {
        $factory = new BookSearchSpecificationFactory();

        $spec = $factory->createFromColumnFilters('clean', 2024, '978', 'draft', 'Martin');

        $this->assertInstanceOf(CompositeAndSpecification::class, $spec);
        $children = $spec->getSpecifications();
        $this->assertCount(5, $children);
        $this->assertInstanceOf(FullTextSpecification::class, $children[0]);
        $this->assertInstanceOf(YearSpecification::class, $children[1]);
        $this->assertInstanceOf(IsbnPrefixSpecification::class, $children[2]);
        $this->assertInstanceOf(StatusSpecification::class, $children[3]);
        $this->assertInstanceOf(AuthorSpecification::class, $children[4]);
    }
}
