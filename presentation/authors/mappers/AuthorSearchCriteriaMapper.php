<?php

declare(strict_types=1);

namespace app\presentation\authors\mappers;

use app\application\authors\queries\AuthorSearchCriteria;
use app\presentation\authors\forms\AuthorSearchForm;

final class AuthorSearchCriteriaMapper
{
    public function toCriteria(AuthorSearchForm $form): AuthorSearchCriteria
    {
        return new AuthorSearchCriteria(
            search: $form->q,
            page: $form->page,
            pageSize: $form->pageSize
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    public function toForm(array $params): AuthorSearchForm
    {
        $form = new AuthorSearchForm();
        $form->load($params);
        return $form;
    }
}
