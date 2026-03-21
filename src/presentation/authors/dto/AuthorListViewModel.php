<?php

declare(strict_types=1);

namespace app\presentation\authors\dto;

use app\presentation\authors\forms\AuthorFilterForm;
use app\presentation\common\ViewModelInterface;
use yii\data\DataProviderInterface;

final readonly class AuthorListViewModel implements ViewModelInterface
{
    public function __construct(
        public DataProviderInterface $dataProvider,
        public AuthorFilterForm $filterModel,
    ) {
    }
}
