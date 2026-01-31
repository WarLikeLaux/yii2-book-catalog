<?php

declare(strict_types=1);

namespace app\presentation\books\dto;

use app\presentation\books\forms\BookSearchForm;
use yii\data\DataProviderInterface;

final readonly class BookIndexViewModel
{
    public function __construct(
        public BookSearchForm $searchModel,
        public DataProviderInterface $dataProvider,
    ) {
    }
}
