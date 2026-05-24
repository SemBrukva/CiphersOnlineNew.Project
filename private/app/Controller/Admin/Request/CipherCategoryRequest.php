<?php

declare(strict_types=1);

namespace App\Controller\Admin\Request;

use App\Http\FormRequest;
use App\Http\Request;

/**
 * DTO запроса создания/обновления категории шифров.
 */
final class CipherCategoryRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected static function rules(): array
    {
        return [
            'alias' => 'required|string|min:2|max:100',
            'sort_order' => 'required|integer|min:0|max:999999',
            'published' => 'boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function extractData(Request $request): array
    {
        $data = $request->allInput();

        $data['alias'] = trim((string) ($data['alias'] ?? ''));
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['published'] = $request->input('published') !== null;

        return $data;
    }

    /**
     * Возвращает alias категории.
     */
    public function alias(): string
    {
        return mb_strtolower((string) $this->value('alias', ''));
    }

    /**
     * Возвращает порядок сортировки категории.
     */
    public function sortOrder(): int
    {
        return (int) $this->value('sort_order', 0);
    }

    /**
     * Возвращает флаг публикации категории.
     */
    public function published(): int
    {
        return (bool) $this->value('published', false) ? 1 : 0;
    }

    /**
     * Проверяет бизнес-ограничения для alias.
     */
    public function validateBusinessRules(): ?string
    {
        if (!preg_match('/^[a-z0-9-]+$/', $this->alias())) {
            return 'Alias может содержать только латиницу в нижнем регистре, цифры и дефис.';
        }

        return null;
    }
}
