<?php

declare(strict_types=1);

namespace app\presentation\books\widgets;

use app\domain\values\BookStatus;
use Override;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;

final class BookStatusActions extends Widget
{
    private const array TRANSITIONS = [
        BookStatus::Draft->value => [
            ['publish', 'ui.publish', 'btn-success', 'book.confirm.publish'],
        ],
        BookStatus::Published->value => [
            ['unpublish', 'ui.unpublish', 'btn-warning', 'book.confirm.unpublish'],
            ['archive', 'ui.archive', 'btn-secondary', 'book.confirm.archive'],
        ],
        BookStatus::Archived->value => [
            ['restore', 'ui.restore', 'btn-info', 'book.confirm.restore'],
        ],
    ];

    public int $bookId;
    public string $status;

    #[Override]
    public function run(): string
    {
        $transitions = self::TRANSITIONS[$this->status] ?? [];
        $buttons = [];

        foreach ($transitions as [$action, $labelKey, $btnClass, $confirmKey]) {
            $buttons[] = Html::a(
                Yii::t('app', $labelKey),
                [$action, 'id' => $this->bookId],
                [
                    'class' => "btn $btnClass",
                    'data' => [
                        'confirm' => Yii::t('app', $confirmKey),
                        'method' => 'post',
                    ],
                ],
            );
        }

        return implode("\n", $buttons);
    }
}
