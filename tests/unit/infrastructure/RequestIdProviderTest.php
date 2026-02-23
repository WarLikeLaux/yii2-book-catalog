<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\application\ports\RequestIdProviderInterface;
use app\infrastructure\services\observability\RequestIdProvider;
use Codeception\Test\Unit;

final class RequestIdProviderTest extends Unit
{
    protected function _before(): void
    {
        RequestIdProvider::reset();
    }

    protected function _after(): void
    {
        RequestIdProvider::reset();
    }

    public function testGetReturnsSameIdWithinRequest(): void
    {
        $id1 = RequestIdProvider::get();
        $id2 = RequestIdProvider::get();

        $this->assertSame($id1, $id2);
    }

    public function testGetReturnsValidUuidFormat(): void
    {
        $id = RequestIdProvider::get();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id,
            'Request ID should be valid UUIDv4',
        );
    }

    public function testResetGeneratesNewId(): void
    {
        $id1 = RequestIdProvider::get();

        RequestIdProvider::reset();
        $id2 = RequestIdProvider::get();

        $this->assertNotSame($id1, $id2);
    }

    public function testIdIsNotEmpty(): void
    {
        $id = RequestIdProvider::get();

        $this->assertNotEmpty($id);
        $this->assertSame(36, strlen($id));
    }

    public function testGetRequestIdDelegatesToStaticGet(): void
    {
        $provider = new RequestIdProvider();

        $this->assertSame(RequestIdProvider::get(), $provider->getRequestId());
    }

    public function testImplementsRequestIdProviderInterface(): void
    {
        $provider = new RequestIdProvider();

        $this->assertInstanceOf(RequestIdProviderInterface::class, $provider);
    }
}
