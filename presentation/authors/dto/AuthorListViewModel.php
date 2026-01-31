<?php

declare(strict_types=1);

namespace app\presentation\authors\dto;

use yii\data\DataProviderInterface;

final readonly class AuthorListViewModel
{
    public function __construct(
        public DataProviderInterface $dataProvider,
    ) {
    }
}
