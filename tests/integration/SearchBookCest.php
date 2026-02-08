<?php

declare(strict_types=1);

namespace tests\integration;

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use IntegrationTester;

class SearchBookCest
{
    public function _before(IntegrationTester $_I): void
    {
    }

    public function _after(IntegrationTester $_I): void
    {
        Book::deleteAll();
        Author::deleteAll();
    }

    public function testSearchByAuthor(IntegrationTester $I): void
    {
        $I->wantTo('search books by author via global search');

        $suffix = uniqid('author_', true);
        $this->createBookWithAuthor($I, '978-3-16-148410-0', 'PHP Expert ' . $suffix, 'Unique PHP Guide');
        $this->createBookWithAuthor($I, '978-1-23-456789-7', 'Java Guru ' . $suffix, 'Another Java Book');

        $I->amOnPage('/');
        $I->see('Unique PHP Guide');
        $I->see('Another Java Book');

        $I->submitForm('#book-search-form', [
            'globalSearch' => 'PHP',
        ]);

        $I->see('Unique PHP Guide');
        $I->dontSee('Another Java Book');
    }

    public function testSearchByIsbn(IntegrationTester $I): void
    {
        $I->wantTo('search books by ISBN prefix via global search');

        $I->haveRecord(Book::class, [
            'title' => 'ISBN Search Book',
            'isbn' => '0-306-40615-2',
            'year' => 2020,
            'description' => 'ISBN desc 1',
            'status' => 'published',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $I->haveRecord(Book::class, [
            'title' => 'Other Book',
            'isbn' => '978-0-545-01022-1',
            'year' => 2021,
            'description' => 'ISBN desc 2',
            'status' => 'published',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $I->amOnPage('/');
        $I->submitForm('#book-search-form', [
            'globalSearch' => '0-306',
        ]);

        $I->see('ISBN Search Book');
        $I->dontSee('Other Book');
    }

    private function createBookWithAuthor(
        IntegrationTester $I,
        string $isbn,
        string $authorName,
        string $title,
    ): void {
        $I->haveRecord(Author::class, ['fio' => $authorName]);
        $author = $I->grabRecord(Author::class, ['fio' => $authorName]);

        $I->haveRecord(Book::class, [
            'title' => $title,
            'isbn' => $isbn,
            'year' => 2023,
            'description' => 'Test desc',
            'status' => 'published',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $book = $I->grabRecord(Book::class, ['isbn' => $isbn]);
        $book->link('authors', $author);

        $transaction = \Yii::$app->db->getTransaction();

        if ($transaction === null || !$transaction->getIsActive()) {
            return;
        }

        $transaction->commit();
    }
}
