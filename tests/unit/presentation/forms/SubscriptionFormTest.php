<?php

declare(strict_types=1);

namespace tests\unit\presentation\forms;

use app\presentation\forms\SubscriptionForm;
use Codeception\Test\Unit;

final class SubscriptionFormTest extends Unit
{
    public function testRulesExist(): void
    {
        $form = new SubscriptionForm();
        $rules = $form->rules();
        
        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
    }

    public function testAttributeLabels(): void
    {
        $form = new SubscriptionForm();
        $labels = $form->attributeLabels();
        
        $this->assertArrayHasKey('phone', $labels);
        $this->assertArrayHasKey('authorId', $labels);
    }

    public function testValidatePhoneWithValidNumber(): void
    {
        $form = new SubscriptionForm();
        $form->phone = '+79001234567';
        $form->authorId = 1;
        
        $form->validatePhone('phone');
        
        $this->assertFalse($form->hasErrors('phone'));
        $this->assertSame('+79001234567', $form->phone);
    }

    public function testValidatePhoneWithInvalidNumber(): void
    {
        $form = new SubscriptionForm();
        $form->phone = '123';
        
        $form->validatePhone('phone');
        
        $this->assertTrue($form->hasErrors('phone'));
    }

    public function testValidatePhoneWithUnparseable(): void
    {
        $form = new SubscriptionForm();
        $form->phone = 'not-a-phone';
        
        $form->validatePhone('phone');
        
        $this->assertTrue($form->hasErrors('phone'));
    }
}
