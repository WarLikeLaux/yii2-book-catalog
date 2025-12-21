<?php

declare(strict_types=1);

namespace app\application\books\queries;

use app\interfaces\QueryResultInterface;
use app\models\forms\BookSearchForm;

readonly class BookSearchPageData
{
    public function __construct(
        public BookSearchForm $searchForm,
        public QueryResultInterface $dataProvider
    ) {
    }
}
