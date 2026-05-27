<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Cache\CacheInterface;
use App\Database\Database;
use App\Database\Tables;
use App\Http\Attribute\ApiOperation;
use App\Http\Attribute\ApiResponse;
use App\Http\Exception\NotFoundException;
use App\Http\Exception\ValidationFailedException;
use App\Http\Request;
use App\Http\Response;
use App\Repository\CipherCategoryRepository;
use App\Repository\CipherCategoryTranslationRepository;
use App\Repository\CipherRepository;
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
        private readonly CipherRepository $ciphers,
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
        $blocks = is_array($payload['blocks'] ?? null) ? $payload['blocks'] : [];
        $tasks = is_array($payload['tasks'] ?? null) ? $payload['tasks'] : [];
        $usedTogether = is_array($payload['used_together'] ?? null) ? $payload['used_together'] : [];
        $faq = is_array($payload['faq'] ?? null) ? $payload['faq'] : [];
        $newBlocks = is_array($payload['new_blocks'] ?? null) ? $payload['new_blocks'] : [];
        $newTasks = is_array($payload['new_tasks'] ?? null) ? $payload['new_tasks'] : [];
        $newUsedTogether = is_array($payload['new_used_together'] ?? null) ? $payload['new_used_together'] : [];
        $newFaq = is_array($payload['new_faq'] ?? null) ? $payload['new_faq'] : [];
        $deleteBlocks = array_map('intval', is_array($payload['delete_blocks'] ?? null) ? $payload['delete_blocks'] : []);
        $deleteTasks = array_map('intval', is_array($payload['delete_tasks'] ?? null) ? $payload['delete_tasks'] : []);
        $deleteUsedTogether = array_map('intval', is_array($payload['delete_used_together'] ?? null) ? $payload['delete_used_together'] : []);
        $deleteFaq = array_map('intval', is_array($payload['delete_faq'] ?? null) ? $payload['delete_faq'] : []);
        $availableLanguages = array_values(array_filter(array_map(
            static fn (mixed $language): string => mb_strtolower(trim((string) $language)),
            (array) config('locale.locales', [])
        ), static fn (string $language): bool => $language !== ''));

        $alias = mb_strtolower(trim((string) ($settings['alias'] ?? '')));
        $categoryType = mb_strtolower(trim((string) ($settings['category'] ?? 'cipher')));
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

        if (!in_array($categoryType, ['cipher', 'encoding'], true)) {
            $errors['settings.category'][] = 'Тип категории должен быть cipher или encoding.';
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

        foreach ($tasks as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $relationCipherId = (int) ($row['relation_cipher_id'] ?? 0);
            if ($relationCipherId < 1 || $this->ciphers->find($relationCipherId) === null) {
                $errors["tasks.{$index}.relation_cipher_id"][] = 'Неверный relation_cipher_id.';
            }

            $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

            foreach ($availableLanguages as $language) {
                $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                $title = trim((string) ($translation['title'] ?? ''));

                if (mb_strlen($title) > 255) {
                    $errors["tasks.{$index}.translations.{$language}.title"][] = 'Title не должен превышать 255 символов.';
                }
            }
        }

        foreach ($newTasks as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $relationCipherId = (int) ($row['relation_cipher_id'] ?? 0);
            if ($relationCipherId < 1 || $this->ciphers->find($relationCipherId) === null) {
                $errors["new_tasks.{$index}.relation_cipher_id"][] = 'Неверный relation_cipher_id.';
            }

            $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

            foreach ($availableLanguages as $language) {
                $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                $title = trim((string) ($translation['title'] ?? ''));

                if (mb_strlen($title) > 255) {
                    $errors["new_tasks.{$index}.translations.{$language}.title"][] = 'Title не должен превышать 255 символов.';
                }
            }
        }

        foreach ($usedTogether as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $firstCipherId = (int) ($row['relation_cipher_first_id'] ?? 0);
            $secondCipherId = (int) ($row['relation_cipher_second_id'] ?? 0);

            if ($firstCipherId < 1 || $this->ciphers->find($firstCipherId) === null) {
                $errors["used_together.{$index}.relation_cipher_first_id"][] = 'Неверный relation_cipher_first_id.';
            }

            if ($secondCipherId < 1 || $this->ciphers->find($secondCipherId) === null) {
                $errors["used_together.{$index}.relation_cipher_second_id"][] = 'Неверный relation_cipher_second_id.';
            }

            $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

            foreach ($availableLanguages as $language) {
                $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                $title = trim((string) ($translation['title'] ?? ''));

                if (mb_strlen($title) > 500) {
                    $errors["used_together.{$index}.translations.{$language}.title"][] = 'Title не должен превышать 500 символов.';
                }
            }
        }

        foreach ($newUsedTogether as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $firstCipherId = (int) ($row['relation_cipher_first_id'] ?? 0);
            $secondCipherId = (int) ($row['relation_cipher_second_id'] ?? 0);

            if ($firstCipherId < 1 || $this->ciphers->find($firstCipherId) === null) {
                $errors["new_used_together.{$index}.relation_cipher_first_id"][] = 'Неверный relation_cipher_first_id.';
            }

            if ($secondCipherId < 1 || $this->ciphers->find($secondCipherId) === null) {
                $errors["new_used_together.{$index}.relation_cipher_second_id"][] = 'Неверный relation_cipher_second_id.';
            }

            $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

            foreach ($availableLanguages as $language) {
                $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                $title = trim((string) ($translation['title'] ?? ''));

                if (mb_strlen($title) > 500) {
                    $errors["new_used_together.{$index}.translations.{$language}.title"][] = 'Title не должен превышать 500 символов.';
                }
            }
        }

        foreach ($faq as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

            foreach ($availableLanguages as $language) {
                $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                $question = trim((string) ($translation['question'] ?? ''));

                if (mb_strlen($question) > 500) {
                    $errors["faq.{$index}.translations.{$language}.question"][] = 'Question не должен превышать 500 символов.';
                }
            }
        }

        foreach ($newFaq as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

            foreach ($availableLanguages as $language) {
                $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                $question = trim((string) ($translation['question'] ?? ''));

                if (mb_strlen($question) > 500) {
                    $errors["new_faq.{$index}.translations.{$language}.question"][] = 'Question не должен превышать 500 символов.';
                }
            }
        }

        if ($errors !== []) {
            throw new ValidationFailedException('The given data was invalid.', ['errors' => $errors]);
        }

        $createdBlocks = [];
        $createdTasks = [];
        $createdUsedTogether = [];
        $createdFaq = [];

        $this->db->transaction(function () use (
            $categoryId,
            $alias,
            $categoryType,
            $sortOrder,
            $published,
            $normalizedTranslations,
            $blocks,
            $tasks,
            $usedTogether,
            $faq,
            $newBlocks,
            $newTasks,
            $newUsedTogether,
            $newFaq,
            $deleteBlocks,
            $deleteTasks,
            $deleteUsedTogether,
            $deleteFaq,
            $availableLanguages,
            &$createdBlocks,
            &$createdTasks,
            &$createdUsedTogether,
            &$createdFaq
        ): void {
            $now = date('Y-m-d H:i:s');

            $this->categories->update($categoryId, [
                'alias' => $alias,
                'category' => $categoryType,
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

            foreach ($deleteBlocks as $blockId) {
                if ($this->isOwnedCategoryEntity(Tables::CIPHERS_CATEGORIES_BLOCKS, 'category_id', $categoryId, $blockId)) {
                    $this->db->execute('DELETE FROM ' . Tables::CIPHERS_CATEGORIES_BLOCKS . ' WHERE id = ?', [$blockId]);
                }
            }

            foreach ($blocks as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $blockId = (int) ($row['id'] ?? 0);

                if (!$this->isOwnedCategoryEntity(Tables::CIPHERS_CATEGORIES_BLOCKS, 'category_id', $categoryId, $blockId)) {
                    continue;
                }

                $this->db->execute(
                    'UPDATE ' . Tables::CIPHERS_CATEGORIES_BLOCKS . ' SET sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                    [max(0, min(999999, (int) ($row['sort_order'] ?? 0))), (bool) ($row['published'] ?? true) ? 1 : 0, $now, $blockId]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertCategoryBlockTranslation($blockId, $language, $translation, $now);
                }
            }

            foreach ($newBlocks as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $tempId = (string) ($row['temp_id'] ?? '');
                $newId = (int) $this->db->insert(
                    'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_BLOCKS . ' (category_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                    [$categoryId, max(0, min(999999, (int) ($row['sort_order'] ?? 0))), (bool) ($row['published'] ?? true) ? 1 : 0, $now, $now]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertCategoryBlockTranslation($newId, $language, $translation, $now);
                }

                if ($tempId !== '') {
                    $createdBlocks[] = ['temp_id' => $tempId, 'id' => $newId];
                }
            }

            foreach ($deleteTasks as $taskId) {
                if ($this->isOwnedCategoryEntity(Tables::CIPHERS_CATEGORIES_TASKS, 'category_id', $categoryId, $taskId)) {
                    $this->db->execute('DELETE FROM ' . Tables::CIPHERS_CATEGORIES_TASKS . ' WHERE id = ?', [$taskId]);
                }
            }

            foreach ($tasks as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $taskId = (int) ($row['id'] ?? 0);
                if (!$this->isOwnedCategoryEntity(Tables::CIPHERS_CATEGORIES_TASKS, 'category_id', $categoryId, $taskId)) {
                    continue;
                }

                $this->db->execute(
                    'UPDATE ' . Tables::CIPHERS_CATEGORIES_TASKS . ' SET relation_cipher_id = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                    [
                        (int) ($row['relation_cipher_id'] ?? 0),
                        max(0, min(999999, (int) ($row['sort_order'] ?? 0))),
                        (bool) ($row['published'] ?? true) ? 1 : 0,
                        $now,
                        $taskId,
                    ]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];
                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertCategoryTaskTranslation($taskId, $language, $translation, $now);
                }
            }

            foreach ($newTasks as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $tempId = (string) ($row['temp_id'] ?? '');
                $newId = (int) $this->db->insert(
                    'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_TASKS . ' (category_id, relation_cipher_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                    [
                        $categoryId,
                        (int) ($row['relation_cipher_id'] ?? 0),
                        max(0, min(999999, (int) ($row['sort_order'] ?? 0))),
                        (bool) ($row['published'] ?? true) ? 1 : 0,
                        $now,
                        $now,
                    ]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];
                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertCategoryTaskTranslation($newId, $language, $translation, $now);
                }

                if ($tempId !== '') {
                    $createdTasks[] = ['temp_id' => $tempId, 'id' => $newId];
                }
            }

            foreach ($deleteUsedTogether as $usedTogetherId) {
                if ($this->isOwnedCategoryEntity(Tables::CIPHERS_CATEGORIES_USED_TOGETHER, 'category_id', $categoryId, $usedTogetherId)) {
                    $this->db->execute('DELETE FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER . ' WHERE id = ?', [$usedTogetherId]);
                }
            }

            foreach ($usedTogether as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $usedTogetherId = (int) ($row['id'] ?? 0);
                if (!$this->isOwnedCategoryEntity(Tables::CIPHERS_CATEGORIES_USED_TOGETHER, 'category_id', $categoryId, $usedTogetherId)) {
                    continue;
                }

                $this->db->execute(
                    'UPDATE ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER . ' SET relation_cipher_first_id = ?, relation_cipher_second_id = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                    [
                        (int) ($row['relation_cipher_first_id'] ?? 0),
                        (int) ($row['relation_cipher_second_id'] ?? 0),
                        max(0, min(999999, (int) ($row['sort_order'] ?? 0))),
                        (bool) ($row['published'] ?? true) ? 1 : 0,
                        $now,
                        $usedTogetherId,
                    ]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];
                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertCategoryUsedTogetherTranslation($usedTogetherId, $language, $translation, $now);
                }
            }

            foreach ($newUsedTogether as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $tempId = (string) ($row['temp_id'] ?? '');
                $newId = (int) $this->db->insert(
                    'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER . ' (category_id, relation_cipher_first_id, relation_cipher_second_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $categoryId,
                        (int) ($row['relation_cipher_first_id'] ?? 0),
                        (int) ($row['relation_cipher_second_id'] ?? 0),
                        max(0, min(999999, (int) ($row['sort_order'] ?? 0))),
                        (bool) ($row['published'] ?? true) ? 1 : 0,
                        $now,
                        $now,
                    ]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];
                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertCategoryUsedTogetherTranslation($newId, $language, $translation, $now);
                }

                if ($tempId !== '') {
                    $createdUsedTogether[] = ['temp_id' => $tempId, 'id' => $newId];
                }
            }

            foreach ($deleteFaq as $faqId) {
                if ($this->isOwnedCategoryEntity(Tables::CIPHERS_CATEGORIES_FAQ, 'category_id', $categoryId, $faqId)) {
                    $this->db->execute('DELETE FROM ' . Tables::CIPHERS_CATEGORIES_FAQ . ' WHERE id = ?', [$faqId]);
                }
            }

            foreach ($faq as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $faqId = (int) ($row['id'] ?? 0);
                if (!$this->isOwnedCategoryEntity(Tables::CIPHERS_CATEGORIES_FAQ, 'category_id', $categoryId, $faqId)) {
                    continue;
                }

                $this->db->execute(
                    'UPDATE ' . Tables::CIPHERS_CATEGORIES_FAQ . ' SET sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                    [
                        max(0, min(999999, (int) ($row['sort_order'] ?? 0))),
                        (bool) ($row['published'] ?? true) ? 1 : 0,
                        $now,
                        $faqId,
                    ]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];
                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertCategoryFaqTranslation($faqId, $language, $translation, $now);
                }
            }

            foreach ($newFaq as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $tempId = (string) ($row['temp_id'] ?? '');
                $newId = (int) $this->db->insert(
                    'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_FAQ . ' (category_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                    [
                        $categoryId,
                        max(0, min(999999, (int) ($row['sort_order'] ?? 0))),
                        (bool) ($row['published'] ?? true) ? 1 : 0,
                        $now,
                        $now,
                    ]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];
                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertCategoryFaqTranslation($newId, $language, $translation, $now);
                }

                if ($tempId !== '') {
                    $createdFaq[] = ['temp_id' => $tempId, 'id' => $newId];
                }
            }
        });
        $this->cache->tag('cipher_categories')->flush();

        return Response::json([
            'ok' => true,
            'message' => 'Категория и переводы сохранены.',
            'created' => [
                'blocks' => $createdBlocks,
                'tasks' => $createdTasks,
                'used_together' => $createdUsedTogether,
                'faq' => $createdFaq,
            ],
        ]);
    }

    /**
     * Сохраняет шифр и его локализованный контент (перевод шифра, блоки, FAQ, примеры).
     *
     * POST /api/admin/ciphers/{id}
     */
    #[ApiOperation(summary: 'Обновление шифра и контент-блоков', tags: ['admin'])]
    #[ApiResponse(status: 200, description: 'Шифр сохранён')]
    #[ApiResponse(status: 404, description: 'Шифр не найден')]
    #[ApiResponse(status: 422, description: 'Ошибки валидации')]
    #[ApiResponse(status: 401, description: 'Требуется авторизация')]
    #[ApiResponse(status: 403, description: 'Недостаточно прав')]
    public function updateCipher(Request $request): Response
    {
        $cipherId = (int) $request->route('id');
        $cipher = $this->ciphers->find($cipherId);

        if ($cipher === null) {
            throw new NotFoundException('Шифр не найден.');
        }

        $payload = $request->json();
        if (!is_array($payload)) {
            $payload = [];
        }

        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $translations = is_array($payload['translations'] ?? null) ? $payload['translations'] : [];
        $blocks = is_array($payload['blocks'] ?? null) ? $payload['blocks'] : [];
        $faq = is_array($payload['faq'] ?? null) ? $payload['faq'] : [];
        $examples = is_array($payload['examples'] ?? null) ? $payload['examples'] : [];
        $tags = is_array($payload['tags'] ?? null) ? $payload['tags'] : [];
        $newBlocks = is_array($payload['new_blocks'] ?? null) ? $payload['new_blocks'] : [];
        $newFaq = is_array($payload['new_faq'] ?? null) ? $payload['new_faq'] : [];
        $newExamples = is_array($payload['new_examples'] ?? null) ? $payload['new_examples'] : [];
        $newTags = is_array($payload['new_tags'] ?? null) ? $payload['new_tags'] : [];
        $deleteBlocks = array_map('intval', is_array($payload['delete_blocks'] ?? null) ? $payload['delete_blocks'] : []);
        $deleteFaq = array_map('intval', is_array($payload['delete_faq'] ?? null) ? $payload['delete_faq'] : []);
        $deleteExamples = array_map('intval', is_array($payload['delete_examples'] ?? null) ? $payload['delete_examples'] : []);
        $deleteTags = array_map('intval', is_array($payload['delete_tags'] ?? null) ? $payload['delete_tags'] : []);

        $availableLanguages = array_values(array_filter(array_map(
            static fn (mixed $language): string => mb_strtolower(trim((string) $language)),
            (array) config('locale.locales', [])
        ), static fn (string $language): bool => $language !== ''));

        $alias = mb_strtolower(trim((string) ($settings['alias'] ?? '')));
        $sortOrder = (int) ($settings['sort_order'] ?? 0);
        $categoryId = (int) ($settings['category_id'] ?? 0);
        $calculationMode = mb_strtolower(trim((string) ($settings['calculation_mode'] ?? 'client')));
        $published = (bool) ($settings['published'] ?? false) ? 1 : 0;

        $errors = [];

        if ($alias === '' || !preg_match('/^[a-z0-9-]{2,100}$/', $alias)) {
            $errors['settings.alias'][] = 'Alias должен содержать 2-100 символов: a-z, 0-9 и дефис.';
        }

        if ($sortOrder < 0 || $sortOrder > 999999) {
            $errors['settings.sort_order'][] = 'Порядок сортировки должен быть от 0 до 999999.';
        }

        if ($categoryId < 1) {
            $errors['settings.category_id'][] = 'Неверный ID категории.';
        }

        if (!in_array($calculationMode, ['api', 'client'], true)) {
            $errors['settings.calculation_mode'][] = 'Режим вычисления должен быть api или client.';
        }

        if ($this->ciphers->existsByAlias($alias, $cipherId)) {
            $errors['settings.alias'][] = 'Шифр с таким alias уже существует.';
        }

        foreach ($translations as $language => $row) {
            if (!in_array((string) $language, $availableLanguages, true) || !is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $metaTitle = trim((string) ($row['meta_title'] ?? ''));

            $nameShort = trim((string) ($row['name_short'] ?? ''));
            $descriptionStort = trim((string) ($row['description_stort'] ?? ''));

            if (mb_strlen($name) > 255) {
                $errors["translations.{$language}.name"][] = 'Название не должно превышать 255 символов.';
            }

            if (mb_strlen($nameShort) > 100) {
                $errors["translations.{$language}.name_short"][] = 'Короткое название не должно превышать 100 символов.';
            }

            if (mb_strlen($descriptionStort) > 255) {
                $errors["translations.{$language}.description_stort"][] = 'Короткое описание не должно превышать 255 символов.';
            }

            if (mb_strlen($metaTitle) > 255) {
                $errors["translations.{$language}.meta_title"][] = 'Meta title не должен превышать 255 символов.';
            }
        }

        foreach ($tags as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

            foreach ($itemTranslations as $language => $translation) {
                if (!in_array((string) $language, $availableLanguages, true) || !is_array($translation)) {
                    continue;
                }

                $tagValue = trim((string) ($translation['tag'] ?? ''));

                if (mb_strlen($tagValue) > 100) {
                    $errors["tags.{$index}.translations.{$language}.tag"][] = 'Tag не должен превышать 100 символов.';
                }
            }
        }

        if ($errors !== []) {
            throw new ValidationFailedException('The given data was invalid.', ['errors' => $errors]);
        }

        $createdBlocks = [];
        $createdFaq = [];
        $createdExamples = [];
        $createdTags = [];

        $this->db->transaction(function () use (
            $cipherId,
            $alias,
            $sortOrder,
            $categoryId,
            $calculationMode,
            $published,
            $translations,
            $blocks,
            $faq,
            $examples,
            $tags,
            $newBlocks,
            $newFaq,
            $newExamples,
            $newTags,
            $deleteBlocks,
            $deleteFaq,
            $deleteExamples,
            $deleteTags,
            $availableLanguages,
            &$createdBlocks,
            &$createdFaq,
            &$createdExamples,
            &$createdTags
        ): void {
            $now = date('Y-m-d H:i:s');

            $this->ciphers->update($cipherId, [
                'category_id' => $categoryId,
                'alias' => $alias,
                'calculation_mode' => $calculationMode,
                'sort_order' => $sortOrder,
                'published' => $published,
                'updated_at' => $now,
            ]);

            foreach ($availableLanguages as $language) {
                $row = is_array($translations[$language] ?? null) ? $translations[$language] : [];
                $this->upsertCipherTranslation($cipherId, $language, $row, $now);
            }

            foreach ($deleteBlocks as $blockId) {
                if ($this->isOwnedEntity(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, $blockId)) {
                    $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE id = ?', [$blockId]);
                }
            }

            foreach ($deleteFaq as $faqId) {
                if ($this->isOwnedEntity(Tables::CIPHERS_FAQ, 'app_id', $cipherId, $faqId)) {
                    $this->db->execute('DELETE FROM ' . Tables::CIPHERS_FAQ . ' WHERE id = ?', [$faqId]);
                }
            }

            foreach ($deleteExamples as $exampleId) {
                if ($this->isOwnedEntity(Tables::CIPHERS_EXAMPLES, 'app_id', $cipherId, $exampleId)) {
                    $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE id = ?', [$exampleId]);
                }
            }

            foreach ($deleteTags as $tagId) {
                if ($this->isOwnedEntity(Tables::CIPHERS_TAGS, 'app_id', $cipherId, $tagId)) {
                    $this->db->execute('DELETE FROM ' . Tables::CIPHERS_TAGS . ' WHERE id = ?', [$tagId]);
                }
            }

            foreach ($blocks as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $blockId = (int) ($row['id'] ?? 0);

                if (!$this->isOwnedEntity(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, $blockId)) {
                    continue;
                }

                $this->db->execute(
                    'UPDATE ' . Tables::CIPHERS_BLOCKS . ' SET sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                    [max(0, min(999999, (int) ($row['sort_order'] ?? 0))), (bool) ($row['published'] ?? true) ? 1 : 0, $now, $blockId]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertBlockTranslation($blockId, $language, $translation, $now);
                }
            }

            foreach ($faq as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $faqId = (int) ($row['id'] ?? 0);

                if (!$this->isOwnedEntity(Tables::CIPHERS_FAQ, 'app_id', $cipherId, $faqId)) {
                    continue;
                }

                $this->db->execute(
                    'UPDATE ' . Tables::CIPHERS_FAQ . ' SET sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                    [max(0, min(999999, (int) ($row['sort_order'] ?? 0))), (bool) ($row['published'] ?? true) ? 1 : 0, $now, $faqId]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertFaqTranslation($faqId, $language, $translation, $now);
                }
            }

            foreach ($examples as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $exampleId = (int) ($row['id'] ?? 0);

                if (!$this->isOwnedEntity(Tables::CIPHERS_EXAMPLES, 'app_id', $cipherId, $exampleId)) {
                    continue;
                }

                $this->db->execute(
                    'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                    [max(0, min(999999, (int) ($row['sort_order'] ?? 0))), (bool) ($row['published'] ?? true) ? 1 : 0, $now, $exampleId]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertExampleTranslation($exampleId, $language, $translation, $now);
                }
            }

            foreach ($tags as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $tagId = (int) ($row['id'] ?? 0);

                if (!$this->isOwnedEntity(Tables::CIPHERS_TAGS, 'app_id', $cipherId, $tagId)) {
                    continue;
                }

                $this->db->execute(
                    'UPDATE ' . Tables::CIPHERS_TAGS . ' SET sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                    [max(0, min(999999, (int) ($row['sort_order'] ?? 0))), (bool) ($row['published'] ?? true) ? 1 : 0, $now, $tagId]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertTagTranslation($tagId, $language, $translation, $now);
                }
            }

            foreach ($newBlocks as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $tempId = (string) ($row['temp_id'] ?? '');
                $newId = (int) $this->db->insert(
                    'INSERT INTO ' . Tables::CIPHERS_BLOCKS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                    [$cipherId, max(0, min(999999, (int) ($row['sort_order'] ?? 0))), (bool) ($row['published'] ?? true) ? 1 : 0, $now, $now]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertBlockTranslation($newId, $language, $translation, $now);
                }

                if ($tempId !== '') {
                    $createdBlocks[] = ['temp_id' => $tempId, 'id' => $newId];
                }
            }

            foreach ($newFaq as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $tempId = (string) ($row['temp_id'] ?? '');
                $newId = (int) $this->db->insert(
                    'INSERT INTO ' . Tables::CIPHERS_FAQ . ' (app_id, sort_order, published, show_in_category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                    [$cipherId, max(0, min(999999, (int) ($row['sort_order'] ?? 0))), (bool) ($row['published'] ?? true) ? 1 : 0, 0, $now, $now]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertFaqTranslation($newId, $language, $translation, $now);
                }

                if ($tempId !== '') {
                    $createdFaq[] = ['temp_id' => $tempId, 'id' => $newId];
                }
            }

            foreach ($newExamples as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $tempId = (string) ($row['temp_id'] ?? '');
                $newId = (int) $this->db->insert(
                    'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                    [$cipherId, max(0, min(999999, (int) ($row['sort_order'] ?? 0))), (bool) ($row['published'] ?? true) ? 1 : 0, $now, $now]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertExampleTranslation($newId, $language, $translation, $now);
                }

                if ($tempId !== '') {
                    $createdExamples[] = ['temp_id' => $tempId, 'id' => $newId];
                }
            }

            foreach ($newTags as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $tempId = (string) ($row['temp_id'] ?? '');
                $newId = (int) $this->db->insert(
                    'INSERT INTO ' . Tables::CIPHERS_TAGS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                    [$cipherId, max(0, min(999999, (int) ($row['sort_order'] ?? 0))), (bool) ($row['published'] ?? true) ? 1 : 0, $now, $now]
                );

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertTagTranslation($newId, $language, $translation, $now);
                }

                if ($tempId !== '') {
                    $createdTags[] = ['temp_id' => $tempId, 'id' => $newId];
                }
            }
        });

        return Response::json([
            'ok' => true,
            'message' => 'Шифр и контент сохранены.',
            'created' => [
                'blocks'   => $createdBlocks,
                'faq'      => $createdFaq,
                'examples' => $createdExamples,
                'tags'     => $createdTags,
            ],
        ]);
    }

    /**
     * Проверяет принадлежность сущности конкретному шифру.
     */
    private function isOwnedEntity(string $table, string $foreignKey, int $cipherId, int $entityId): bool
    {
        if ($entityId < 1) {
            return false;
        }

        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE id = ? AND ' . $foreignKey . ' = ? LIMIT 1',
            [$entityId, $cipherId]
        );

        return $row !== false;
    }

    /**
     * Проверяет принадлежность сущности конкретной категории.
     */
    private function isOwnedCategoryEntity(string $table, string $foreignKey, int $categoryId, int $entityId): bool
    {
        if ($entityId < 1) {
            return false;
        }

        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE id = ? AND ' . $foreignKey . ' = ? LIMIT 1',
            [$entityId, $categoryId]
        );

        return $row !== false;
    }

    /**
     * Создаёт или обновляет перевод шифра для языка.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertCipherTranslation(int $cipherId, string $language, array $row, string $now): void
    {
        $name = trim((string) ($row['name'] ?? ''));
        $nameShort = trim((string) ($row['name_short'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $descriptionStort = trim((string) ($row['description_stort'] ?? ''));
        $metaTitle = trim((string) ($row['meta_title'] ?? ''));
        $metaDescription = trim((string) ($row['meta_description'] ?? ''));

        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ? AND language = ? LIMIT 1',
            [$cipherId, $language]
        );

        $hasAnyValue = $name !== '' || $nameShort !== '' || $description !== '' || $descriptionStort !== '' || $metaTitle !== '' || $metaDescription !== '';

        if (!$hasAnyValue) {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ? AND language = ?',
                    [$cipherId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_TRANSLATIONS . ' (app_id, language, name, name_short, description, description_stort, meta_title, meta_description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$cipherId, $language, $name, $nameShort, $description !== '' ? $description : null, $descriptionStort, $metaTitle, $metaDescription !== '' ? $metaDescription : null, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_TRANSLATIONS . ' SET name = ?, name_short = ?, description = ?, description_stort = ?, meta_title = ?, meta_description = ?, updated_at = ? WHERE id = ?',
            [$name, $nameShort, $description !== '' ? $description : null, $descriptionStort, $metaTitle, $metaDescription !== '' ? $metaDescription : null, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод info-блока.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertBlockTranslation(int $blockId, string $language, array $row, string $now): void
    {
        $title = trim((string) ($row['title'] ?? ''));
        $text = trim((string) ($row['text'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ? LIMIT 1',
            [$blockId, $language]
        );

        if ($title === '' && $text === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ?',
                    [$blockId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' (block_id, language, title, text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$blockId, $language, $title, $text, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' SET title = ?, text = ?, updated_at = ? WHERE id = ?',
            [$title, $text, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод info-блока категории.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertCategoryBlockTranslation(int $blockId, string $language, array $row, string $now): void
    {
        $title = trim((string) ($row['title'] ?? ''));
        $text = trim((string) ($row['text'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ? LIMIT 1',
            [$blockId, $language]
        );

        if ($title === '' && $text === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ?',
                    [$blockId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS . ' (block_id, language, title, text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$blockId, $language, $title, $text, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS . ' SET title = ?, text = ?, updated_at = ? WHERE id = ?',
            [$title, $text, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод задачи категории.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertCategoryTaskTranslation(int $taskId, string $language, array $row, string $now): void
    {
        $title = trim((string) ($row['title'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS . ' WHERE task_id = ? AND language = ? LIMIT 1',
            [$taskId, $language]
        );

        if ($title === '' && $description === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS . ' WHERE task_id = ? AND language = ?',
                    [$taskId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS . ' (task_id, language, title, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$taskId, $language, $title, $description, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS . ' SET title = ?, description = ?, updated_at = ? WHERE id = ?',
            [$title, $description, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод связки used together для категории.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertCategoryUsedTogetherTranslation(int $usedTogetherId, string $language, array $row, string $now): void
    {
        $title = trim((string) ($row['title'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS . ' WHERE used_together_id = ? AND language = ? LIMIT 1',
            [$usedTogetherId, $language]
        );

        if ($title === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS . ' WHERE used_together_id = ? AND language = ?',
                    [$usedTogetherId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS . ' (used_together_id, language, title, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [$usedTogetherId, $language, $title, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS . ' SET title = ?, updated_at = ? WHERE id = ?',
            [$title, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод FAQ для категории.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertCategoryFaqTranslation(int $faqId, string $language, array $row, string $now): void
    {
        $question = trim((string) ($row['question'] ?? ''));
        $answer = trim((string) ($row['answer'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS . ' WHERE faq_id = ? AND language = ? LIMIT 1',
            [$faqId, $language]
        );

        if ($question === '' && $answer === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS . ' WHERE faq_id = ? AND language = ?',
                    [$faqId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS . ' (faq_id, language, question, answer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$faqId, $language, $question, $answer, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS . ' SET question = ?, answer = ?, updated_at = ? WHERE id = ?',
            [$question, $answer, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод FAQ.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertFaqTranslation(int $faqId, string $language, array $row, string $now): void
    {
        $question = trim((string) ($row['question'] ?? ''));
        $answer = trim((string) ($row['answer'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' WHERE faq_id = ? AND language = ? LIMIT 1',
            [$faqId, $language]
        );

        if ($question === '' && $answer === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' WHERE faq_id = ? AND language = ?',
                    [$faqId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' (faq_id, language, question, answer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$faqId, $language, $question, $answer, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' SET question = ?, answer = ?, updated_at = ? WHERE id = ?',
            [$question, $answer, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertExampleTranslation(int $exampleId, string $language, array $row, string $now): void
    {
        $title = trim((string) ($row['title'] ?? ''));
        $input = trim((string) ($row['input'] ?? ''));
        $output = trim((string) ($row['output'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($title === '' && $input === '' && $output === '' && $description === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ?',
                    [$exampleId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' (example_id, language, title, input, output, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$exampleId, $language, $title, $input, $output, $description, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' SET title = ?, input = ?, output = ?, description = ?, updated_at = ? WHERE id = ?',
            [$title, $input, $output, $description, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод тега.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertTagTranslation(int $tagId, string $language, array $row, string $now): void
    {
        $tagValue = trim((string) ($row['tag'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' WHERE tag_id = ? AND language = ? LIMIT 1',
            [$tagId, $language]
        );

        if ($tagValue === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' WHERE tag_id = ? AND language = ?',
                    [$tagId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' (tag_id, language, tag, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [$tagId, $language, $tagValue, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' SET tag = ?, updated_at = ? WHERE id = ?',
            [$tagValue, $now, (int) $existing['id']]
        );
    }
}
