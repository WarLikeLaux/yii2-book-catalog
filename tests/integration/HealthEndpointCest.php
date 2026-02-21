<?php

declare(strict_types=1);

namespace tests\integration;

use IntegrationTester;

final class HealthEndpointCest
{
    public function testGetHealthStatus(IntegrationTester $I): void
    {
        $I->sendGet('/index-test.php?r=health/index');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        // At least check the root structure
        $I->seeResponseContainsJson([
            'status' => 'healthy',
        ]);

        /** @var array<string, mixed> $response */
        $response = json_decode((string) $I->grabResponse(), true);

        $I->assertArrayHasKey('timestamp', $response);
        $I->assertArrayHasKey('version', $response);
        $I->assertArrayHasKey('checks', $response);

        /** @var array<string, mixed> $checks */
        $checks = $response['checks'];

        $I->assertArrayHasKey('database', $checks);
        $I->assertArrayHasKey('redis', $checks);
        $I->assertArrayHasKey('queue', $checks);
        $I->assertArrayHasKey('disk', $checks);
    }
}
