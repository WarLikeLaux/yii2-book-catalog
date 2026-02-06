<?php

declare(strict_types=1);

namespace app\presentation\common\dto;

use app\application\common\dto\PaginationRequest;
use yii\web\Request;

final class CrudPaginationRequest
{
    private const int DEFAULT_LIMIT = 20;

    public static function fromRequest(Request $request): PaginationRequest
    {
        return new PaginationRequest(
            $request->get('page'),
            $request->get('limit'),
            self::DEFAULT_LIMIT,
        );
    }
}
