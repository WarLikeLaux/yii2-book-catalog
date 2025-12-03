<?php

declare(strict_types=1);

namespace app\services;

use app\interfaces\FileStorageInterface;
use app\jobs\NotifySubscribersJob;
use app\models\Book;
use app\models\forms\BookForm;
use DomainException;
use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\queue\db\Queue;

final class BookService
{
    public function __construct(
        private readonly Connection $db,
        private readonly Queue $queue,
        private readonly FileStorageInterface $fileStorage
    ) {
    }

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
                throw new DomainException($this->getFirstErrorMessage($book, 'Не удалось сохранить книгу'));
            }

            $rows = array_map(
                fn($authorId) => [$book->id, $authorId],
                $form->authorIds
            );
            $this->db->createCommand()->batchInsert(
                'book_authors',
                ['book_id', 'author_id'],
                $rows
            )->execute();

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
            throw new DomainException('Книга не найдена');
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
                throw new DomainException($this->getFirstErrorMessage($book, 'Не удалось обновить книгу'));
            }

            $this->syncBookAuthors($book->id, $form->authorIds);

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
            throw new DomainException('Книга не найдена');
        }

        if (!$book->delete()) {
            throw new DomainException('Не удалось удалить книгу');
        }
    }

    /**
     * @param int[] $newAuthorIds
     */
    private function syncBookAuthors(int $bookId, array $newAuthorIds): void
    {
        $existingAuthorIds = Book::findOne($bookId)
            ->getAuthors()
            ->select('id')
            ->column();

        $existingAuthorIds = array_map('intval', $existingAuthorIds);
        $newAuthorIds = array_map('intval', $newAuthorIds);

        $toDelete = array_diff($existingAuthorIds, $newAuthorIds);
        $toAdd = array_diff($newAuthorIds, $existingAuthorIds);

        if ($toDelete) {
            $this->db->createCommand()->delete('book_authors', [
                'and',
                ['book_id' => $bookId],
                ['in', 'author_id', $toDelete],
            ])->execute();
        }

        if ($toAdd) {
            $rows = array_map(
                fn($authorId) => [$bookId, $authorId],
                $toAdd
            );
            $this->db->createCommand()->batchInsert(
                'book_authors',
                ['book_id', 'author_id'],
                $rows
            )->execute();
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
