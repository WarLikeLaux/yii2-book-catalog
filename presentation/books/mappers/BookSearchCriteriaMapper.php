<?php

declare(strict_types=1);

namespace app\presentation\books\mappers;

use app\application\books\queries\BookSearchCriteria;
use app\presentation\books\forms\BookSearchForm;

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

    /**
     * @param array<string, mixed> $params
     */
    public function toForm(array $params): BookSearchForm
    {
        $form = new BookSearchForm();
        $form->load($params);
        return $form;
    }
}
