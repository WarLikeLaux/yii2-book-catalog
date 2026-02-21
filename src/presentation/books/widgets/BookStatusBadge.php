<?php

declare(strict_types=1);

namespace app\presentation\books\widgets;

use Override;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;

final class BookStatusBadge extends Widget
{
    private const array CSS_MAP = [
        'published' => 'bg-success',
        'archived' => 'bg-dark',
        'draft' => 'bg-secondary',
    ];

    public string $status;

    #[Override]
    public function run(): string
    {
        $class = self::CSS_MAP[$this->status] ?? 'bg-secondary';
        $label = Yii::t('app', 'ui.status_' . $this->status);

        return Html::tag('span', Html::encode($label), ['class' => "badge $class"]);
    }
}
