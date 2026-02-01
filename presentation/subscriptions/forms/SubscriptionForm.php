<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\forms;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorQueryServiceInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Override;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Yii;
use yii\base\Model;
use yii\web\Request;

final class SubscriptionForm extends Model
{
    /** @var string|int|null */
    public $phone = '';

    /** @var int|string */
    public $authorId = 0;

    public function __construct(
        private readonly AuthorQueryServiceInterface $authorQueryService,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    #[Override]
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

    #[Override]
    #[CodeCoverageIgnore]
    public function attributeLabels(): array
    {
        return [
            'phone' => Yii::t('app', 'ui.phone'),
            'authorId' => Yii::t('app', 'ui.author'),
        ];
    }

    public function validatePhone(string $attribute): void
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($this->$attribute, PhoneNumberUtil::UNKNOWN_REGION);

            if (!$phoneUtil->isValidNumber($phoneNumber)) {
                $this->addError($attribute, Yii::t('app', 'phone.error.invalid_format'));
                return;
            }

            $this->$attribute = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            $this->addError($attribute, Yii::t('app', 'phone.error.invalid_format_hint'));
        }
    }

    public function validateAuthorExists(string $attribute): void
    {
        $value = $this->$attribute;

        if (!is_int($value) && !is_string($value)) {
            $this->addError($attribute, Yii::t('app', 'author.error.invalid_id'));
            return;
        }

        $authorId = (int)$value;

        if ($authorId <= 0) {
            $this->addError($attribute, Yii::t('app', 'author.error.invalid_id'));
            return;
        }

        if ($this->authorQueryService->findById($authorId) instanceof AuthorReadDto) {
            return;
        }

        $this->addError($attribute, Yii::t('app', 'author.error.not_exists'));
    }

    #[CodeCoverageIgnore]
    public function loadFromRequest(Request $request): bool
    {
        return $this->load((array)$request->post());
    }
}
