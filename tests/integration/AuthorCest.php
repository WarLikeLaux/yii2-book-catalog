<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\User;

final class AuthorCest
{
    public function _before(IntegrationTester $I): void
    {
        DbCleaner::clear(['authors']);
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testCanViewAuthorsIndex(IntegrationTester $I): void
    {
        $I->amOnRoute('author/index');
        $I->seeResponseCodeIs(200);
        $I->see('Авторы', 'h1');
    }

    public function testCanViewAuthorCreatePage(IntegrationTester $I): void
    {
        $I->amOnRoute('author/create');
        $I->seeResponseCodeIs(200);
        $I->see('Создать автора', 'h1');
        $I->seeElement('form');
        $I->seeElement('input[name="AuthorForm[fio]"]');
    }

    public function testCanCreateAuthor(IntegrationTester $I): void
    {
        $I->amOnRoute('author/create');
        $I->submitForm('.author-create form', [
            'AuthorForm[fio]' => 'New Unique Author',
        ]);
        $I->seeResponseCodeIs(200);
        $I->see('New Unique Author');
        $I->seeRecord(Author::class, ['fio' => 'New Unique Author']);
    }

    public function testCreateDuplicateAuthor(IntegrationTester $I): void
    {
        $I->haveRecord(Author::class, ['fio' => 'Existing Author']);

        $I->amOnRoute('author/create');
        $I->submitForm('.author-create form', [
            'AuthorForm[fio]' => 'Existing Author',
        ]);

        $I->see('Автор с таким ФИО уже существует');
        $I->see('Создать автора');
    }

    public function testCanViewAuthorDetails(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);

        $I->amOnRoute('author/view', ['id' => $authorId]);
        $I->seeResponseCodeIs(200);
        $I->see('Test Author');
    }

    public function testCanViewAuthorUpdatePage(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);

        $I->amOnRoute('author/update', ['id' => $authorId]);
        $I->seeResponseCodeIs(200);
        $I->see('Обновить автора', 'h1');
        $I->seeElement('form');
    }

    public function testCanUpdateAuthor(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Author To Update']);

        $I->amOnRoute('author/update', ['id' => $authorId]);
        $I->submitForm('.author-update form', [
            'AuthorForm[fio]' => 'Updated Author Name',
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Updated Author Name');
        $I->seeRecord(Author::class, ['id' => $authorId, 'fio' => 'Updated Author Name']);
    }

    public function testCanSearchAuthors(IntegrationTester $I): void
    {
        $I->haveRecord(Author::class, ['fio' => 'Test Author 1']);
        $I->haveRecord(Author::class, ['fio' => 'Test Author 2']);

        $I->sendAjaxGetRequest('/index-test.php?r=author/search', ['q' => 'Test']);
        $I->seeResponseCodeIs(200);
        $response = $I->grabPageSource();
        $I->assertStringContainsString('Test Author', $response);
    }
}
