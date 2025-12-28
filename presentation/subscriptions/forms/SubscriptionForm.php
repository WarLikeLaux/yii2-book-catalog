<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\forms;

use app\infrastructure\persistence\Author;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Yii;
use yii\base\Model;

final class SubscriptionForm extends Model
{
    /** @var string|int|null */
    public $phone = '';

    /** @var int|string */
    public $authorId = 0;

    #[\Override]
    public function rules(): array
    {
        return [
            [['phone', 'authorId'], 'required'],
            [['authorId'], 'integer'],
            [['authorId'], 'exist', 'targetClass' => Author::class, 'targetAttribute' => 'id'],
            [['phone'], 'trim'],
            [['phone'], 'string', 'max' => 20],
            [['phone'], 'validatePhone'],
        ];
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return [
            'phone' => Yii::t('app', 'Phone'),
            'authorId' => Yii::t('app', 'Author'),
        ];
    }

    public function validatePhone(string $attribute): void
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($this->$attribute, PhoneNumberUtil::UNKNOWN_REGION);

            if (!$phoneUtil->isValidNumber($phoneNumber)) {
                $this->addError($attribute, Yii::t('app', 'Invalid phone format'));
                return;
            }

            $this->$attribute = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            $this->addError($attribute, Yii::t('app', 'Invalid phone format. Use international format (e.g., +79991234567)'));
        }
    }
}
