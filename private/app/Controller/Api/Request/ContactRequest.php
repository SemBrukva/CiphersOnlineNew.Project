<?php

declare(strict_types=1);

namespace App\Controller\Api\Request;

use App\Http\FormRequest;

/**
 * DTO запроса формы контактов.
 */
final class ContactRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected static function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'message' => 'required|string|max:10000',
        ];
    }

    /**
     * Возвращает имя отправителя.
     */
    public function name(): string
    {
        return trim((string) $this->value('name', ''));
    }

    /**
     * Возвращает email отправителя.
     */
    public function email(): string
    {
        return mb_strtolower(trim((string) $this->value('email', '')));
    }

    /**
     * Возвращает текст сообщения.
     */
    public function message(): string
    {
        return trim((string) $this->value('message', ''));
    }
}
