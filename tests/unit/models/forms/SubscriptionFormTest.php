<?php

declare(strict_types=1);

namespace tests\unit\models\forms;

use app\models\forms\SubscriptionForm;
use Codeception\Test\Unit;
use Yii;

final class SubscriptionFormTest extends Unit
{
    protected function _before(): void
    {
        Yii::$app->db->createCommand()->insert('authors', [
            'id' => 1,
            'fio' => 'Test Author',
        ])->execute();
    }

    protected function _after(): void
    {
        Yii::$app->db->createCommand()->delete('authors', ['id' => 1])->execute();
    }

    public function testPhoneNormalizedToE164(): void
    {
        $form = new SubscriptionForm();
        $form->phone = '+7 (900) 123-45-67';
        $form->authorId = 1;

        verify($form->validate())->true();
        verify($form->phone)->equals('+79001234567');
    }

    public function testInvalidPhoneFailsValidation(): void
    {
        $form = new SubscriptionForm();
        $form->phone = '12345';
        $form->authorId = 1;

        verify($form->validate())->false();
        verify($form->getErrors('phone'))->notEmpty();
    }
}
