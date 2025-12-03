<?php

declare(strict_types=1);

namespace tests\unit\services;

use app\interfaces\FileStorageInterface;
use app\jobs\NotifySubscribersJob;
use app\models\Book;
use app\models\forms\BookForm;
use app\services\BookService;
use Codeception\Test\Unit;
use DomainException;
use yii\db\Connection;
use yii\queue\db\Queue;
use yii\web\UploadedFile;

class BookServiceTest extends Unit
{
    private BookService $service;
    private Connection $db;
    private Queue $queue;
    private FileStorageInterface $fileStorage;

    protected function _before(): void
    {
        parent::_before();

        $this->db = \Yii::$app->db;
        $this->queue = $this->createMock(Queue::class);
        $this->fileStorage = $this->createMock(FileStorageInterface::class);

        $this->service = new BookService(
            $this->db,
            $this->queue,
            $this->fileStorage
        );

        $this->db->createCommand()->delete('book_authors')->execute();
        $this->db->createCommand()->delete('books')->execute();
        $this->db->createCommand()->delete('authors')->execute();
    }

    public function testCreateBookWithoutCover(): void
    {
        $authorId = $this->createAuthor('Test Author');

        $form = new BookForm();
        $form->title = 'Test Book';
        $form->year = 2024;
        $form->description = 'Test Description';
        $form->isbn = '978-3-16-148410-0';
        $form->authorIds = [$authorId];

        $this->queue->expects($this->once())
            ->method('push')
            ->with($this->callback(function ($job) {
                return $job instanceof NotifySubscribersJob
                    && $job->title === 'Test Book';
            }));

        $book = $this->service->create($form);

        verify($book)->notNull();
        verify($book->id)->notNull();
        verify($book->title)->equals('Test Book');
        verify($book->year)->equals(2024);
        verify($book->isbn)->equals('978-3-16-148410-0');
        verify($book->cover_url)->null();

        $authors = $this->db->createCommand(
            'SELECT author_id FROM book_authors WHERE book_id = :id'
        )->bindValue(':id', $book->id)->queryColumn();

        verify($authors)->equals([$authorId]);
    }

    public function testCreateBookWithCover(): void
    {
        $authorId = $this->createAuthor('Test Author');

        $form = new BookForm();
        $form->title = 'Book with Cover';
        $form->year = 2024;
        $form->description = 'Description';
        $form->isbn = '9783161484100';
        $form->authorIds = [$authorId];
        $form->cover = $this->createMock(UploadedFile::class);

        $this->fileStorage->expects($this->once())
            ->method('save')
            ->with($form->cover)
            ->willReturn('/uploads/test-cover.jpg');

        $this->queue->expects($this->once())
            ->method('push')
            ->with($this->isInstanceOf(NotifySubscribersJob::class));

        $book = $this->service->create($form);

        verify($book->cover_url)->equals('/uploads/test-cover.jpg');
    }

    public function testCreateBookWithMultipleAuthors(): void
    {
        $author1 = $this->createAuthor('Author One');
        $author2 = $this->createAuthor('Author Two');

        $form = new BookForm();
        $form->title = 'Multi-Author Book';
        $form->year = 2024;
        $form->description = 'Description';
        $form->isbn = '9783161484117';
        $form->authorIds = [$author1, $author2];

        $this->queue->expects($this->once())
            ->method('push');

        $book = $this->service->create($form);

        $authors = $this->db->createCommand(
            'SELECT author_id FROM book_authors WHERE book_id = :id ORDER BY author_id'
        )->bindValue(':id', $book->id)->queryColumn();

        verify(count($authors))->equals(2);
        verify(in_array($author1, $authors, true))->true();
        verify(in_array($author2, $authors, true))->true();
    }

    public function testUpdateBook(): void
    {
        $author1 = $this->createAuthor('Initial Author');
        $author2 = $this->createAuthor('Updated Author');

        $book = new Book();
        $book->title = 'Initial Title';
        $book->year = 2023;
        $book->description = 'Initial Description';
        $book->isbn = '9783161484124';
        if (!$book->save()) {
            throw new \Exception('Failed to save book: ' . json_encode($book->errors));
        }

        $this->db->createCommand()->insert('book_authors', [
            'book_id' => $book->id,
            'author_id' => $author1,
        ])->execute();

        $form = new BookForm();
        $form->title = 'Updated Title';
        $form->year = 2024;
        $form->description = 'Updated Description';
        $form->isbn = '9783161484131';
        $form->authorIds = [$author2];

        $updatedBook = $this->service->update($book->id, $form);

        verify($updatedBook->title)->equals('Updated Title');
        verify($updatedBook->year)->equals(2024);

        $authors = $this->db->createCommand(
            'SELECT author_id FROM book_authors WHERE book_id = :id'
        )->bindValue(':id', $book->id)->queryColumn();

        verify(count($authors))->equals(1);
        verify($authors[0])->equals($author2);
    }

    public function testUpdateNonExistentBook(): void
    {
        $form = new BookForm();
        $form->title = 'Test';
        $form->year = 2024;
        $form->isbn = '978-3-16-148410-5';
        $form->authorIds = [];

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Книга не найдена');

        $this->service->update(99999, $form);
    }

    public function testDeleteBook(): void
    {
        $book = new Book();
        $book->title = 'Book to Delete';
        $book->year = 2024;
        $book->description = 'Description';
        $book->isbn = '9783161484148';
        if (!$book->save()) {
            throw new \Exception('Failed to save book: ' . json_encode($book->errors));
        }

        $bookId = $book->id;
        verify($bookId)->notNull();

        $this->service->delete($bookId);

        $deletedBook = Book::findOne($bookId);
        verify($deletedBook)->null();
    }

    public function testDeleteNonExistentBook(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Книга не найдена');

        $this->service->delete(99999);
    }

    public function testCreateBookRollsBackOnError(): void
    {
        $form = new BookForm();
        $form->title = '';
        $form->year = 2024;
        $form->description = 'Test';
        $form->isbn = '9783161484155';
        $form->authorIds = [];

        $this->expectException(DomainException::class);

        $this->service->create($form);

        $books = Book::find()->where(['isbn' => '9783161484155'])->count();
        verify($books)->equals(0);
    }

    private function createAuthor(string $fio): int
    {
        $this->db->createCommand()->insert('authors', ['fio' => $fio])->execute();
        return (int)$this->db->getLastInsertID();
    }
}
