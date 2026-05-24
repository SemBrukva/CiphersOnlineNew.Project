<?php

declare(strict_types=1);

namespace App\Controller\Admin\Request;

use App\Http\FormRequest;
use App\Http\Request;

/**
 * DTO запроса создания/обновления редиректа.
 */
final class RedirectRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected static function rules(): array
    {
        return [
            'from_path' => 'required|string|min:1|max:255',
            'to_path' => 'required|string|min:1|max:255',
            'status_code' => 'required|integer|in:301,302',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function extractData(Request $request): array
    {
        $data = $request->allInput();

        $data['from_path'] = trim((string) ($data['from_path'] ?? ''));
        $data['to_path'] = trim((string) ($data['to_path'] ?? ''));
        $data['status_code'] = (int) ($data['status_code'] ?? 301);
        $data['is_active'] = $request->input('is_active') !== null;

        return $data;
    }

    /**
     * Возвращает путь-источник редиректа.
     */
    public function fromPath(): string
    {
        return (string) $this->value('from_path', '');
    }

    /**
     * Возвращает путь-назначение редиректа.
     */
    public function toPath(): string
    {
        return (string) $this->value('to_path', '');
    }

    /**
     * Возвращает HTTP-код редиректа.
     */
    public function statusCode(): int
    {
        return (int) $this->value('status_code', 301);
    }

    /**
     * Возвращает флаг активности редиректа.
     */
    public function isActive(): int
    {
        return (bool) $this->value('is_active', false) ? 1 : 0;
    }

    /**
     * Проверяет бизнес-правила, которые не покрываются generic Validator.
     */
    public function validateBusinessRules(): ?string
    {
        if (!str_starts_with($this->fromPath(), '/')) {
            return 'Поле «Откуда» должно начинаться с /.';
        }

        return null;
    }
}
