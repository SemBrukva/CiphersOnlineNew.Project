<?php

declare(strict_types=1);

// Глобальный стек middleware для веб-маршрутов; выполняется для каждого запроса в указанном порядке.

use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\EnforceHttpsMiddleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\RedirectMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SessionMiddleware;
use App\Http\Middleware\ShareViewDataMiddleware;
use App\Http\Middleware\TrailingSlashMiddleware;
use App\Http\Middleware\TrustedProxyMiddleware;

return [
    TrustedProxyMiddleware::class,
    EnforceHttpsMiddleware::class,
    TrailingSlashMiddleware::class,
    SecurityHeadersMiddleware::class,
    SessionMiddleware::class,
    RedirectMiddleware::class,
    CsrfMiddleware::class,
    LocaleMiddleware::class,
    ShareViewDataMiddleware::class,
];
