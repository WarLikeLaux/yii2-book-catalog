<?php

declare(strict_types=1);

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\domain\exceptions\BusinessRuleException;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\Subscription;
use app\infrastructure\persistence\User;
use yii\db\IntegrityException;

final class AuthorUseCasesCest
{
    public function _before(IntegrationTester $I): void
    {
        DbCleaner::clear(['subscriptions', 'book_authors', 'books', 'authors']);
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testCreateAuthorUseCase(IntegrationTester $I): void
    {
        $command = new CreateAuthorCommand(fio: 'New UseCase Author');

        $useCase = Yii::$container->get(CreateAuthorUseCase::class);
        $authorId = $useCase->execute($command);

        $I->seeRecord(Author::class, ['id' => $authorId, 'fio' => 'New UseCase Author']);
    }

    public function testUpdateAuthorUseCase(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Original Author']);

        $command = new UpdateAuthorCommand(id: $authorId, fio: 'Updated Author');

        $useCase = Yii::$container->get(UpdateAuthorUseCase::class);
        $useCase->execute($command);

        $I->seeRecord(Author::class, ['id' => $authorId, 'fio' => 'Updated Author']);
    }

    public function testDeleteAuthorUseCase(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Author To Delete']);

        $command = new DeleteAuthorCommand(id: $authorId);

        $useCase = Yii::$container->get(DeleteAuthorUseCase::class);
        $useCase->execute($command);

        $I->dontSeeRecord(Author::class, ['id' => $authorId]);
    }

    public function testDeleteAuthorWithPublishedBookThrows(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Published Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Published Book',
            'year' => 2024,
            'isbn' => '9783161484100',
            'description' => str_repeat('A', 50),
            'status' => 'published',
            'cover_image' => 'covers/test.jpg',
        ]);
        Yii::$app->db->createCommand()
            ->insert('book_authors', ['book_id' => $bookId, 'author_id' => $authorId])
            ->execute();

        $command = new DeleteAuthorCommand(id: $authorId);
        $useCase = Yii::$container->get(DeleteAuthorUseCase::class);

        $I->expectThrowable(BusinessRuleException::class, static function () use ($useCase, $command): void {
            $useCase->execute($command);
        });

        $I->seeRecord(Author::class, ['id' => $authorId]);
    }

    public function testDeleteAuthorWithSubscriptionsThrows(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Subscribed Author']);
        $I->haveRecord(Subscription::class, [
            'phone' => '+79001234567',
            'author_id' => $authorId,
            'created_at' => time(),
        ]);

        $command = new DeleteAuthorCommand(id: $authorId);
        $useCase = Yii::$container->get(DeleteAuthorUseCase::class);

        $I->expectThrowable(BusinessRuleException::class, static function () use ($useCase, $command): void {
            $useCase->execute($command);
        });

        $I->seeRecord(Author::class, ['id' => $authorId]);
    }

    public function testDeleteAuthorWithDraftBookSucceeds(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Draft Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Draft Book',
            'year' => 2024,
            'isbn' => '9783161484100',
            'description' => null,
            'status' => 'draft',
        ]);
        Yii::$app->db->createCommand()
            ->insert('book_authors', ['book_id' => $bookId, 'author_id' => $authorId])
            ->execute();

        $command = new DeleteAuthorCommand(id: $authorId);
        $useCase = Yii::$container->get(DeleteAuthorUseCase::class);
        $useCase->execute($command);

        $I->dontSeeRecord(Author::class, ['id' => $authorId]);
        $I->seeRecord(Book::class, ['id' => $bookId]);
    }

    public function testRestrictFkPreventsDirectDeleteWithBookLinks(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'FK Restrict Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'FK Test Book',
            'year' => 2024,
            'isbn' => '9783161484100',
            'description' => null,
            'status' => 'draft',
        ]);
        Yii::$app->db->createCommand()
            ->insert('book_authors', ['book_id' => $bookId, 'author_id' => $authorId])
            ->execute();

        $I->expectThrowable(IntegrityException::class, static function () use ($authorId): void {
            Yii::$app->db->createCommand()
                ->delete('authors', ['id' => $authorId])
                ->execute();
        });

        $I->seeRecord(Author::class, ['id' => $authorId]);
    }

    public function testRestrictFkPreventsDirectDeleteWithSubscriptions(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'FK Sub Author']);
        $I->haveRecord(Subscription::class, [
            'phone' => '+79001234567',
            'author_id' => $authorId,
            'created_at' => time(),
        ]);

        $I->expectThrowable(IntegrityException::class, static function () use ($authorId): void {
            Yii::$app->db->createCommand()
                ->delete('authors', ['id' => $authorId])
                ->execute();
        });

        $I->seeRecord(Author::class, ['id' => $authorId]);
    }
}
