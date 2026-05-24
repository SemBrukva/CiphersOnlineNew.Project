<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Cache\CacheInterface;
use App\Database\Database;
use App\Http\Attribute\ApiOperation;
use App\Http\Attribute\ApiResponse;
use App\Http\Exception\NotFoundException;
use App\Http\Exception\ValidationFailedException;
use App\Http\Request;
use App\Http\Response;
use App\Repository\CipherCategoryRepository;
use App\Repository\CipherCategoryTranslationRepository;
use App\Repository\UserRepository;

/**
 * API-эндпоинты для администраторов.
 *
 * Защищены ApiAdminMiddleware — не-администраторы получают 401 или 403.
 */
final class AdminController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private readonly UserRepository $users,
        private readonly CipherCategoryRepository $categories,
        private readonly CipherCategoryTranslationRepository $translations,
        private readonly Database $db,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Возвращает базовую статистику приложения.
     *
     * GET /api/admin/stats
     */
    #[ApiOperation(summary: 'Статистика приложения', tags: ['admin'])]
    #[ApiResponse(status: 200, description: 'Статистика', schema: ['type' => 'object', 'properties' => ['users_count' => ['type' => 'integer']]])]
    #[ApiResponse(status: 401, description: 'Требуется авторизация')]
    #[ApiResponse(status: 403, description: 'Недостаточно прав')]
    public function stats(Request $request): Response
    {
        $usersCount = $this->users->countAll();

        return Response::json([
            'users_count' => $usersCount,
        ]);
    }

    /**
     * Сохраняет основные настройки категории и переводы по всем языкам.
     *
     * POST /api/admin/cipher-categories/{id}
     */
    #[ApiOperation(summary: 'Обновление категории шифров и переводов', tags: ['admin'])]
    #[ApiResponse(status: 200, description: 'Категория сохранена')]
    #[ApiResponse(status: 404, description: 'Категория не найдена')]
    #[ApiResponse(status: 422, description: 'Ошибки валидации')]
    #[ApiResponse(status: 401, description: 'Требуется авторизация')]
    #[ApiResponse(status: 403, description: 'Недостаточно прав')]
    public function updateCipherCategory(Request $request): Response
    {
        $categoryId = (int) $request->route('id');
        $category = $this->categories->find($categoryId);

        if ($category === null) {
            throw new NotFoundException('Категория не найдена.');
        }

        $payload = $request->json();
        if (!is_array($payload)) {
            $payload = [];
        }

        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $translations = is_array($payload['translations'] ?? null) ? $payload['translations'] : [];
        $availableLanguages = array_values(array_filter(array_map(
            static fn (mixed $language): string => mb_strtolower(trim((string) $language)),
            (array) config('locale.locales', [])
        ), static fn (string $language): bool => $language !== ''));

        $alias = mb_strtolower(trim((string) ($settings['alias'] ?? '')));
        $sortOrder = (int) ($settings['sort_order'] ?? 0);
        $published = (bool) ($settings['published'] ?? false) ? 1 : 0;

        $errors = [];

        if ($alias === '' || !preg_match('/^[a-z0-9-]{2,100}$/', $alias)) {
            $errors['settings.alias'][] = 'Alias должен содержать 2-100 символов: a-z, 0-9 и дефис.';
        }

        if ($sortOrder < 0 || $sortOrder > 999999) {
            $errors['settings.sort_order'][] = 'Порядок сортировки должен быть от 0 до 999999.';
        }

        if ($this->categories->existsByAlias($alias, $categoryId)) {
            $errors['settings.alias'][] = 'Категория с таким alias уже существует.';
        }

        $normalizedTranslations = [];

        foreach ($availableLanguages as $language) {
            $row = is_array($translations[$language] ?? null) ? $translations[$language] : [];
            $name = trim((string) ($row['name'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));
            $metaTitle = trim((string) ($row['meta_title'] ?? ''));
            $metaDescription = trim((string) ($row['meta_description'] ?? ''));

            $hasAnyValue = $name !== '' || $description !== '' || $metaTitle !== '' || $metaDescription !== '';

            if ($hasAnyValue && $name === '') {
                $errors["translations.{$language}.name"][] = 'Название обязательно, если заполнен перевод.';
            }

            if (mb_strlen($name) > 255) {
                $errors["translations.{$language}.name"][] = 'Название не должно превышать 255 символов.';
            }

            if (mb_strlen($metaTitle) > 255) {
                $errors["translations.{$language}.meta_title"][] = 'Meta title не должен превышать 255 символов.';
            }

            $normalizedTranslations[$language] = [
                'name' => $name,
                'description' => $description,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'has_any_value' => $hasAnyValue,
            ];
        }

        if ($errors !== []) {
            throw new ValidationFailedException('The given data was invalid.', ['errors' => $errors]);
        }

        $this->db->transaction(function () use ($categoryId, $alias, $sortOrder, $published, $normalizedTranslations): void {
            $now = date('Y-m-d H:i:s');

            $this->categories->update($categoryId, [
                'alias' => $alias,
                'sort_order' => $sortOrder,
                'published' => $published,
                'updated_at' => $now,
            ]);

            foreach ($normalizedTranslations as $language => $translation) {
                $existing = $this->translations->findByCategoryAndLanguage($categoryId, $language);

                if (!$translation['has_any_value']) {
                    if ($existing !== null) {
                        $this->translations->deleteByCategoryAndLanguage($categoryId, $language);
                    }

                    continue;
                }

                $payload = [
                    'name' => $translation['name'],
                    'description' => $translation['description'] !== '' ? $translation['description'] : null,
                    'meta_title' => $translation['meta_title'],
                    'meta_description' => $translation['meta_description'] !== '' ? $translation['meta_description'] : null,
                    'updated_at' => $now,
                ];

                if ($existing === null) {
                    $this->translations->insert([
                        'category_id' => $categoryId,
                        'language' => $language,
                        'name' => $payload['name'],
                        'description' => $payload['description'],
                        'meta_title' => $payload['meta_title'],
                        'meta_description' => $payload['meta_description'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $this->translations->update((int) $existing['id'], $payload);
                }
            }
        });
        $this->cache->tag('cipher_categories')->flush();

        return Response::json([
            'ok' => true,
            'message' => 'Категория и переводы сохранены.',
        ]);
    }
}
