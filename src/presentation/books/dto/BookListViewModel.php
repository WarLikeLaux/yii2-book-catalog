<?php

declare(strict_types=1);

namespace app\presentation\books\dto;

use app\presentation\common\ViewModelInterface;
use yii\data\DataProviderInterface;

final readonly class BookListViewModel implements ViewModelInterface
{
    public function __construct(
        public DataProviderInterface $dataProvider,
    ) {
    }
}
