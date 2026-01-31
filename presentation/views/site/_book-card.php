<?php

declare(strict_types=1);

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\application\books\queries\BookReadDto $book
 */

$coverUrl = $book->coverUrl ?? '';
?>

<div class="col-md-4 mb-4">
    <div class="card h-100 shadow-sm">
        <?php if ($coverUrl !== ''): ?>
        <div style="height: 200px; overflow: hidden;">
            <?= Html::a(
                Html::img($coverUrl, ['class' => 'card-img-top', 'alt' => $book->title, 'style' => 'width: 100%; height: 100%; object-fit: cover; cursor: pointer;', 'loading' => 'lazy']),
                $coverUrl,
                ['class' => 'glightbox', 'data-gallery' => 'books-gallery', 'data-type' => 'image'],
            ) ?>
        </div>
        <?php endif; ?>
        <div class="card-body">
            <h5 class="card-title"><?= Html::encode($book->title) ?></h5>
            <p class="text-muted mb-1"><?= Html::encode($book->year) ?></p>

            <div class="mb-3">
                <?php foreach ($book->authorNames as $authorId => $fio): ?>
                    <span class="badge bg-secondary me-1">
                        <?= Html::encode($fio) ?>
                        <a href="#" class="text-white sub-link" data-id="<?= $authorId ?>" title="Подписаться">
                            <i class="bi bi-bell"></i> +
                        </a>
                    </span>
                <?php endforeach; ?>
            </div>

            <?php if ($book->description): ?>
                <p class="small"><?= Html::encode(mb_substr((string) $book->description, 0, 100)) ?>...</p>
            <?php endif; ?>
        </div>
    </div>
</div>
