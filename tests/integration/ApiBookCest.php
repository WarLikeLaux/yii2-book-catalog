<?php

declare(strict_types=1);

namespace tests\integration;

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use IntegrationTester;

final class ApiBookCest
{
    public function _before(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, [
            'fio' => 'Test API Author',
        ]);

        $I->haveRecord(Book::class, [
            'title' => 'Test API Book',
            'year' => 2025,
            'isbn' => '9781234567897',
        ]);
    }

    public function testGetBooksList(IntegrationTester $I): void
    {
        $I->sendGet('/index-test.php?r=api/v1/book/index');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            [
                'title' => 'Test API Book',
            ],
        ]);
    }

    public function testPagination(IntegrationTester $I): void
    {
        $I->sendGet('/index-test.php?r=api/v1/book/index', ['pageSize' => 1]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $response = json_decode($I->grabResponse(), true);
        $I->assertCount(1, $response);
    }
}
