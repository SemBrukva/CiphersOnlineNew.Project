<?php

declare(strict_types=1);

// Конфигурация шаблонизатора Smarty: пути к шаблонам, кэшу и параметры компиляции.

return [
    'views_path' => RESOURCE_PATH . '/views',
    'compile_path' => STORAGE_PATH . '/cache/templates',
    'cache_path' => STORAGE_PATH . '/cache/smarty',
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'caching' => false,
    'force_compile' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'escape_html' => true,
];
