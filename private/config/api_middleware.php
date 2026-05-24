<?php

declare(strict_types=1);

use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\EnforceHttpsMiddleware;
use App\Http\Middleware\SessionMiddleware;
use App\Http\Middleware\TrustedProxyMiddleware;

/**
 * Глобальный стек middleware для API-маршрутов.
 *
 * Не включает LocaleMiddleware и ShareViewDataMiddleware,
 * так как API не использует шаблонизатор и не нуждается в локали.
 */
return [
    TrustedProxyMiddleware::class,
    EnforceHttpsMiddleware::class,
    CorsMiddleware::class,
    SessionMiddleware::class,
];
