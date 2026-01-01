<?php

declare(strict_types=1);

namespace app\presentation\widgets;

use yii\base\Widget;
use yii\helpers\Html;

final class RandomDataButton extends Widget
{
    public string $type;

    public string $targetSelector;

    public string $title = 'Сгенерировать данные';

    public string $text = '🎲';

    /** @var array<string, mixed> */
    public array $options = [];

    public function run(): string
    {
        $options = array_merge([
            'type' => 'button',
            'class' => 'btn btn-outline-secondary btn-isbn-generator',
            'title' => $this->title,
            'data-bs-toggle' => 'tooltip',
            'data-action' => 'generate-data',
            'data-type' => $this->type,
            'data-target' => $this->targetSelector,
        ], $this->options);

        return Html::tag('button', $this->text, $options);
    }
}
