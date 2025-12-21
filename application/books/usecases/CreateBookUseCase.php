<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\CreateBookCommand;
use app\domain\exceptions\DomainException;
use app\interfaces\FileStorageInterface;
use app\jobs\NotifySubscribersJob;
use app\models\Book;
use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\queue\db\Queue;
use Yii;

final class CreateBookUseCase
{
    public function __construct(
        private readonly Connection $db,
        private readonly Queue $queue,
        private readonly FileStorageInterface $fileStorage,
    ) {
    }

    public function execute(CreateBookCommand $command): Book
    {
        $transaction = $this->db->beginTransaction();

        try {
            $coverUrl = $command->cover ? $this->fileStorage->save($command->cover) : null;
            $book = Book::create(
                title: $command->title,
                year: $command->year,
                isbn: $command->isbn,
                description: $command->description,
                coverUrl: $coverUrl
            );

            if (!$book->save()) {
                throw new DomainException($this->getFirstErrorMessage($book, Yii::t('app', 'Failed to save book')));
            }

            $rows = array_map(
                fn($authorId) => [$book->id, $authorId],
                $command->authorIds
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

    private function getFirstErrorMessage(ActiveRecord $model, string $fallback): string
    {
        $errors = $model->getFirstErrors();
        if (!$errors) {
            return $fallback;
        }
        return array_shift($errors);
    }
}
