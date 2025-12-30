<?php

declare(strict_types=1);

namespace tests\unit\presentation\validators;

use app\presentation\books\validators\IsbnValidator;
use Codeception\Test\Unit;
use yii\base\Model;

final class ValidatorEdgeCasesTest extends Unit
{
    public function testIsbnValidatorAddsErrorForNonStringValue(): void
    {
        $validator = new IsbnValidator();

        $model = new class extends Model {
            public $isbn = null;
        };

        $validator->validateAttribute($model, 'isbn');

        $this->assertTrue($model->hasErrors('isbn'));
    }

    public function testIsbnValidatorAddsErrorForInvalidIsbn(): void
    {
        $validator = new IsbnValidator();

        $model = new class extends Model {
            public $isbn = 'invalid-isbn';
        };

        $validator->validateAttribute($model, 'isbn');

        $this->assertTrue($model->hasErrors('isbn'));
    }

    public function testIsbnValidatorPassesForValidIsbn(): void
    {
        $validator = new IsbnValidator();

        $model = new class extends Model {
            public $isbn = '9783161484100';
        };

        $validator->validateAttribute($model, 'isbn');

        $this->assertFalse($model->hasErrors('isbn'));
    }
}
