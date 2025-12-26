<?php

declare(strict_types=1);

namespace tests\unit;

use app\presentation\validators\IsbnValidator;
use app\presentation\forms\BookForm;
use Codeception\Test\Unit;

final class IsbnFormValidatorTest extends Unit
{
    public function testValidateAttributeWithValidIsbn(): void
    {
        $validator = new IsbnValidator();
        
        $form = new BookForm();
        $form->isbn = '9783161484100';
        
        $validator->validateAttribute($form, 'isbn');
        
        $this->assertFalse($form->hasErrors('isbn'));
    }

    public function testValidateAttributeWithInvalidIsbn(): void
    {
        $validator = new IsbnValidator();
        
        $form = new BookForm();
        $form->isbn = 'invalid';
        
        $validator->validateAttribute($form, 'isbn');
        
        $this->assertTrue($form->hasErrors('isbn'));
    }
}
