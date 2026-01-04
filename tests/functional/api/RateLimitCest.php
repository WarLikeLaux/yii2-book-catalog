<?php

declare(strict_types=1);

namespace tests\functional\api;

use Yii;

final class RateLimitCest
{
    private function clearRedis(): void
    {
        $redis = Yii::$app->get('redis');
        $keys = $redis->keys('ratelimit:*');

        if (empty($keys)) {
            return;
        }

        $redis->del($keys);
    }

    public function testApiBookIndexAllowsRequestsBelowLimit(FunctionalTester $I): void
    {
        $this->clearRedis();

        for ($i = 0; $i < 60; ++$i) {
            $I->sendGET('/api/books');
            $I->seeResponseCodeIs(200);
        }

        $I->seeHttpHeader('X-RateLimit-Limit', '60');
        $I->seeHttpHeader('X-RateLimit-Remaining', '0');
    }

    public function testApiBookIndexDeniesRequestsAfterLimit(FunctionalTester $I): void
    {
        $this->clearRedis();

        for ($i = 0; $i < 60; ++$i) {
            $I->sendGET('/api/books');
            $I->seeResponseCodeIs(200);
        }

        $I->sendGET('/api/books');
        $I->seeResponseCodeIs(429);
    }

    public function testApiBookIndexReturnsCorrectRateLimitHeaders(FunctionalTester $I): void
    {
        $this->clearRedis();

        $I->sendGET('/api/books');
        $I->seeResponseCodeIs(200);

        $I->seeHttpHeader('X-RateLimit-Limit', '60');
        $I->seeHttpHeader('X-RateLimit-Remaining', '59');

        $resetHeader = $I->grabHttpHeader('X-RateLimit-Reset');
        $I->assertNotNull($resetHeader);
        $I->assertIsNumeric($resetHeader);
        $I->assertGreaterThan(time(), (int)$resetHeader);
    }

    public function testApiBookIndexReturns429WithRetryAfter(FunctionalTester $I): void
    {
        $this->clearRedis();

        for ($i = 0; $i < 60; ++$i) {
            $I->sendGET('/api/books');
        }

        $I->sendGET('/api/books');
        $I->seeResponseCodeIs(429);

        $retryAfter = $I->grabHttpHeader('Retry-After');
        $I->assertNotNull($retryAfter);
        $I->assertIsNumeric($retryAfter);
        $I->assertGreaterThan(0, (int)$retryAfter);

        $I->seeResponseContainsJson(['error' => 'Rate limit exceeded']);
        $I->seeResponseContainsJson(['message' => 'Too many requests']);
        $I->seeResponseContainsJson(['retryAfter' => (int)$retryAfter]);
    }

    public function testApiBookIndexReturnsJsonBodyOn429(FunctionalTester $I): void
    {
        $this->clearRedis();

        for ($i = 0; $i < 61; ++$i) {
            $I->sendGET('/api/books');
        }

        $I->seeResponseCodeIs(429);
        $I->seeHttpHeader('Content-Type', 'application/json');

        $I->seeResponseContainsJson(['error' => 'Rate limit exceeded']);
        $I->seeResponseContainsJson(['message' => 'Too many requests, try again later.']);

        $response = json_decode($I->grabResponse(), true);
        $I->assertArrayHasKey('retryAfter', $response);
        $I->assertIsInt($response['retryAfter']);
        $I->assertGreaterThan(0, $response['retryAfter']);
    }

    public function testApiBookIndexIsolatesIpAddresses(FunctionalTester $I): void
    {
        $this->clearRedis();

        for ($i = 0; $i < 60; ++$i) {
            $I->sendGET('/api/books');
            $I->seeResponseCodeIs(200);
        }

        $I->sendGET('/api/books');
        $I->seeResponseCodeIs(429);

        $I->haveHttpHeader('X-Forwarded-For', '192.168.1.100');
        $I->sendGET('/api/books');
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('X-RateLimit-Remaining', '59');
    }
}
