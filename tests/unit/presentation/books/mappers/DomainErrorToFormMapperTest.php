<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\mappers;

use app\presentation\books\mappers\DomainErrorToFormMapper;
use Codeception\Test\Unit;

final class DomainErrorToFormMapperTest extends Unit
{
    private DomainErrorToFormMapper $mapper;

    protected function _before(): void
    {
        $this->mapper = new DomainErrorToFormMapper();
    }

    public function testMapsIsbnErrorToIsbnField(): void
    {
        $this->assertSame('isbn', $this->mapper->getFieldForError('isbn.error.invalid_format'));
    }

    public function testMapsYearTooOldErrorToYearField(): void
    {
        $this->assertSame('year', $this->mapper->getFieldForError('year.error.too_old'));
    }

    public function testMapsYearFutureErrorToYearField(): void
    {
        $this->assertSame('year', $this->mapper->getFieldForError('year.error.future'));
    }

    public function testMapsTitleEmptyErrorToTitleField(): void
    {
        $this->assertSame('title', $this->mapper->getFieldForError('book.error.title_empty'));
    }

    public function testMapsTitleTooLongErrorToTitleField(): void
    {
        $this->assertSame('title', $this->mapper->getFieldForError('book.error.title_too_long'));
    }

    public function testMapsIsbnChangePublishedErrorToIsbnField(): void
    {
        $this->assertSame('isbn', $this->mapper->getFieldForError('book.error.isbn_change_published'));
    }

    public function testMapsInvalidAuthorIdErrorToAuthorIdsField(): void
    {
        $this->assertSame('authorIds', $this->mapper->getFieldForError('book.error.invalid_author_id'));
    }

    public function testMapsPublishWithoutAuthorsErrorToAuthorIdsField(): void
    {
        $this->assertSame('authorIds', $this->mapper->getFieldForError('book.error.publish_without_authors'));
    }

    public function testReturnsNullForUnknownError(): void
    {
        $this->assertNull($this->mapper->getFieldForError('unknown.error'));
    }

    public function testReturnsNullForEmptyString(): void
    {
        $this->assertNull($this->mapper->getFieldForError(''));
    }
}
