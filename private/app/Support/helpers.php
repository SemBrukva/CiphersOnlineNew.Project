<?php

declare(strict_types=1);

use App\Config\Config;
use App\Container\Container;

if (!function_exists('env')) {
    /**
     * Возвращает значение переменной окружения или значение по умолчанию.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    /**
     * Возвращает значение конфигурации по ключу с поддержкой точечной нотации.
     */
    function config(string $key, mixed $default = null): mixed
    {
        global $config;

        if (!$config instanceof Config) {
            return $default;
        }

        return $config->get($key, $default);
    }
}

if (!function_exists('redirect')) {
    /**
     * Создаёт HTTP-ответ с перенаправлением.
     */
    function redirect(string $url, int $status = 302): App\Http\Response
    {
        return new App\Http\Response('', $status, ['Location' => $url]);
    }
}

if (!function_exists('session')) {
    /**
     * Возвращает экземпляр менеджера сессий из контейнера.
     */
    function session(): App\Http\Session
    {
        return app(App\Http\Session::class);
    }
}

if (!function_exists('auth')) {
    /**
     * Возвращает экземпляр сервиса аутентификации из контейнера.
     */
    function auth(): App\Auth\Auth
    {
        return app(App\Auth\Auth::class);
    }
}

if (!function_exists('validate')) {
    /**
     * Валидирует массив данных по заданным правилам и возвращает его при успехе.
     *
     * @param  array<string, mixed>  $data  Входные данные для проверки.
     * @param  array<string, mixed>  $rules Правила валидации.
     * @return array<string, mixed>
     */
    function validate(array $data, array $rules): array
    {
        $validator = new App\Validation\Validator($data, $rules);
        $validator->validate();

        return $data;
    }
}

if (!function_exists('trans')) {
    /**
     * Возвращает перевод строки по ключу для текущей локали.
     *
     * Поддерживает классический формат «:param» и ICU-формат «{param}».
     *
     * @param array<string, mixed> $replace Параметры для подстановки.
     */
    function trans(string $key, array $replace = []): string
    {
        $translator = app(App\I18n\Translator::class);

        return $translator !== null ? $translator->get($key, $replace) : $key;
    }
}

if (!function_exists('trans_choice')) {
    /**
     * Возвращает перевод в нужной форме множественного числа.
     *
     * Строка перевода должна содержать формы через «|»:
     *   «:count item|:count items»               (en)
     *   «:count элемент|:count элемента|:count элементов» (ru)
     *
     * @param int|float            $count   Число для определения формы.
     * @param array<string, mixed> $replace Дополнительные параметры.
     */
    function trans_choice(string $key, int|float $count, array $replace = []): string
    {
        $translator = app(App\I18n\Translator::class);

        return $translator !== null ? $translator->choice($key, $count, $replace) : $key;
    }
}

if (!function_exists('locale')) {
    /**
     * Возвращает текущую активную локаль.
     */
    function locale(): string
    {
        $translator = app(App\I18n\Translator::class);

        return $translator !== null ? $translator->getLocale() : (string) config('locale.locale');
    }
}

if (!function_exists('locale_url')) {
    /**
     * Возвращает URL с префиксом текущей локали.
     * Для локали по умолчанию возвращает путь без изменений.
     */
    function locale_url(string $path): string
    {
        $translator = app(App\I18n\Translator::class);

        if ($translator === null || !$translator->isMultilang()) {
            return $path;
        }

        $locale        = $translator->getLocale();
        $defaultLocale = $translator->getDefaultLocale();

        return ($locale !== $defaultLocale ? '/' . $locale : '') . $path;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Возвращает CSRF-токен текущей сессии.
     */
    function csrf_token(): string
    {
        return session()->csrfToken();
    }
}

if (!function_exists('cache')) {
    /**
     * Возвращает экземпляр кеша из контейнера.
     */
    function cache(): App\Cache\CacheInterface
    {
        return app(App\Cache\CacheInterface::class);
    }
}

if (!function_exists('route_url')) {
    /**
     * Строит URL по имени маршрута и параметрам.
     *
     * @param array<string, scalar> $params
     */
    function route_url(string $name, array $params = []): string
    {
        /** @var App\Http\UrlGenerator $generator */
        $generator = app(App\Http\UrlGenerator::class);

        return $generator->url($name, $params);
    }
}

if (!function_exists('app')) {
    /**
     * Возвращает сервис из контейнера по идентификатору или сам контейнер, если $id не указан.
     */
    function app(?string $id = null): mixed
    {
        global $container;

        if (!$container instanceof Container) {
            return null;
        }

        if ($id === null) {
            return $container;
        }

        return $container->get($id);
    }
}

if (!function_exists('http_client')) {
    /**
     * Возвращает экземпляр HTTP-клиента из контейнера.
     */
    function http_client(): App\Http\Client\HttpClientInterface
    {
        return app(App\Http\Client\HttpClientInterface::class);
    }
}
