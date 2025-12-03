<?php

declare(strict_types=1);

namespace app\models\forms;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use yii\base\Model;

final class SubscriptionForm extends Model
{
    public string $phone = '';
    public int $authorId = 0;

    public function rules(): array
    {
        return [
            [['phone', 'authorId'], 'required'],
            [['authorId'], 'integer'],
            [['phone'], 'trim'],
            [['phone'], 'string', 'max' => 20],
            [['phone'], 'validatePhone'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'phone' => 'Телефон',
            'authorId' => 'Автор',
        ];
    }

    /**
     * Validates phone number format using libphonenumber.
     * Accepts international format with country code.
     *
     * @throws NumberParseException When phone format is invalid
     */
    public function validatePhone(string $attribute): void
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($this->$attribute, PhoneNumberUtil::UNKNOWN_REGION);

            if (!$phoneUtil->isValidNumber($phoneNumber)) {
                $this->addError($attribute, 'Неверный формат телефона');
                return;
            }

            $this->$attribute = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            $this->addError($attribute, 'Неверный формат телефона. Используйте международный формат с кодом страны (например, +79991234567)');
        }
    }
}
