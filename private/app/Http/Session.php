<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Менеджер HTTP-сессий.
 *
 * Обёртка над нативными сессиями PHP с поддержкой flash-сообщений.
 * Сессия не стартует автоматически — требует явного вызова start().
 * При передаче $handler сессии хранятся во внешнем хранилище (Memcached, Redis).
 */
final class Session
{
    /** @var bool Признак того, что сессия уже была запущена. */
    private bool $started = false;

    /**
     * Создаёт экземпляр менеджера сессий.
     *
     * @param array<string, mixed>       $config  Конфигурация из config/session.php.
     * @param \SessionHandlerInterface|null $handler Кастомный обработчик хранилища (Memcached, Redis).
     */
    public function __construct(
        private readonly array $config,
        private readonly ?\SessionHandlerInterface $handler = null,
    ) {
    }

    /**
     * Запускает сессию с параметрами из конфигурации.
     * Повторный вызов безопасен — пропускается, если сессия уже активна.
     */
    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        session_name($this->config['name'] ?? 'APP_SESSION');

        if ($this->handler !== null) {
            session_set_save_handler($this->handler, true);
        } elseif (!empty($this->config['save_path'])) {
            session_save_path($this->config['save_path']);
        }

        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'] ?? 0,
            'path'     => $this->config['path']     ?? '/',
            'domain'   => $this->config['domain']   ?? '',
            'secure'   => $this->config['secure']   ?? false,
            'httponly' => $this->config['httponly']  ?? true,
            'samesite' => $this->config['samesite']  ?? 'Lax',
        ]);

        session_start();
        $this->started = true;
    }

    /**
     * Возвращает значение из сессии по ключу.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Сохраняет значение в сессию.
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Проверяет наличие ключа в сессии.
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Удаляет значение из сессии.
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Сохраняет flash-сообщение, которое будет доступно только в следующем запросе.
     */
    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Читает и удаляет flash-сообщение.
     * Возвращает $default, если сообщение не было установлено.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    /**
     * Перегенерирует идентификатор сессии.
     * Вызывается при входе и выходе для защиты от фиксации сессии.
     *
     * @param bool $deleteOld Удалить ли старые данные сессии.
     */
    public function regenerate(bool $deleteOld = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOld);
        }
    }

    /**
     * Возвращает CSRF-токен текущей сессии.
     * Если токен ещё не был создан — генерирует и сохраняет его.
     */
    public function csrfToken(): string
    {
        if (!$this->has('_csrf_token')) {
            $this->set('_csrf_token', bin2hex(random_bytes(32)));
        }

        return $this->get('_csrf_token');
    }

    /**
     * Полностью уничтожает сессию и очищает данные.
     */
    public function destroy(): void
    {
        $_SESSION  = [];
        $this->started = false;
        session_destroy();
    }

    /**
     * Возвращает все данные текущей сессии.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $_SESSION ?? [];
    }
}
