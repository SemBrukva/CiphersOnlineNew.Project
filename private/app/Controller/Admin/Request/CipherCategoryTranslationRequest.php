<?php

declare(strict_types=1);

namespace App\Controller\Admin\Request;

use App\Http\FormRequest;
use App\Http\Request;

/**
 * DTO запроса создания/обновления перевода категории шифров.
 */
final class CipherCategoryTranslationRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected static function rules(): array
    {
        return [
            'category_id' => 'required|integer|min:1',
            'language' => 'required|string|min:2|max:8',
            'name' => 'required|string|min:1|max:255',
            'description' => 'string|max:65000',
            'meta_title' => 'string|max:255',
            'meta_description' => 'string|max:65000',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function extractData(Request $request): array
    {
        $data = $request->allInput();

        $data['category_id'] = (int) ($data['category_id'] ?? 0);
        $data['language'] = trim((string) ($data['language'] ?? ''));
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['description'] = trim((string) ($data['description'] ?? ''));
        $data['meta_title'] = trim((string) ($data['meta_title'] ?? ''));
        $data['meta_description'] = trim((string) ($data['meta_description'] ?? ''));

        return $data;
    }

    /**
     * Возвращает ID категории.
     */
    public function categoryId(): int
    {
        return (int) $this->value('category_id', 0);
    }

    /**
     * Возвращает код языка перевода.
     */
    public function language(): string
    {
        return mb_strtolower((string) $this->value('language', ''));
    }

    /**
     * Возвращает заголовок категории.
     */
    public function name(): string
    {
        return (string) $this->value('name', '');
    }

    /**
     * Возвращает описание категории или null.
     */
    public function description(): ?string
    {
        $value = (string) $this->value('description', '');

        return $value === '' ? null : $value;
    }

    /**
     * Возвращает meta title.
     */
    public function metaTitle(): string
    {
        return (string) $this->value('meta_title', '');
    }

    /**
     * Возвращает meta description или null.
     */
    public function metaDescription(): ?string
    {
        $value = (string) $this->value('meta_description', '');

        return $value === '' ? null : $value;
    }

    /**
     * Проверяет бизнес-ограничения для языкового кода.
     */
    public function validateBusinessRules(): ?string
    {
        if (!preg_match('/^[a-z]{2,3}(?:-[a-z]{2})?$/', $this->language())) {
            return 'Поле «Язык» должно быть в формате ru, en или pt-br.';
        }

        return null;
    }
}
