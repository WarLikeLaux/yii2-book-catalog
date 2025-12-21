<?php

declare(strict_types=1);

use app\models\Author;
use app\models\User;

final class AuthorCest
{
    public function _before(\FunctionalTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testCanViewAuthorsIndex(\FunctionalTester $I): void
    {
        $I->amOnRoute('author/index');
        $I->seeResponseCodeIs(200);
        $I->see('Авторы', 'h1');
    }

    public function testCanViewAuthorCreatePage(\FunctionalTester $I): void
    {
        $I->amOnRoute('author/create');
        $I->seeResponseCodeIs(200);
        $I->see('Создать автора', 'h1');
        $I->seeElement('form');
        $I->seeElement('input[name="AuthorForm[fio]"]');
    }

    public function testCanViewAuthorDetails(\FunctionalTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);

        $I->amOnRoute('author/view', ['id' => $authorId]);
        $I->seeResponseCodeIs(200);
        $I->see('Test Author');
    }

    public function testCanViewAuthorUpdatePage(\FunctionalTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);

        $I->amOnRoute('author/update', ['id' => $authorId]);
        $I->seeResponseCodeIs(200);
        $I->see('Обновить автора', 'h1');
        $I->seeElement('form');
    }

    public function testCanSearchAuthors(\FunctionalTester $I): void
    {
        $I->haveRecord(Author::class, ['fio' => 'Test Author 1']);
        $I->haveRecord(Author::class, ['fio' => 'Test Author 2']);

        $I->sendAjaxGetRequest('/index-test.php?r=author/search', ['q' => 'Test']);
        $I->seeResponseCodeIs(200);
        $response = $I->grabPageSource();
        $I->assertStringContainsString('Test Author', $response);
    }
}

