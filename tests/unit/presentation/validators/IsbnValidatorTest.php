<?php

declare(strict_types=1);

namespace tests\unit\presentation\validators;

use app\presentation\validators\IsbnValidator;
use Codeception\Test\Unit;
use yii\base\Model;

final class IsbnValidatorTest extends Unit
{
    private IsbnValidator $validator;

    protected function _before(): void
    {
        $this->validator = new IsbnValidator();
    }

    /**
     * @dataProvider provideValidIsbns
     */
    public function testValidIsbn(string $isbn): void
    {
        $model = new class extends Model {
            public $isbn;
        };
        $model->isbn = $isbn;

        $this->validator->validateAttribute($model, 'isbn');

        $this->assertEmpty($model->getErrors(), "ISBN '$isbn' should be valid");
    }

    /**
     * @dataProvider provideInvalidIsbns
     */
    public function testInvalidIsbn(string $isbn): void
    {
        $model = new class extends Model {
            public $isbn;
        };
        $model->isbn = $isbn;

        $this->validator->validateAttribute($model, 'isbn');

        $this->assertNotEmpty($model->getErrors(), "ISBN '$isbn' should be invalid");
        $this->assertStringContainsString('Некорректный ISBN', $model->getFirstError('isbn'));
    }

    /**
     * @return string[][]
     */
    public function provideValidIsbns(): array
    {
        return [
            ['978-3-16-148410-0'],
            ['9783161484100'],
            ['0-306-40615-2'],
            ['0306406152'],
            ['0-8044-2957-X'],
            ['080442957x'],
        ];
    }

    /**
     * @return string[][]
     */
    public function provideInvalidIsbns(): array
    {
        return [
            ['123'],
            ['123456789012345'],
            ['978-3-16-148410-1'],
            ['0-306-40615-5'],
            ['invalid-string'],
            ['978-1-23-456789-X'],
            ['123456789'],
        ];
    }
}