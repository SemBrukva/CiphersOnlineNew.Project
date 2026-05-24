<?php

declare(strict_types=1);

namespace App\Event\Events;

/**
 * Событие успешной регистрации пользователя.
 */
final readonly class UserRegistered
{
    /**
     * @param array<string, mixed> $user Данные зарегистрированного пользователя.
     */
    public function __construct(
        public array $user,
        public bool $verificationRequired
    ) {
    }
}
