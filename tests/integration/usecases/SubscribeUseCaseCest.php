<?php

declare(strict_types=1);

use app\application\subscriptions\commands\SubscribeCommand;
use app\application\subscriptions\usecases\SubscribeUseCase;
use app\domain\exceptions\DomainException;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Subscription;

final class SubscribeUseCaseCest
{
    public function _before(\IntegrationTester $I): void
    {
        \Yii::$app->db->createCommand()->delete('subscriptions')->execute();
    }

    public function testCreatesSubscription(\IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);

        $command = new SubscribeCommand(
            phone: '+79001234567',
            authorId: $authorId
        );

        $useCase = \Yii::$container->get(SubscribeUseCase::class);
        $useCase->execute($command);

        $I->seeRecord(Subscription::class, [
            'phone' => '+79001234567',
            'author_id' => $authorId,
        ]);
    }

    public function testPreventsDuplicateSubscription(\IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);
        $I->haveRecord(Subscription::class, [
            'phone' => '+79001234567',
            'author_id' => $authorId,
        ]);

        $command = new SubscribeCommand(
            phone: '+79001234567',
            authorId: $authorId
        );

        $useCase = \Yii::$container->get(SubscribeUseCase::class);

        $I->expectThrowable(DomainException::class, function () use ($useCase, $command): void {
            $useCase->execute($command);
        });

        $subscriptions = Subscription::find()
            ->where(['phone' => '+79001234567', 'author_id' => $authorId])
            ->count();

        $I->assertEquals(1, $subscriptions, 'Should not create duplicate subscription');
    }

    public function testAllowsSubscriptionToDifferentAuthors(\IntegrationTester $I): void
    {
        $author1Id = $I->haveRecord(Author::class, ['fio' => 'Author 1']);
        $author2Id = $I->haveRecord(Author::class, ['fio' => 'Author 2']);

        $I->haveRecord(Subscription::class, [
            'phone' => '+79001234567',
            'author_id' => $author1Id,
        ]);

        $command = new SubscribeCommand(
            phone: '+79001234567',
            authorId: $author2Id
        );

        $useCase = \Yii::$container->get(SubscribeUseCase::class);
        $useCase->execute($command);

        $I->seeRecord(Subscription::class, [
            'phone' => '+79001234567',
            'author_id' => $author2Id,
        ]);
    }
}

