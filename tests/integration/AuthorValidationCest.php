<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\User;

final class AuthorValidationCest
{
    public function _before(IntegrationTester $I): void
    {
        DbCleaner::clear(['authors']);
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testCreateAuthorWithDuplicateFio(IntegrationTester $I): void
    {
        $I->haveRecord(Author::class, ['fio' => 'Existing Author Name']);

        $I->amOnRoute('author/create');
        $I->sendPost('/index-test.php?r=author/create', [
            'AuthorForm' => [
                'fio' => 'Existing Author Name',
            ],
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Автор с таким ФИО уже существует');
    }

    public function testUpdateAuthorWithSameFioAllowed(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Author To Update']);

        $I->sendPost('/index-test.php?r=author/update&id=' . $authorId, [
            'AuthorForm' => [
                'fio' => 'Author To Update',
            ],
        ]);

        $I->seeInCurrentUrl('view');
        $I->seeRecord(Author::class, ['id' => $authorId, 'fio' => 'Author To Update']);
    }

    public function testUpdateAuthorWithAnotherExistingFio(IntegrationTester $I): void
    {
        $I->haveRecord(Author::class, ['fio' => 'First Author']);
        $secondAuthorId = $I->haveRecord(Author::class, ['fio' => 'Second Author']);

        $transaction = Yii::$app->db->getTransaction();

        if ($transaction !== null && $transaction->getIsActive()) {
            $transaction->commit();
        }

        $I->sendPost('/index-test.php?r=author/update&id=' . $secondAuthorId, [
            'AuthorForm' => [
                'fio' => 'First Author',
            ],
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Автор с таким ФИО уже существует');
    }

    public function testSearchAuthorsReturnsEmptyForNoMatch(IntegrationTester $I): void
    {
        $I->sendAjaxGetRequest('/index-test.php?r=author/search', [
            'q' => 'NonExistentAuthorXYZ123',
        ]);

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true);
        $I->assertEmpty($response['results']);
        $I->assertFalse($response['pagination']['more']);
    }

    public function testSearchAuthorsReturnsMatches(IntegrationTester $I): void
    {
        $I->haveRecord(Author::class, ['fio' => 'Searchable Author One']);
        $I->haveRecord(Author::class, ['fio' => 'Searchable Author Two']);

        $I->sendAjaxGetRequest('/index-test.php?r=author/search', [
            'q' => 'Searchable',
        ]);

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true);
        $I->assertNotEmpty($response['results']);
        $I->assertArrayHasKey('id', $response['results'][0]);
        $I->assertArrayHasKey('text', $response['results'][0]);
    }

    public function testSearchAuthorsWithPagination(IntegrationTester $I): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $I->haveRecord(Author::class, ['fio' => 'Paginated Author ' . $i]);
        }

        $I->sendAjaxGetRequest('/index-test.php?r=author/search', [
            'q' => 'Paginated',
            'page' => 1,
        ]);

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true);
        $I->assertNotEmpty($response['results']);
    }
}
