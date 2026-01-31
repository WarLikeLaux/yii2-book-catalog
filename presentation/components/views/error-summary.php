<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var string $header
 * @var string $footer
 * @var array<string> $lines
 * @var array<string, mixed> $options
 */

use yii\helpers\Html;

?>

<?= Html::beginTag('div', $options) ?>
    <?= $header ?>
    <ul class="mb-0">
        <?php foreach ($lines as $line): ?>
            <li><?= $line ?></li>
        <?php endforeach; ?>
    </ul>
    <?= $footer ?>
<?= Html::endTag('div') ?>
