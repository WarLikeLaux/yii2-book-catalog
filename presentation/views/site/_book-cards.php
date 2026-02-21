<?php

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var yii\data\DataProviderInterface $dataProvider
 */

$pagination = $dataProvider->getPagination();
$currentPage = $pagination->getPage();
$pageCount = $pagination->getPageCount();
$hasMore = $currentPage < $pageCount - 1;

?>

<div class="row" id="book-cards-container">
    <?php foreach ($dataProvider->getModels() as $book): ?>
        <?= $this->render('_book-card', ['book' => $book]) ?>
    <?php endforeach; ?>
</div>

<?php if ($hasMore):
    $nextPage = $currentPage + 2;
    $nextUrl = Url::current(['page' => $nextPage]);
    ?>
    <div id="load-more-container" class="mt-4">
        <div
            hx-get="<?= Html::encode($nextUrl) ?>"
            hx-target="#book-cards-container"
            hx-swap="beforeend"
            hx-trigger="revealed"
            hx-select="#book-cards-container > .col-md-4, #load-more-container"
            hx-select-oob="#load-more-container"
            class="d-flex justify-content-center py-4"
        >
            <div class="book-skeleton-container row w-100">
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <?= $this->render('_skeleton-card') ?>
                <?php endfor; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div id="load-more-container"></div>
<?php endif; ?>
