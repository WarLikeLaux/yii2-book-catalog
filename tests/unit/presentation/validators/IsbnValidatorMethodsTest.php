<?php

declare(strict_types=1);

namespace tests\unit\presentation\validators;

use app\presentation\forms\BookForm;
use app\application\common\validators\IsbnValidator;
use Codeception\Test\Unit;
use yii\base\Model;

final class IsbnValidatorMethodsTest extends Unit
{
    public function testValidateAttributeExistingError(): void
    {
        $validator = new IsbnValidator();
        
        $form = new BookForm();
        $form->isbn = '';
        $form->addError('isbn', 'Required');
        
        $validator->validateAttribute($form, 'isbn');
        
        $this->assertTrue($form->hasErrors('isbn'));
    }

    public function testInit(): void
    {
        $validator = new IsbnValidator();
        $this->assertInstanceOf(IsbnValidator::class, $validator);
    }

    public function testValidateAttributeWithNonStringValueAddsError(): void
    {
        $validator = new IsbnValidator();

        $form = new class extends Model {
            public $isbn = null;
        };

        $validator->validateAttribute($form, 'isbn');

        $this->assertTrue($form->hasErrors('isbn'));
    }
}

