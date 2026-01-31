<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 */
/**
 * @var array<int, array{label: string, value: string, url: string, logoUrl: string}> $items
 */
use yii\bootstrap5\Html;

?>

<div class="system-info-container ms-auto d-flex align-items-center gap-1">
    <?php foreach ($items as $item): ?>
        <?= Html::a(
            Html::img($item['logoUrl'], ['class' => 'system-logo-img', 'alt' => $item['label']]) .
            '<span>' .
                '<span class="text-uppercase fw-bold opacity-75">' . Html::encode($item['label']) . ':</span>' .
                '<span class="ms-1 text-dev-value">' . Html::encode($item['value']) . '</span>' .
            '</span>',
            $item['url'],
            [
                'class' => 'system-info-item',
                'target' => '_blank',
                'title' => "Official {$item['label']} Site",
            ],
        ) ?>
    <?php endforeach; ?>
</div>