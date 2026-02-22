<?php

declare(strict_types=1);

namespace tests\unit\application\books\factories;

use app\application\books\factories\BookSearchSpecificationFactory;
use app\domain\specifications\AuthorSpecification;
use app\domain\specifications\CompositeOrSpecification;
use app\domain\specifications\FullTextSpecification;
use app\domain\specifications\IsbnPrefixSpecification;
use app\domain\specifications\YearSpecification;
use Codeception\Test\Unit;

final class BookSearchSpecificationFactoryTest extends Unit
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
}
