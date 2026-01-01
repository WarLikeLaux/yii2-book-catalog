<?php

declare(strict_types=1);

namespace app\presentation\components;

use app\presentation\widgets\RandomDataButton;
use yii\bootstrap5\ActiveField as BootstrapActiveField;
use yii\helpers\Html;

class ActiveField extends BootstrapActiveField
{
    /**
     * @param array<string, mixed> $options
     */
    public function withRandomGenerator(string $type, array $options = []): self
    {
        $inputId = Html::getInputId($this->model, $this->attribute);

        $button = RandomDataButton::widget([
            'type' => $type,
            'targetSelector' => '#' . $inputId,
            'title' => $options['title'] ?? 'Сгенерировать',
        ]);

        $this->template = "{label}\n<div class=\"input-group\">{input}{$button}</div>\n{hint}\n{error}";

        return $this;
    }
}
