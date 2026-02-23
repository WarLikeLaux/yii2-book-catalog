<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\forms;

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

    #[Override]
    #[CodeCoverageIgnore]
    public function rules(): array
    {
        return [
            [['phone', 'authorId'], 'required'],
            [['authorId'], 'integer'],
            [['phone'], 'trim'],
            [['phone'], 'string', 'max' => 20],
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

    #[CodeCoverageIgnore]
    public function loadFromRequest(Request $request): bool
    {
        return $this->load((array)$request->post());
    }
}
