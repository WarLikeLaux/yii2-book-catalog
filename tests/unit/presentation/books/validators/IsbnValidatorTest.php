<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\validators;

use app\application\common\services\IsbnFormatValidator;
use app\presentation\books\validators\IsbnValidator;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Yii;
use yii\base\Model;

final class IsbnValidatorTest extends Unit
{
    private IsbnFormatValidator|MockObject $formatValidator;

    protected function _before(): void
    {
        $this->formatValidator = $this->createMock(IsbnFormatValidator::class);
    }

    public function testInitSetsDefaultMessage(): void
    {
        $validator = new IsbnValidator($this->formatValidator);

        $validator->init();

        $this->assertEquals(Yii::t('app', 'isbn.error.invalid_format_hint'), $validator->message);
    }

    public function testInitDoesNotOverrideMessage(): void
    {
        $validator = new IsbnValidator($this->formatValidator, ['message' => 'Custom error']);

        $validator->init();

        $this->assertEquals('Custom error', $validator->message);
    }

    public function testValidateAttributeAddsErrorOnNonString(): void
    {
        $validator = new IsbnValidator($this->formatValidator);
        $validator->init();

        $model = new class extends Model {
            public $isbn = 123;
        };

        $validator->validateAttribute($model, 'isbn');

        $this->assertTrue($model->hasErrors('isbn'));
    }

    public function testValidateAttributeAddsErrorOnInvalidFormat(): void
    {
        $this->formatValidator->method('isValid')->willReturn(false);

        $validator = new IsbnValidator($this->formatValidator);
        $validator->init();

        $model = new class extends Model {
            public $isbn = 'invalid-isbn';
        };

        $validator->validateAttribute($model, 'isbn');

        $this->assertTrue($model->hasErrors('isbn'));
    }

    public function testValidateAttributePassesOnValidFormat(): void
    {
        $this->formatValidator->method('isValid')->willReturn(true);

        $validator = new IsbnValidator($this->formatValidator);
        $validator->init();

        $model = new class extends Model {
            public $isbn = 'valid-isbn';
        };

        $validator->validateAttribute($model, 'isbn');

        $this->assertEmpty($model->getErrors('isbn'));
    }
}
