<?php

declare(strict_types=1);

namespace tests\unit\domain\entities;

use app\domain\entities\Book;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use Codeception\Test\Unit;

final class BookTest extends Unit
{
    public function testCreateAndGetters(): void
    {
        $year = new BookYear(2023);
        $isbn = new Isbn('978-3-16-148410-0');
        
        $book = Book::create('Title', $year, $isbn, 'Desc', 'http://url.com');
        
        $this->assertNull($book->getId());
        $this->assertSame('Title', $book->getTitle());
        $this->assertSame($year, $book->getYear());
        $this->assertSame($isbn, $book->getIsbn());
        $this->assertSame('Desc', $book->getDescription());
        $this->assertSame('http://url.com', $book->getCoverUrl());
        $this->assertSame([], $book->getAuthorIds());
    }

    public function testUpdate(): void
    {
        $book = new Book(
            1, 
            'Old Title', 
            new BookYear(2020), 
            new Isbn('978-3-16-148410-0'), 
            null, 
            null
        );

        $newYear = new BookYear(2024);
        $newIsbn = new Isbn('978-3-16-148410-0'); // Same valid ISBN
        
        $book->update('New Title', $newYear, $newIsbn, 'New Desc', 'http://new.com');
        
        $this->assertSame('New Title', $book->getTitle());
        $this->assertSame($newYear, $book->getYear());
        $this->assertSame('New Desc', $book->getDescription());
        $this->assertSame('http://new.com', $book->getCoverUrl());

        // Test update without cover update (null)
        $book->update('Title 2', $newYear, $newIsbn, 'Desc 2', null);
        $this->assertSame('http://new.com', $book->getCoverUrl(), 'Cover URL should not change if null passed');
    }

    public function testAuthorSync(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
        
        $book->syncAuthors([1, '2', 3]);
        
        $this->assertSame([1, 2, 3], $book->getAuthorIds());
    }

    public function testSetId(): void
    {
        $book = Book::create('Title', new BookYear(2023), new Isbn('978-3-16-148410-0'), null, null);
        
        $book->setId(100);
        $this->assertSame(100, $book->getId());
        
        // Setting same ID is fine
        $book->setId(100);
        
        // Changing ID throws exception
        $this->expectException(\RuntimeException::class);
        $book->setId(200);
    }
}
