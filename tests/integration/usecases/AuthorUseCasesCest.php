<?php

declare(strict_types=1);

use app\application\authors\commands\CreateAuthorCommand;
use app\application\authors\commands\DeleteAuthorCommand;
use app\application\authors\commands\UpdateAuthorCommand;
use app\application\authors\usecases\CreateAuthorUseCase;
use app\application\authors\usecases\DeleteAuthorUseCase;
use app\application\authors\usecases\UpdateAuthorUseCase;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\User;

final class AuthorUseCasesCest
{
    public function _before(IntegrationTester $I): void
    {
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
}
