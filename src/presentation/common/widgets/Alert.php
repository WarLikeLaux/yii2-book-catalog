<?php

declare(strict_types=1);

namespace app\presentation\common\widgets;

use Yii;
use yii\bootstrap5\Alert as BootstrapAlert;
use yii\bootstrap5\Widget;
use yii\web\Application;

final class Alert extends Widget
{
    /** @var array<string, string> */
    public array $alertTypes = [
        'error' => 'alert-danger',
        'danger' => 'alert-danger',
        'success' => 'alert-success',
        'info' => 'alert-info',
        'warning' => 'alert-warning',
    ];

    /**
     * @var array<string, mixed> $closeButton Опции для рендеринга кнопки закрытия сообщения.
     */
    public array $closeButton = [];

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $app = Yii::$app;

        if (!$app instanceof Application) {
            return;
        }

        $session = $app->session;
        $appendClass = isset($this->options['class']) ? ' ' . $this->options['class'] : '';

        foreach (array_keys($this->alertTypes) as $type) {
            $flash = $session->getFlash($type);

            foreach ((array)$flash as $i => $message) {
                echo BootstrapAlert::widget([
                    'body' => $message,
                    'closeButton' => $this->closeButton,
                    'options' => array_merge($this->options, [
                        'id' => $this->getId() . '-' . $type . '-' . $i,
                        'class' => $this->alertTypes[$type] . $appendClass,
                    ]),
                ]);
            }

            $session->removeFlash($type);
        }
    }
}
