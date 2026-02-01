<?php

declare(strict_types=1);

namespace app\presentation\authors\dto;

use app\presentation\common\ViewModelInterface;
use yii\data\DataProviderInterface;

final readonly class AuthorListViewModel implements ViewModelInterface
{
    public function __construct(
        public DataProviderInterface $dataProvider,
    ) {
    }
}
