<?php

declare(strict_types=1);

// Конфигурация локализации: активная локаль, список поддерживаемых языков и путь к переводам.

$defaultLocale = (string) env('APP_LOCALE', 'en');
$rawLocales = (string) env('APP_LANGUAGES', $defaultLocale);
$locales = array_values(array_unique(array_filter(array_map(
    static fn (string $locale): string => trim($locale),
    explode(',', $rawLocales)
), static fn (string $locale): bool => $locale !== '')));

if ($locales === []) {
    $locales = [$defaultLocale];
}

return [
    'multilang' => filter_var(env('APP_MULTILANG', false), FILTER_VALIDATE_BOOL),
    'locale'    => $defaultLocale,
    'locales'   => $locales,
    'path'      => PRIVATE_PATH . '/translates',
];
