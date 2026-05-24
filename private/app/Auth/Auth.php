<?php

declare(strict_types=1);

namespace App\Auth;

use App\Http\Session;
use App\Repository\UserRepository;

/**
 * Сервис аутентификации пользователей.
 *
 * Управляет входом, выходом и хранением данных
 * аутентифицированного пользователя в сессии.
 */
final class Auth
{
    /** @var string Ключ в сессии для хранения данных пользователя. */
    private const string SESSION_KEY = '_auth_user';

    /**
     * Создаёт экземпляр сервиса аутентификации.
     */
    public function __construct(
        private readonly Session  $session,
        private readonly UserRepository $users
    ) {
    }

    /**
     * Пытается аутентифицировать пользователя по email и паролю.
     *
     * @param  string $email    Email пользователя.
     * @param  string $password Пароль в открытом виде.
     * @return bool             true — вход выполнен, false — неверные учётные данные.
     */
    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password'])) {
            return false;
        }

        if ((bool) config('app.user_verification', false) && empty($user['email_verified_at'])) {
            return false;
        }

        $this->login($user);

        return true;
    }

    /**
     * Выполняет вход для переданного пользователя.
     * Перегенерирует ID сессии для защиты от фиксации сессии.
     *
     * @param array<string, mixed> $user Массив данных пользователя из базы.
     */
    public function login(array $user): void
    {
        $this->session->regenerate();

        unset($user['password']);

        $this->session->set(self::SESSION_KEY, $user);
    }

    /**
     * Завершает сессию текущего пользователя.
     */
    public function logout(): void
    {
        $this->session->remove(self::SESSION_KEY);
        $this->session->regenerate();
    }

    /**
     * Проверяет, аутентифицирован ли текущий пользователь.
     */
    public function check(): bool
    {
        return $this->session->has(self::SESSION_KEY);
    }

    /**
     * Возвращает данные аутентифицированного пользователя или null.
     *
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        return $this->session->get(self::SESSION_KEY);
    }

    /**
     * Возвращает ID аутентифицированного пользователя или null.
     */
    public function id(): ?int
    {
        $user = $this->user();

        return $user !== null ? (int) $user['id'] : null;
    }
}
