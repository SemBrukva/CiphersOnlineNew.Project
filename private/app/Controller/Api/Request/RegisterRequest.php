<?php

declare(strict_types=1);

namespace App\Controller\Api\Request;

use App\Http\FormRequest;
use App\Validation\ValidationException;

/**
 * DTO запроса регистрации пользователя.
 */
final class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected static function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255',
            'password_confirmation' => 'required|string|min:8|max:255',
            'language' => 'string|min:2|max:10',
            'policy_agreement' => 'required|boolean',
        ];
    }

    /**
     * @throws ValidationException
     */
    public static function fromRequest(\App\Http\Request $request): static
    {
        $instance = parent::fromRequest($request);

        if ($instance->password() !== $instance->passwordConfirmation()) {
            throw new ValidationException([
                'password_confirmation' => ['The password confirmation does not match.'],
            ]);
        }

        if (!$instance->policyAgreement()) {
            throw new ValidationException([
                'policy_agreement' => ['You must agree to the privacy policy and terms of service.'],
            ]);
        }

        return $instance;
    }

    /**
     * Возвращает имя пользователя.
     */
    public function name(): string
    {
        return trim((string) $this->value('name', ''));
    }

    /**
     * Возвращает нормализованный email.
     */
    public function email(): string
    {
        return mb_strtolower(trim((string) $this->value('email', '')));
    }

    /**
     * Возвращает пароль.
     */
    public function password(): string
    {
        return (string) $this->value('password', '');
    }

    /**
     * Возвращает подтверждение пароля.
     */
    public function passwordConfirmation(): string
    {
        return (string) $this->value('password_confirmation', '');
    }

    /**
     * Возвращает язык, переданный клиентом.
     */
    public function language(): ?string
    {
        $value = trim((string) $this->value('language', ''));

        return $value === '' ? null : $value;
    }

    /**
     * Возвращает согласие с политикой конфиденциальности и условиями использования.
     */
    public function policyAgreement(): bool
    {
        return filter_var($this->value('policy_agreement', false), FILTER_VALIDATE_BOOL);
    }
}
