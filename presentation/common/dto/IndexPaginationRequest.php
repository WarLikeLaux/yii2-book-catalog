<?php

declare(strict_types=1);

namespace app\presentation\common\dto;

use app\application\common\dto\PaginationRequest;

final readonly class IndexPaginationRequest extends PaginationRequest
{
    protected const int DEFAULT_LIMIT = 9;
}
