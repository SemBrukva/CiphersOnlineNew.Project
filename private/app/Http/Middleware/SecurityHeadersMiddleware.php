<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\CspNonce;
use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware установки базовых security-заголовков для веб-ответов.
 *
 * Добавляет заголовки, снижающие риск XSS, MIME sniffing, clickjacking
 * и утечек referer-данных. В prod-окружении script-src ужесточается
 * до nonce-only (без unsafe-inline/unsafe-eval).
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт экземпляр middleware.
     */
    public function __construct(private readonly CspNonce $cspNonce)
    {
    }

    /**
     * Формирует Content-Security-Policy с учётом окружения.
     *
     * В dev/local добавляет unsafe-inline и unsafe-eval для совместимости
     * с Vite HMR. В prod использует строгий nonce-only для script-src.
     * Домены внешних трекеров добавляются динамически по конфигурации.
     */
    private function buildCspPolicy(): string
    {
        $nonce      = $this->cspNonce->get();
        $nonceSrc   = "'nonce-{$nonce}'";

        $env        = (string) config('app.env', 'production');
        $isLocalEnv = in_array($env, ['local', 'dev'], true);

        $fontSrc    = ["'self'", 'data:', 'https://fonts.gstatic.com'];
        $connectSrc = ["'self'", 'ws:', 'wss:'];
        $frameSrc   = ["'self'"];

        // style-src: unsafe-inline нужен Bootstrap JS (inline style=".." атрибуты)
        $styleSrc = ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'];

        if ($isLocalEnv) {
            // В dev-режиме Vite HMR вставляет динамические скрипты без nonce
            $scriptSrc = ["'self'", $nonceSrc, "'unsafe-inline'", "'unsafe-eval'"];

            $viteHosts = [
                'http://localhost:5173',
                'http://127.0.0.1:5173',
                'ws://localhost:5173',
                'ws://127.0.0.1:5173',
            ];

            $scriptSrc  = array_merge($scriptSrc, $viteHosts);
            $styleSrc   = array_merge($styleSrc, ['http://localhost:5173', 'http://127.0.0.1:5173']);
            $fontSrc    = array_merge($fontSrc, ['http://localhost:5173', 'http://127.0.0.1:5173']);
            $connectSrc = array_merge($connectSrc, $viteHosts);
        } else {
            $scriptSrc = ["'self'", $nonceSrc];
        }

        if ((string) config('tracking.google.analytics_id', '') !== '') {
            $scriptSrc  = array_merge($scriptSrc, ['https://www.googletagmanager.com']);
            $connectSrc = array_merge($connectSrc, [
                'https://www.google-analytics.com',
                'https://analytics.google.com',
                'https://www.googletagmanager.com',
                'https://region1.google-analytics.com',
            ]);
        }

        if ((string) config('tracking.google.adsense_client_id', '') !== '') {
            $scriptSrc  = array_merge($scriptSrc, [
                'https://pagead2.googlesyndication.com',
                'https://partner.googleadservices.com',
                'https://www.googletagservices.com',
            ]);
            $connectSrc = array_merge($connectSrc, ['https://pagead2.googlesyndication.com']);
            $frameSrc   = array_merge($frameSrc, [
                'https://googleads.g.doubleclick.net',
                'https://tpc.googlesyndication.com',
                'https://ep2.adtrafficquality.google',
                'https://www.google.com',
            ]);
        }

        if ((string) config('tracking.yandex.metrica_id', '') !== '') {
            $scriptSrc  = array_merge($scriptSrc, ['https://mc.yandex.ru', 'https://mc.yandex.com']);
            $connectSrc = array_merge($connectSrc, ['https://mc.yandex.ru', 'https://mc.yandex.com']);
        }

        return sprintf(
            "default-src 'self'; script-src %s; style-src %s; img-src 'self' data: https:; font-src %s; connect-src %s; frame-src %s;",
            implode(' ', array_unique($scriptSrc)),
            implode(' ', array_unique($styleSrc)),
            implode(' ', array_unique($fontSrc)),
            implode(' ', array_unique($connectSrc)),
            implode(' ', array_unique($frameSrc))
        );
    }

    /**
     * Устанавливает security-заголовки и передаёт запрос дальше по цепочке.
     */
    public function process(Request $request, callable $next): Response
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: ' . $this->buildCspPolicy());

        return $next($request);
    }
}
