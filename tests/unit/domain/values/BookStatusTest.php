<?php

declare(strict_types=1);

namespace tests\unit\domain\values;

use app\domain\values\BookStatus;
use Codeception\Test\Unit;

final class BookStatusTest extends Unit
{
    public function testDraftToPublishedAllowed(): void
    {
        $this->assertTrue(BookStatus::Draft->canTransitionTo(BookStatus::Published));
    }

    public function testPublishedToDraftAllowed(): void
    {
        $this->assertTrue(BookStatus::Published->canTransitionTo(BookStatus::Draft));
    }

    public function testPublishedToArchivedAllowed(): void
    {
        $this->assertTrue(BookStatus::Published->canTransitionTo(BookStatus::Archived));
    }

    public function testArchivedToDraftAllowed(): void
    {
        $this->assertTrue(BookStatus::Archived->canTransitionTo(BookStatus::Draft));
    }

    public function testDraftToArchivedForbidden(): void
    {
        $this->assertFalse(BookStatus::Draft->canTransitionTo(BookStatus::Archived));
    }

    public function testArchivedToPublishedForbidden(): void
    {
        $this->assertFalse(BookStatus::Archived->canTransitionTo(BookStatus::Published));
    }

    public function testSameStatusTransitionForbidden(): void
    {
        $this->assertFalse(BookStatus::Draft->canTransitionTo(BookStatus::Draft));
        $this->assertFalse(BookStatus::Published->canTransitionTo(BookStatus::Published));
        $this->assertFalse(BookStatus::Archived->canTransitionTo(BookStatus::Archived));
    }

    public function testBackedValues(): void
    {
        $this->assertSame('draft', BookStatus::Draft->value);
        $this->assertSame('published', BookStatus::Published->value);
        $this->assertSame('archived', BookStatus::Archived->value);
    }
}
