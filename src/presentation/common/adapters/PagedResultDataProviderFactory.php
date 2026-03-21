<?php

declare(strict_types=1);

namespace app\presentation\common\adapters;

use app\application\ports\PagedResultInterface;
use yii\data\DataProviderInterface;

final readonly class PagedResultDataProviderFactory
{
    /**
     * @param string[] $sortAttributes
     */
    public function create(PagedResultInterface $result, array $sortAttributes = []): DataProviderInterface
    {
        return new PagedResultDataProvider($result, $sortAttributes);
    }
}
