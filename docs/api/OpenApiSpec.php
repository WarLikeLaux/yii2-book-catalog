<?php

declare(strict_types=1);

namespace app\docs\api;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'Yii2 Book Catalog API', version: '1.0.0', description: 'Modern Yii2 Book Catalog API Documentation')]
#[OA\Server(url: 'http://localhost:8000', description: 'Local Development Server')]
class OpenApiSpec
{
}
