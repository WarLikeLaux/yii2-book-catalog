<?php

declare(strict_types=1);

namespace app\presentation\mappers;

use app\application\books\queries\BookSearchCriteria;
use app\models\forms\BookSearchForm;

final class BookSearchCriteriaMapper
{
    public function toCriteria(BookSearchForm $form, int $page = 1, int $pageSize = 20): BookSearchCriteria
    {
        return new BookSearchCriteria(
            globalSearch: $form->globalSearch,
            page: $page,
            pageSize: $pageSize
        );
    }

    public function toForm(array $params): BookSearchForm
    {
        $form = new BookSearchForm();
        $form->load($params);
        return $form;
    }
}
