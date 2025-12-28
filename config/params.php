<?php

declare(strict_types=1);

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'bsVersion' => '5',
    'appPort' => (int)env('APP_PORT', 8000),
    'swaggerPort' => (int)env('SWAGGER_PORT', 8081),
    'buggregatorUiPort' => (int)env('BUGGREGATOR_UI_PORT', 8090),
];
