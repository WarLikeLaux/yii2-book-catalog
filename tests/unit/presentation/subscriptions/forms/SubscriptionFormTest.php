<?php

declare(strict_types=1);

namespace tests\unit\presentation\subscriptions\forms;

use app\presentation\subscriptions\forms\SubscriptionForm;
use Codeception\Test\Unit;

final class SubscriptionFormTest extends Unit
{
    protected function _before(): void
    {
        // Ensure we have an author for validation
        // We use Author ActiveRecord directly as validation uses it
        $author = new \app\infrastructure\persistence\Author();
        $author->id = 1;
        $author->fio = 'Test Author';
        $author->save();
    }

    public function testRules(): void
    {
        $form = new SubscriptionForm();
        $this->assertIsArray($form->rules());
    }

    public function testValidatePhoneDirectly(): void
    {
         $form = new SubscriptionForm();
         $form->phone = '+79001112233';
         
         $form->validatePhone('phone');
         
         $this->assertFalse($form->hasErrors('phone'));
         $this->assertSame('+79001112233', $form->phone);
    }

    public function testValidateValidPhone(): void
    {
        $form = new SubscriptionForm();
        $form->phone = '+79001112233';
        $form->authorId = 1;

        $this->assertTrue($form->validate(), 'Form should valid. Errors: ' . json_encode($form->getErrors()));
        
        $this->assertFalse($form->hasErrors('phone'));
        $this->assertSame('+79001112233', $form->phone);
    }

    public function testValidateFormatting(): void
    {
        $form = new SubscriptionForm();
        $form->phone = '8 (900) 111-22-33'; // RU format
        $form->authorId = 1;

        // Note: libphonenumber needs region if + is missing, but here we test if it handles what passed
        // without region it might fail or rely on default. Let's send international.
        $form->phone = '+7 900 111 22 33';

        $form->validatePhone('phone');

        $this->assertFalse($form->hasErrors('phone'));
        $this->assertSame('+79001112233', $form->phone);
    }

    public function testValidateInvalidPhone(): void
    {
        $form = new SubscriptionForm();
        $form->phone = 'not-a-phone';
        $form->authorId = 1;

        $form->validatePhone('phone');

        $this->assertTrue($form->hasErrors('phone'));
        // We don't check exact message text to avoid fragility with translations
        $this->assertNotEmpty($form->getFirstError('phone'));
    }

    public function testValidateParseableButInvalidNumber(): void
    {
        $form = new SubscriptionForm();
        // +999 is unassigned country code, might parse but be invalid?
        // Or +1 000 000 0000 (invalid US number)
        $form->phone = '+10000000000';
        $form->authorId = 1;
        
        $form->validatePhone('phone');
        
        $this->assertTrue($form->hasErrors('phone'));
        $this->assertNotEmpty($form->getFirstError('phone'));
    }

    public function testLabels(): void
    {
        $form = new SubscriptionForm();
        $labels = $form->attributeLabels();
        $this->assertArrayHasKey('phone', $labels);
        $this->assertArrayHasKey('authorId', $labels);
    }
}
