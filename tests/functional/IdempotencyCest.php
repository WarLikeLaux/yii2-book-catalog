<?php

declare(strict_types=1);

namespace tests\functional;

use app\infrastructure\persistence\Author;
use FunctionalTester;

final class IdempotencyCest
{
    private int $authorId;

    public function _before(FunctionalTester $I): void
    {
        $this->authorId = (int)$I->haveRecord(Author::class, [
            'fio' => 'Idempotency Author',
        ]);
    }

    public function testApiIdempotency(FunctionalTester $I): void
    {
        $key = 'test-idempotency-api-' . uniqid();
        $I->haveHttpHeader('Idempotency-Key', $key);

        $I->sendPost('/index-test.php?r=api/book/index', ['title' => 'First Title']);
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('X-Idempotency-Cache', 'MISS');

        $I->haveHttpHeader('Idempotency-Key', $key);
        $I->sendPost('/index-test.php?r=api/book/index', ['title' => 'Second Title']);
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('X-Idempotency-Cache', 'HIT');
    }

    public function testWebFormIdempotency(FunctionalTester $I): void
    {
        $I->amOnPage('/index-test.php?r=site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin');
        $I->click('login-button');

        $key = 'test-idempotency-web-' . uniqid();
        $validData = [
            'BookForm' => [
                'title' => 'Idempotent Web Book',
                'isbn' => '9783161484100',
                'year' => '2025',
                'authorIds' => [$this->authorId],
            ],
        ];

        $I->haveHttpHeader('Idempotency-Key', $key);
        $I->stopFollowingRedirects();
        $I->sendPost('/index-test.php?r=book/create', $validData);
        $I->seeResponseCodeIs(302);
        $I->seeHttpHeader('X-Idempotency-Cache', 'MISS');
        $location = $I->grabHttpHeader('Location');

        $I->haveHttpHeader('Idempotency-Key', $key);
        $I->sendPost('/index-test.php?r=book/create', $validData);
        $I->seeResponseCodeIs(302);
        $I->seeHttpHeader('X-Idempotency-Cache', 'HIT');
        $I->seeHttpHeader('Location', $location);
    }
}
