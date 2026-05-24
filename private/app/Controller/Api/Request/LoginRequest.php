<?php

declare(strict_types=1);

namespace App\Controller\Api\Request;

use App\Http\FormRequest;

/**
 * DTO запроса авторизации пользователя.
 */
final class LoginRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected static function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:1',
        ];
    }

    /**
     * Возвращает нормализованный email.
     */
    public function email(): string
    {
        return mb_strtolower(trim((string) $this->value('email', '')));
    }

    /**
     * Возвращает пароль в открытом виде.
     */
    public function password(): string
    {
        return (string) $this->value('password', '');
    }
}
