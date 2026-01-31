<?php

declare(strict_types=1);

namespace app\presentation\components;

use Stringable;
use Yii;
use yii\base\Model;
use yii\base\Widget;
use yii\helpers\Html;

final class ErrorSummaryWidget extends Widget
{
    /**
     * @var Model|array<Model>
     */
    public Model|array $models;

    /**
     * @var array<string, mixed>
     */
    public array $options = [];

    public function run(): string
    {
        $models = $this->normalizeModels();
        $encode = $this->extractEncodeOption();
        $header = $this->extractHeaderOption();
        $footer = $this->extractFooterOption();

        Html::addCssClass($this->options, ['alert', 'alert-danger', 'error-summary']);

        $lines = $this->collectLines($models, $encode);
        $this->applyEmptyStyles($lines);

        return $this->render('error-summary', [
            'header' => $header,
            'footer' => $footer,
            'lines' => $lines,
            'options' => $this->options,
        ]);
    }

    /**
     * @return array<Model>
     */
    private function normalizeModels(): array
    {
        $models = $this->models;

        if (!is_array($models)) {
            return [$models];
        }

        return $models;
    }

    private function extractEncodeOption(): bool
    {
        $encode = (bool)($this->options['encode'] ?? true);
        unset($this->options['encode']);

        return $encode;
    }

    private function extractHeaderOption(): string
    {
        $header = $this->options['header'] ?? null;
        unset($this->options['header']);

        if ($header === null) {
            return '<p>' . Yii::t('yii', 'Please fix the following errors:') . '</p>';
        }

        if (is_string($header)) {
            return $header;
        }

        if ($header instanceof Stringable) {
            return (string)$header;
        }

        return '<p>' . Yii::t('yii', 'Please fix the following errors:') . '</p>';
    }

    private function extractFooterOption(): string
    {
        $footer = $this->options['footer'] ?? null;
        unset($this->options['footer']);

        if ($footer === null) {
            return '';
        }

        if (is_string($footer)) {
            return $footer;
        }

        if ($footer instanceof Stringable) {
            return (string)$footer;
        }

        return '';
    }

    /**
     * @param array<Model> $models
     * @return array<string>
     */
    private function collectLines(array $models, bool $encode): array
    {
        $lines = [];

        foreach ($models as $model) {
            $lines = array_unique(array_merge($lines, $model->getErrorSummary(true)));
        }

        $lines = array_values($lines);

        if ($encode) {
            foreach ($lines as &$line) {
                $line = Html::encode($line);
            }
        }

        return $lines;
    }

    /**
     * @param array<string> $lines
     */
    private function applyEmptyStyles(array $lines): void
    {
        if ($lines !== []) {
            return;
        }

        $style = '';

        if (isset($this->options['style']) && is_string($this->options['style'])) {
            $style = $this->options['style'];
        }

        $this->options['style'] = $style !== '' ? rtrim($style, ';') . '; display:none' : 'display:none';
    }
}
