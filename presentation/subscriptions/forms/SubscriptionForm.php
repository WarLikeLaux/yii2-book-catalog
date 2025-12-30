<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\forms;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorRepositoryInterface;
use app\presentation\common\forms\RepositoryAwareForm;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Yii;

final class SubscriptionForm extends RepositoryAwareForm
{
    /** @var string|int|null */
    public $phone = '';

    /** @var int|string */
    public $authorId = 0;

    #[\Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            [['phone', 'authorId'], 'required'],
            [['authorId'], 'integer'],
            [['authorId'], 'validateAuthorExists'],
            [['phone'], 'trim'],
            [['phone'], 'string', 'max' => 20],
            [['phone'], 'validatePhone'],
        ];
    }

    #[\Override]
    #[CodeCoverageIgnore]
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

    public function validateAuthorExists(string $attribute): void
    {
        $value = $this->$attribute;

        if (!is_int($value) && !is_string($value)) {
            $this->addError($attribute, Yii::t('app', 'Invalid author ID')); // @codeCoverageIgnore
            return; // @codeCoverageIgnore
        }

        $authorId = (int)$value;

        if ($authorId <= 0) {
            $this->addError($attribute, Yii::t('app', 'Invalid author ID'));
            return;
        }

        $repository = $this->resolve(AuthorRepositoryInterface::class);

        if ($repository->findById($authorId) instanceof AuthorReadDto) {
            return;
        }

        $this->addError($attribute, Yii::t('app', 'Author does not exist'));
    }
}
