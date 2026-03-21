<?php

declare(strict_types=1);

namespace app\application\common\dto;

enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';

    public function toSortOrder(): int
    {
        return match ($this) {
            self::ASC => SORT_ASC,
            self::DESC => SORT_DESC,
        };
    }
}
