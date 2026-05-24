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

        $availableLanguages = array_values(array_filter(array_map(
            static fn (mixed $language): string => mb_strtolower(trim((string) $language)),
            (array) config('locale.locales', [])
        ), static fn (string $language): bool => $language !== ''));

        $alias = mb_strtolower(trim((string) ($settings['alias'] ?? '')));
        $sortOrder = (int) ($settings['sort_order'] ?? 0);
        $categoryId = (int) ($settings['category_id'] ?? 0);
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

            if (mb_strlen($name) > 255) {
                $errors["translations.{$language}.name"][] = 'Название не должно превышать 255 символов.';
            }

            if (mb_strlen($nameShort) > 100) {
                $errors["translations.{$language}.name_short"][] = 'Короткое название не должно превышать 100 символов.';
            }

            if (mb_strlen($metaTitle) > 255) {
                $errors["translations.{$language}.meta_title"][] = 'Meta title не должен превышать 255 символов.';
            }
        }

        if ($errors !== []) {
            throw new ValidationFailedException('The given data was invalid.', ['errors' => $errors]);
        }

        $this->db->transaction(function () use ($cipherId, $alias, $sortOrder, $categoryId, $published, $translations, $blocks, $faq, $examples, $availableLanguages): void {
            $now = date('Y-m-d H:i:s');

            $this->ciphers->update($cipherId, [
                'category_id' => $categoryId,
                'alias' => $alias,
                'sort_order' => $sortOrder,
                'published' => $published,
                'updated_at' => $now,
            ]);

            foreach ($availableLanguages as $language) {
                $row = is_array($translations[$language] ?? null) ? $translations[$language] : [];
                $this->upsertCipherTranslation($cipherId, $language, $row, $now);
            }

            foreach ($blocks as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $blockId = (int) ($row['id'] ?? 0);

                if (!$this->isOwnedEntity(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, $blockId)) {
                    continue;
                }

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

                $itemTranslations = is_array($row['translations'] ?? null) ? $row['translations'] : [];

                foreach ($availableLanguages as $language) {
                    $translation = is_array($itemTranslations[$language] ?? null) ? $itemTranslations[$language] : [];
                    $this->upsertExampleTranslation($exampleId, $language, $translation, $now);
                }
            }
        });

        return Response::json([
            'ok' => true,
            'message' => 'Шифр и контент сохранены.',
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
     * Создаёт или обновляет перевод шифра для языка.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertCipherTranslation(int $cipherId, string $language, array $row, string $now): void
    {
        $name = trim((string) ($row['name'] ?? ''));
        $nameShort = trim((string) ($row['name_short'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $metaTitle = trim((string) ($row['meta_title'] ?? ''));
        $metaDescription = trim((string) ($row['meta_description'] ?? ''));

        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ? AND language = ? LIMIT 1',
            [$cipherId, $language]
        );

        $hasAnyValue = $name !== '' || $nameShort !== '' || $description !== '' || $metaTitle !== '' || $metaDescription !== '';

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
                'INSERT INTO ' . Tables::CIPHERS_TRANSLATIONS . ' (app_id, language, name, name_short, description, meta_title, meta_description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$cipherId, $language, $name, $nameShort, $description !== '' ? $description : null, $metaTitle, $metaDescription !== '' ? $metaDescription : null, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_TRANSLATIONS . ' SET name = ?, name_short = ?, description = ?, meta_title = ?, meta_description = ?, updated_at = ? WHERE id = ?',
            [$name, $nameShort, $description !== '' ? $description : null, $metaTitle, $metaDescription !== '' ? $metaDescription : null, $now, (int) $existing['id']]
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
}
