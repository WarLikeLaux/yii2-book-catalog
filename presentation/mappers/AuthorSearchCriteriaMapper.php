<?php

declare(strict_types=1);

namespace app\presentation\mappers;

use app\application\authors\queries\AuthorSearchCriteria;
use app\models\forms\AuthorSearchForm;

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

    public function toForm(array $params): AuthorSearchForm
    {
        $form = new AuthorSearchForm();
        $form->load($params);
        return $form;
    }
}
