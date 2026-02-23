<?php

declare(strict_types=1);

namespace app\presentation\books\dto;

use app\presentation\books\forms\BookSearchForm;
use app\presentation\common\ViewModelInterface;
use yii\data\DataProviderInterface;

final readonly class BookIndexViewModel implements ViewModelInterface
{
    public function __construct(
        public BookSearchForm $searchModel,
        public DataProviderInterface $dataProvider,
    ) {
    }
}
