<?php

declare(strict_types=1);

namespace app\presentation\common\adapters;

use app\application\ports\PagedResultInterface;
use yii\data\DataProviderInterface;

final readonly class PagedResultDataProviderFactory
{
    public function create(PagedResultInterface $result): DataProviderInterface
    {
        return new PagedResultDataProvider($result);
    }
}
