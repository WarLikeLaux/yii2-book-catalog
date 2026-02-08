<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\sms;

use app\infrastructure\services\sms\SmsPilotSender;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use ReflectionClass;

final class SmsPilotSenderTest extends Unit
{
    private SmsPilotSender $sender;

    protected function _before(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->sender = new SmsPilotSender('test-key', $logger);
    }

    public function testParseResponseStatusReturnsOk(): void
    {
        $json = json_encode(['send' => [['status' => 'OK']]], JSON_THROW_ON_ERROR);

        $this->assertSame('OK', $this->invokeParseResponseStatus($json));
    }

    public function testParseResponseStatusReturnsZero(): void
    {
        $json = json_encode(['send' => [['status' => '0']]], JSON_THROW_ON_ERROR);

        $this->assertSame('0', $this->invokeParseResponseStatus($json));
    }

    public function testParseResponseStatusReturnsNullForInvalidJson(): void
    {
        $this->assertNull($this->invokeParseResponseStatus('not json'));
    }

    public function testParseResponseStatusReturnsNullForEmptySend(): void
    {
        $json = json_encode(['send' => []], JSON_THROW_ON_ERROR);

        $this->assertNull($this->invokeParseResponseStatus($json));
    }

    public function testParseResponseStatusReturnsNullWhenSendIsNotArray(): void
    {
        $json = json_encode(['send' => 'string'], JSON_THROW_ON_ERROR);

        $this->assertNull($this->invokeParseResponseStatus($json));
    }

    public function testParseResponseStatusReturnsNullWhenFirstElementNotArray(): void
    {
        $json = json_encode(['send' => ['string']], JSON_THROW_ON_ERROR);

        $this->assertNull($this->invokeParseResponseStatus($json));
    }

    public function testParseResponseStatusReturnsNullWhenNoStatusKey(): void
    {
        $json = json_encode(['send' => [['id' => 123]]], JSON_THROW_ON_ERROR);

        $this->assertNull($this->invokeParseResponseStatus($json));
    }

    public function testParseResponseStatusReturnsNullWhenStatusNotString(): void
    {
        $json = json_encode(['send' => [['status' => 123]]], JSON_THROW_ON_ERROR);

        $this->assertNull($this->invokeParseResponseStatus($json));
    }

    public function testParseResponseStatusReturnsNullWhenNoSendKey(): void
    {
        $json = json_encode(['error' => 'something'], JSON_THROW_ON_ERROR);

        $this->assertNull($this->invokeParseResponseStatus($json));
    }

    private function invokeParseResponseStatus(string $responseBody): ?string
    {
        $reflection = new ReflectionClass($this->sender);
        $method = $reflection->getMethod('parseResponseStatus');

        /** @var ?string */
        return $method->invoke($this->sender, $responseBody);
    }
}
