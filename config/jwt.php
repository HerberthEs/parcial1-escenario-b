<?php

return [
    // Secreto HS256. Se define SOLO en .env (ver .env.example). Generar: php artisan jwt:secret
    'secret' => env('JWT_SECRET'),
    'ttl_minutos' => (int) env('JWT_TTL_MINUTOS', 120),
    'emisor' => env('JWT_EMISOR', env('APP_NAME', 'api')),
];
