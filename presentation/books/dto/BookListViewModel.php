<?php

declare(strict_types=1);

namespace app\presentation\books\dto;

use yii\data\DataProviderInterface;

final readonly class BookListViewModel
{
    public function __construct(
        public DataProviderInterface $dataProvider,
    ) {
    }
}
