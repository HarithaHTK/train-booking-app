<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'Train Booking API', version: '1.0.0', description: 'OpenAPI documentation for the Train Booking backend')]
#[OA\Server(url: '/api', description: 'Local API base path')]
#[OA\SecurityScheme(securityScheme: 'bearerAuth', type: 'http', scheme: 'bearer', bearerFormat: 'JWT')]
class OpenApiInfo
{
    // This file only contains OpenAPI annotations for swagger-php to consume.
}
