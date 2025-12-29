<?php

declare(strict_types=1);

namespace tests\unit\presentation\forms;

use app\presentation\subscriptions\forms\SubscriptionForm;
use Codeception\Test\Unit;

final class SubscriptionFormTest extends Unit
{
    public function testValidatePhoneAddsErrorForParsableButInvalidNumber(): void
    {
        $form = new SubscriptionForm();
        $form->phone = '+1555';

        $form->validatePhone('phone');

        $this->assertTrue($form->hasErrors('phone'));
    }
}
