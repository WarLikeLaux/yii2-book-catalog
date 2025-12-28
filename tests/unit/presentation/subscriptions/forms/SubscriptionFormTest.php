<?php

declare(strict_types=1);

namespace tests\unit\presentation\subscriptions\forms;

use app\infrastructure\persistence\Author;
use app\presentation\subscriptions\forms\SubscriptionForm;
use Codeception\Test\Unit;

final class SubscriptionFormTest extends Unit
{
    protected function _before(): void
    {
        $author = new Author();
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
        $form->phone = '+7 900 111 22 33';
        $form->authorId = 1;

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
        $this->assertNotEmpty($form->getFirstError('phone'));
    }

    public function testValidateParseableButInvalidNumber(): void
    {
        $form = new SubscriptionForm();
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
