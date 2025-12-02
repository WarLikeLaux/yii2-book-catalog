<?php

declare(strict_types=1);

namespace app\services;

use app\interfaces\FileStorageInterface;
use app\models\Book;
use app\models\forms\BookForm;
use app\jobs\NotifySubscribersJob;
use DomainException;
use Yii;
use yii\db\Connection;
use yii\db\ActiveRecord;
use yii\queue\db\Queue;

final class BookService
{
    public function __construct(
        private readonly Connection $db,
        private readonly Queue $queue,
        private readonly FileStorageInterface $fileStorage
    ) {}

    public function create(BookForm $form): Book
    {
        $transaction = $this->db->beginTransaction();

        try {
            $book = new Book();
            $book->title = $form->title;
            $book->year = $form->year;
            $book->description = $form->description;
            $book->isbn = $form->isbn;

            if ($form->cover) {
                $book->cover_url = $this->fileStorage->save($form->cover);
            }

            if (!$book->save()) {
                throw new DomainException($this->getFirstErrorMessage($book, 'Failed to save book'));
            }

            foreach ($form->authorIds as $authorId) {
                $this->db->createCommand()->insert('book_authors', [
                    'book_id' => $book->id,
                    'author_id' => $authorId,
                ])->execute();
            }

            $transaction->commit();

            $this->queue->push(new NotifySubscribersJob([
                'bookId' => $book->id,
                'title' => $book->title,
            ]));

            return $book;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function update(int $id, BookForm $form): Book
    {
        $book = Book::findOne($id);
        if (!$book) {
            throw new DomainException('Book not found');
        }

        $transaction = $this->db->beginTransaction();

        try {
            $book->title = $form->title;
            $book->year = $form->year;
            $book->description = $form->description;
            $book->isbn = $form->isbn;

            if ($form->cover) {
                $book->cover_url = $this->fileStorage->save($form->cover);
            }

            if (!$book->save()) {
                throw new DomainException($this->getFirstErrorMessage($book, 'Failed to update book'));
            }

            $this->db->createCommand()
                ->delete('book_authors', ['book_id' => $book->id])
                ->execute();

            foreach ($form->authorIds as $authorId) {
                $this->db->createCommand()->insert('book_authors', [
                    'book_id' => $book->id,
                    'author_id' => $authorId,
                ])->execute();
            }

            $transaction->commit();

            return $book;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $book = Book::findOne($id);
        if (!$book) {
            throw new DomainException('Book not found');
        }

        if (!$book->delete()) {
            throw new DomainException('Failed to delete book');
        }
    }

    private function getFirstErrorMessage(ActiveRecord $model, string $fallback): string
    {
        $errors = $model->getFirstErrors();
        if (!$errors) {
            return $fallback;
        }
        return array_shift($errors);
    }
}
