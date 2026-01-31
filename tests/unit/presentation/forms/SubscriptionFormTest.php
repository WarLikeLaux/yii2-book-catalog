<?php

declare(strict_types=1);

namespace tests\unit\presentation\forms;

use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\subscriptions\forms\SubscriptionForm;
use Codeception\Test\Unit;

final class SubscriptionFormTest extends Unit
{
    public function testValidatePhoneAddsErrorForParsableButInvalidNumber(): void
    {
        $form = new SubscriptionForm($this->createMock(AuthorQueryServiceInterface::class));
        $form->phone = '+1555';

        $form->validatePhone('phone');

        $this->assertTrue($form->hasErrors('phone'));
    }

    public function testValidatePhoneFormatsValidNumber(): void
    {
        $form = new SubscriptionForm($this->createMock(AuthorQueryServiceInterface::class));
        $form->phone = '+7 999 123-45-67';

        $form->validatePhone('phone');

        $this->assertFalse($form->hasErrors('phone'));
        $this->assertEquals('+79991234567', $form->phone);
    }

    public function testValidatePhoneAddsErrorForUnparseableNumber(): void
    {
        $form = new SubscriptionForm($this->createMock(AuthorQueryServiceInterface::class));
        $form->phone = 'not-a-phone';

        $form->validatePhone('phone');

        $this->assertTrue($form->hasErrors('phone'));
    }
}
