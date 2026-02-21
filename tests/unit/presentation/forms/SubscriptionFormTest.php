<?php

declare(strict_types=1);

namespace tests\unit\presentation\forms;

use app\presentation\subscriptions\forms\SubscriptionForm;
use Codeception\Test\Unit;

final class SubscriptionFormTest extends Unit
{
    public function testValidateRequiredFields(): void
    {
        $form = new SubscriptionForm();
        $form->phone = '';
        $form->authorId = null;

        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasErrors('phone'));
        $this->assertTrue($form->hasErrors('authorId'));
    }

    public function testValidateAuthorIdInteger(): void
    {
        $form = new SubscriptionForm();
        $form->phone = '+79991234567';
        $form->authorId = 'invalid';

        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasErrors('authorId'));
    }

    public function testValidatePhoneMaxLength(): void
    {
        $form = new SubscriptionForm();
        $form->phone = str_repeat('1', 21);
        $form->authorId = 1;

        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasErrors('phone'));
    }

    public function testValidateSuccess(): void
    {
        $form = new SubscriptionForm();
        $form->phone = '+79991234567';
        $form->authorId = 1;

        $this->assertTrue($form->validate());
    }
}
