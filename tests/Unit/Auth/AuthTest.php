<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\Auth;
use App\Config\Config;
use App\Database\Database;
use App\Database\Tables;
use App\Http\RequestContext;
use App\Http\Session;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет сценарии аутентификации и работы сессии.
 */
final class AuthTest extends TestCase
{
    /**
     * Подготавливает глобальную конфигурацию для хелпера config().
     */
    protected function setUp(): void
    {
        global $config;
        $config = new Config([
            'app' => [
                'user_verification' => false,
            ],
        ]);

        $_SESSION = [];
    }

    /**
     * Проверяет успешный вход по корректным данным.
     */
    public function testAttemptSucceedsWithCorrectCredentials(): void
    {
        $auth = $this->makeAuthWithUser('john@example.com', 'secret');

        self::assertTrue($auth->attempt('john@example.com', 'secret'));
        self::assertTrue($auth->check());
        self::assertSame(1, $auth->id());
    }

    /**
     * Проверяет провал входа при неверном пароле.
     */
    public function testAttemptFailsWithWrongPassword(): void
    {
        $auth = $this->makeAuthWithUser('john@example.com', 'secret');

        self::assertFalse($auth->attempt('john@example.com', 'wrong'));
        self::assertFalse($auth->check());
    }

    /**
     * Проверяет нормализацию email при попытке входа.
     */
    public function testAttemptNormalizesEmailBeforeLookup(): void
    {
        $auth = $this->makeAuthWithUser('john@example.com', 'secret');

        self::assertTrue($auth->attempt('  JOHN@EXAMPLE.COM  ', 'secret'));
    }

    /**
     * Проверяет требование верификации email, если оно включено в конфиге.
     */
    public function testAttemptFailsWhenVerificationRequiredAndUserNotVerified(): void
    {
        global $config;
        $config = new Config([
            'app' => [
                'user_verification' => true,
            ],
        ]);

        $auth = $this->makeAuthWithUser('john@example.com', 'secret', null);

        self::assertFalse($auth->attempt('john@example.com', 'secret'));
        self::assertFalse($auth->check());
    }

    /**
     * Проверяет, что login очищает пароль из данных сессии.
     */
    public function testLoginRemovesPasswordFromSessionUser(): void
    {
        $auth = $this->makeAuthWithUser('john@example.com', 'secret');

        $auth->login([
            'id' => 7,
            'email' => 'john@example.com',
            'password' => 'raw',
        ]);

        $user = $auth->user();

        self::assertIsArray($user);
        self::assertArrayNotHasKey('password', $user);
    }

    /**
     * Проверяет, что logout удаляет пользователя из сессии.
     */
    public function testLogoutClearsAuthentication(): void
    {
        $auth = $this->makeAuthWithUser('john@example.com', 'secret');
        $auth->attempt('john@example.com', 'secret');

        $auth->logout();

        self::assertFalse($auth->check());
        self::assertNull($auth->user());
    }

    /**
     * Проверяет возврат null id, когда пользователь не авторизован.
     */
    public function testIdReturnsNullForGuest(): void
    {
        $auth = $this->makeAuthWithUser('john@example.com', 'secret');

        self::assertNull($auth->id());
    }

    /**
     * Создаёт Auth с in-memory БД и опциональным тестовым пользователем.
     */
    private function makeAuthWithUser(string $email, string $password, ?string $verifiedAt = '2026-01-01 00:00:00'): Auth
    {
        $db = new Database([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'options' => [],
        ], new RequestContext('test-request', microtime(true), true));

        $db->execute('CREATE TABLE ' . Tables::USERS . ' (id INTEGER PRIMARY KEY, email TEXT, password TEXT, email_verified_at TEXT)');

        $db->insert(
            'INSERT INTO ' . Tables::USERS . ' (email, password, email_verified_at) VALUES (?, ?, ?)',
            [$email, password_hash($password, PASSWORD_BCRYPT), $verifiedAt]
        );

        return new Auth(new Session([]), new UserRepository($db));
    }
}
