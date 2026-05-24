<?php

declare(strict_types=1);

namespace App\View;

/**
 * Хелпер для генерации HTML-тегов ассетов, собранных Vite.
 *
 * В dev-режиме (наличие public/build/hot) проксирует запросы на Vite dev-сервер.
 * В prod-режиме читает public/build/.vite/manifest.json и подставляет хешированные пути.
 */
final class ViteAssets
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $manifest = null;

    /**
     * Возвращает HTML-теги для указанного entry-point.
     *
     * @param  string      $entry Путь к entry-point относительно корня проекта (например, 'private/resources/js/app.js').
     * @param  string      $type  Какие теги выводить: 'css', 'js' или 'all'.
     * @param  string|null $nonce CSP nonce для атрибута nonce="" тегов <script>.
     * @return string             Готовые HTML-теги.
     */
    public static function tags(string $entry, string $type = 'all', ?string $nonce = null): string
    {
        if (self::isHot()) {
            return self::devTags($entry, $type, $nonce);
        }

        return self::prodTags($entry, $type, $nonce);
    }

    private static function devTags(string $entry, string $type, ?string $nonce): string
    {
        if ($type === 'css') {
            return '';
        }

        $url        = rtrim((string) file_get_contents(self::hotFilePath()), "\n");
        $nonceAttr  = $nonce !== null ? sprintf(' nonce="%s"', htmlspecialchars($nonce, ENT_QUOTES)) : '';

        return implode("\n", [
            sprintf('<script type="module"%s src="%s/@vite/client"></script>', $nonceAttr, $url),
            sprintf('<script type="module"%s src="%s/%s"></script>', $nonceAttr, $url, $entry),
        ]);
    }

    private static function prodTags(string $entry, string $type, ?string $nonce): string
    {
        $manifest = self::manifest();
        $chunk    = $manifest[$entry] ?? null;

        if ($chunk === null) {
            return sprintf('<!-- vite: entry "%s" not found in manifest -->', $entry);
        }

        $nonceAttr = $nonce !== null ? sprintf(' nonce="%s"', htmlspecialchars($nonce, ENT_QUOTES)) : '';
        $tags      = [];

        if ($type !== 'js') {
            foreach ($chunk['css'] ?? [] as $cssFile) {
                $tags[] = sprintf('<link rel="stylesheet" href="/build/%s">', $cssFile);
            }
        }

        if ($type !== 'css') {
            // Preload shared chunks чтобы браузер не ждал waterfall-загрузки
            foreach ($chunk['imports'] ?? [] as $importKey) {
                $importChunk = $manifest[$importKey] ?? null;
                if ($importChunk !== null) {
                    $tags[] = sprintf('<link rel="modulepreload"%s href="/build/%s">', $nonceAttr, $importChunk['file']);
                }
            }
            $tags[] = sprintf('<script type="module"%s src="/build/%s"></script>', $nonceAttr, $chunk['file']);
        }

        return implode("\n    ", $tags);
    }

    private static function isHot(): bool
    {
        return file_exists(self::hotFilePath());
    }

    private static function hotFilePath(): string
    {
        return self::root() . '/public/build/hot';
    }

    /**
     * @throws \RuntimeException Если manifest.json не найден (забыли запустить `npm run build`).
     *
     * @return array<string, array<string, mixed>>
     */
    private static function manifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $path = self::root() . '/public/build/.vite/manifest.json';

        if (!file_exists($path)) {
            throw new \RuntimeException(
                'Vite manifest not found. Run `npm run build` or `make build`.'
            );
        }

        self::$manifest = (array) json_decode((string) file_get_contents($path), true);

        return self::$manifest;
    }

    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
