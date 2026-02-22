<?php

declare(strict_types=1);

namespace tests\unit\domain\specifications;

use app\domain\specifications\AuthorSpecification;
use app\domain\specifications\BookSpecificationVisitorInterface;
use app\domain\specifications\CompositeAndSpecification;
use app\domain\specifications\CompositeOrSpecification;
use app\domain\specifications\FullTextSpecification;
use app\domain\specifications\IsbnPrefixSpecification;
use app\domain\specifications\StatusSpecification;
use app\domain\specifications\YearSpecification;
use app\domain\values\BookStatus;
use Codeception\Test\Unit;

final class BookSpecificationsTest extends Unit
{
    public function testYearSpecificationGetterAndAccept(): void
    {
        $spec = new YearSpecification(2024);

        $this->assertSame(2024, $spec->getYear());

        $visitor = $this->createMock(BookSpecificationVisitorInterface::class);
        $visitor->expects($this->once())->method('visitYear')->with($spec);
        $spec->accept($visitor);
    }

    public function testIsbnPrefixSpecificationGetterAndAccept(): void
    {
        $spec = new IsbnPrefixSpecification('978-3');

        $this->assertSame('978-3', $spec->getPrefix());

        $visitor = $this->createMock(BookSpecificationVisitorInterface::class);
        $visitor->expects($this->once())->method('visitIsbnPrefix')->with($spec);
        $spec->accept($visitor);
    }

    public function testFullTextSpecificationGetterAndAccept(): void
    {
        $spec = new FullTextSpecification('clean code');

        $this->assertSame('clean code', $spec->getQuery());

        $visitor = $this->createMock(BookSpecificationVisitorInterface::class);
        $visitor->expects($this->once())->method('visitFullText')->with($spec);
        $spec->accept($visitor);
    }

    public function testAuthorSpecificationGetterAndAccept(): void
    {
        $spec = new AuthorSpecification('Martin Fowler');

        $this->assertSame('Martin Fowler', $spec->getAuthorName());

        $visitor = $this->createMock(BookSpecificationVisitorInterface::class);
        $visitor->expects($this->once())->method('visitAuthor')->with($spec);
        $spec->accept($visitor);
    }

    public function testCompositeOrSpecificationGetterAndAccept(): void
    {
        $yearSpec = new YearSpecification(2024);
        $authorSpec = new AuthorSpecification('Kent Beck');
        $composite = new CompositeOrSpecification([$yearSpec, $authorSpec]);

        $specs = $composite->getSpecifications();
        $this->assertCount(2, $specs);
        $this->assertSame($yearSpec, $specs[0]);
        $this->assertSame($authorSpec, $specs[1]);

        $visitor = $this->createMock(BookSpecificationVisitorInterface::class);
        $visitor->expects($this->once())->method('visitCompositeOr')->with($composite);
        $composite->accept($visitor);
    }

    public function testCompositeAndSpecificationGetterAndAccept(): void
    {
        $statusSpec = new StatusSpecification(BookStatus::Published);
        $fullTextSpec = new FullTextSpecification('clean');
        $composite = new CompositeAndSpecification([$statusSpec, $fullTextSpec]);

        $specs = $composite->getSpecifications();
        $this->assertCount(2, $specs);
        $this->assertSame($statusSpec, $specs[0]);
        $this->assertSame($fullTextSpec, $specs[1]);

        $visitor = $this->createMock(BookSpecificationVisitorInterface::class);
        $visitor->expects($this->once())->method('visitCompositeAnd')->with($composite);
        $composite->accept($visitor);
    }

    public function testStatusSpecificationGetterAndAccept(): void
    {
        $spec = new StatusSpecification(BookStatus::Published);

        $this->assertSame(BookStatus::Published, $spec->getStatus());

        $visitor = $this->createMock(BookSpecificationVisitorInterface::class);
        $visitor->expects($this->once())->method('visitStatus')->with($spec);
        $spec->accept($visitor);
    }
}
