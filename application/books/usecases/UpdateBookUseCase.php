<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\UpdateBookCommand;
use app\domain\exceptions\DomainException;
use app\interfaces\FileStorageInterface;
use app\models\Book;
use yii\db\ActiveRecord;
use yii\db\Connection;
use Yii;

final class UpdateBookUseCase
{
    public function __construct(
        private readonly Connection $db,
        private readonly FileStorageInterface $fileStorage,
    ) {
    }

    public function execute(UpdateBookCommand $command): Book
    {
        $book = Book::findOne($command->id);
        if (!$book) {
            throw new DomainException(Yii::t('app', 'Book not found'));
        }

        $transaction = $this->db->beginTransaction();

        try {
            $coverUrl = $command->cover ? $this->fileStorage->save($command->cover) : null;
            $book->edit(
                title: $command->title,
                year: $command->year,
                isbn: $command->isbn,
                description: $command->description,
                coverUrl: $coverUrl
            );

            if (!$book->save()) {
                throw new DomainException($this->getFirstErrorMessage($book, Yii::t('app', 'Failed to update book')));
            }

            $this->syncBookAuthors($book->id, $command->authorIds);

            $transaction->commit();

            return $book;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    private function syncBookAuthors(int $bookId, array $newAuthorIds): void
    {
        $book = Book::findOne($bookId);
        if (!$book) {
            return;
        }

        $existingAuthorIds = $book->getAuthors()->select('id')->column();

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
