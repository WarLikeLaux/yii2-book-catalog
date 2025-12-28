<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\User;

final class AuthorDeleteCest
{
    public function _before(\IntegrationTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testCanDeleteAuthor(\IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Author To Delete']);

        $I->sendAjaxPostRequest('/index-test.php?r=author/delete&id=' . $authorId);

        $I->dontSeeRecord(Author::class, ['id' => $authorId]);
    }
}
