<?php

declare(strict_types=1);

namespace app\presentation\common\dto;

use app\application\common\dto\PaginationRequest;
use yii\web\Request;

final readonly class CrudPaginationRequest extends PaginationRequest
{
    protected const int DEFAULT_LIMIT = 20;

    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->get('page'),
            $request->get('pageSize'),
        );
    }
}
