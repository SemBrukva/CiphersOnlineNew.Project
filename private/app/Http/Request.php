<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;

/**
 * Представляет входящий HTTP-запрос.
 *
 * Инкапсулирует суперглобальные переменные PHP ($_SERVER, $_GET, $_POST и др.)
 * и предоставляет типобезопасный доступ к их данным.
 */
final readonly class Request
{
    /**
     * Создаёт экземпляр запроса.
     *
     * @param array<string, mixed>  $server          Данные из $_SERVER.
     * @param array<string, mixed>  $query            Данные из $_GET.
     * @param array<string, mixed>  $request          Данные из $_POST.
     * @param array<string, string> $cookies          Данные из $_COOKIE.
     * @param array<string, mixed>  $files            Данные из $_FILES.
     * @param array<string, string> $routeParams      Параметры, извлечённые из URI роутером.
     * @param string|null           $resolvedIp       IP клиента, разрешённый TrustedProxyMiddleware.
     * @param string|null           $resolvedScheme   Схема (http/https), разрешённая TrustedProxyMiddleware.
     * @param string|null           $resolvedHost     Хост, разрешённый TrustedProxyMiddleware.
     */
    public function __construct(
        private array $server,
        private array $query,
        private array $request,
        private array $cookies,
        private array $files,
        private array $routeParams = [],
        private ?string $resolvedIp = null,
        private ?string $resolvedScheme = null,
        private ?string $resolvedHost = null,
    ) {
    }

    /**
     * Создаёт экземпляр запроса из суперглобальных переменных PHP.
     */
    public static function capture(): self
    {
        return new self(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );
    }

    /**
     * Возвращает HTTP-метод запроса в верхнем регистре.
     */
    public function getMethod(): string
    {
        return strtoupper(
            $this->server['REQUEST_METHOD'] ?? 'GET'
        );
    }

    /**
     * Возвращает полный URI запроса, включая строку запроса.
     */
    public function getUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * Возвращает параметр строки запроса ($_GET) по ключу.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Возвращает поле тела запроса ($_POST) по ключу.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $default;
    }

    /**
     * Возвращает значение cookie по ключу.
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Возвращает данные загруженного файла из $_FILES по ключу.
     */
    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Возвращает значение HTTP-заголовка запроса по имени.
     */
    public function header(string $name, mixed $default = null): mixed
    {
        $header = 'HTTP_' . strtoupper(
            str_replace('-', '_', $name)
        );

        return $this->server[$header] ?? $default;
    }

    /**
     * Возвращает все параметры строки запроса ($_GET).
     *
     * @return array<string, mixed>
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    /**
     * Возвращает все поля тела запроса ($_POST).
     *
     * @return array<string, mixed>
     */
    public function allInput(): array
    {
        return $this->request;
    }

    /**
     * Возвращает параметр маршрута, извлечённый роутером из URI.
     */
    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Проверяет, отправлен ли запрос с Content-Type: application/json.
     */
    public function isJson(): bool
    {
        $contentType = $this->server['CONTENT_TYPE'] ?? '';

        return str_contains($contentType, 'application/json');
    }

    /**
     * Декодирует тело запроса как JSON и возвращает результат.
     * Возвращает null, если тело пустое или не является валидным JSON.
     *
     * @return mixed
     */
    public function json(): mixed
    {
        $body = file_get_contents('php://input');

        if ($body === false || $body === '') {
            return null;
        }

        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * Возвращает путь запроса без строки запроса.
     */
    public function path(): string
    {
        $uri = $this->getUri();
        $pos = strpos($uri, '?');

        return $pos !== false ? substr($uri, 0, $pos) : $uri;
    }

    /**
     * Возвращает IP-адрес прямого соединения (REMOTE_ADDR), не учитывая прокси-заголовки.
     *
     * Используется TrustedProxyMiddleware для проверки, является ли подключившийся доверенным прокси.
     */
    public function remoteAddr(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '');
    }

    /**
     * Возвращает IP-адрес клиента.
     *
     * Если TrustedProxyMiddleware разрешил реальный IP из заголовков доверенного прокси —
     * возвращает его; иначе возвращает REMOTE_ADDR напрямую, без blind-trust proxy-заголовков.
     */
    public function ip(): string
    {
        return $this->resolvedIp ?? (string) ($this->server['REMOTE_ADDR'] ?? '');
    }

    /**
     * Проверяет, был ли запрос выполнен по HTTPS.
     *
     * Если TrustedProxyMiddleware разрешил схему из X-Forwarded-Proto доверенного прокси —
     * использует её; иначе проверяет исходные серверные переменные.
     */
    public function isSecure(): bool
    {
        if ($this->resolvedScheme !== null) {
            return $this->resolvedScheme === 'https';
        }

        $scheme = strtolower((string) ($this->server['REQUEST_SCHEME'] ?? ''));
        if ($scheme === 'https') {
            return true;
        }

        $https = (string) ($this->server['HTTPS'] ?? '');

        return strtolower($https) === 'on' || $https === '1';
    }

    /**
     * Возвращает хост запроса.
     *
     * Если TrustedProxyMiddleware разрешил хост из X-Forwarded-Host доверенного прокси —
     * возвращает его; иначе возвращает HTTP_HOST.
     */
    public function host(): string
    {
        return $this->resolvedHost ?? (string) ($this->server['HTTP_HOST'] ?? '');
    }

    /**
     * Возвращает новый экземпляр запроса с данными, разрешёнными TrustedProxyMiddleware.
     *
     * @param string|null $ip     Реальный IP клиента из X-Forwarded-For / X-Real-IP.
     * @param string|null $scheme Схема из X-Forwarded-Proto ('http' или 'https').
     * @param string|null $host   Хост из X-Forwarded-Host.
     */
    public function withTrustedData(?string $ip, ?string $scheme, ?string $host): static
    {
        return new static(
            $this->server,
            $this->query,
            $this->request,
            $this->cookies,
            $this->files,
            $this->routeParams,
            $ip ?? $this->resolvedIp,
            $scheme ?? $this->resolvedScheme,
            $host ?? $this->resolvedHost,
        );
    }

    /**
     * Возвращает все HTTP-заголовки запроса в виде ассоциативного массива «Имя → значение».
     *
     * @return array<string, string>
     */
    public function allHeaders(): array
    {
        $headers = [];

        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name           = ucwords(strtolower(str_replace('_', '-', substr($key, 5))), '-');
                $headers[$name] = (string) $value;
            }
        }

        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = (string) $this->server['CONTENT_TYPE'];
        }

        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = (string) $this->server['CONTENT_LENGTH'];
        }

        return $headers;
    }

    /**
     * Возвращает новый экземпляр запроса с заменённым URI (для срезания префикса локали).
     */
    public function withUri(string $uri): static
    {
        $server                = $this->server;
        $server['REQUEST_URI'] = $uri;

        return new static($server, $this->query, $this->request, $this->cookies, $this->files, $this->routeParams, $this->resolvedIp, $this->resolvedScheme, $this->resolvedHost);
    }

    /**
     * Возвращает новый экземпляр запроса с заданными параметрами маршрута.
     *
     * @param array<string, string> $params Параметры, извлечённые из URI.
     */
    public function withRouteParams(array $params): static
    {
        return new static(
            $this->server,
            $this->query,
            $this->request,
            $this->cookies,
            $this->files,
            $params,
            $this->resolvedIp,
            $this->resolvedScheme,
            $this->resolvedHost,
        );
    }
}
