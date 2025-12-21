<?php

declare(strict_types=1);

namespace app\application\books\queries;

use app\models\forms\BookSearchForm;
use yii\data\DataProviderInterface;

readonly class BookSearchPageData
{
    public function __construct(
        public BookSearchForm $searchForm,
        public DataProviderInterface $dataProvider
    ) {
    }
}
