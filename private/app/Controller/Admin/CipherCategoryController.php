<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Cache\CacheInterface;
use App\Controller\Admin\Request\CipherCategoryRequest;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Repository\CipherCategoryRepository;
use App\Repository\CipherRepository;
use App\Repository\CipherCategoryTranslationRepository;
use App\Validation\ValidationException;
use App\View\View;

/**
 * Контроллер управления категориями шифров в панели администратора.
 */
final class CipherCategoryController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private readonly View $view,
        private readonly CipherCategoryRepository $categories,
        private readonly CipherRepository $ciphers,
        private readonly CipherCategoryTranslationRepository $translations,
        private readonly Session $session,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Отображает список категорий шифров.
     */
    public function index(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');
        $availableLanguages = array_values(array_filter(array_map(
            static fn (mixed $language): string => mb_strtolower(trim((string) $language)),
            (array) config('locale.locales', [])
        ), static fn (string $language): bool => $language !== ''));

        $this->view
            ->setTitle('Категории шифров')
            ->setBreadcrumbs([['label' => 'Категории шифров']])
            ->setContent($this->view->fetch('admin/cipher_categories/index.tpl', [
                'categories' => $this->categories->listForAdmin(),
                'category_languages' => $this->categories->listLanguageMapByCategory(),
                'available_languages' => $availableLanguages,
                'admin_path' => $adminPath,
                'success' => $this->session->getFlash('success'),
                'error' => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Отображает форму создания категории.
     */
    public function create(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');

        $this->view
            ->setTitle('Добавить категорию')
            ->setBreadcrumbs([
                ['label' => 'Категории шифров', 'url' => $adminPath . '/cipher-categories'],
                ['label' => 'Добавить категорию'],
            ])
            ->setContent($this->view->fetch('admin/cipher_categories/form.tpl', [
                'category' => null,
                'admin_path' => $adminPath,
                'error' => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Сохраняет новую категорию.
     */
    public function store(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');

        try {
            $dto = CipherCategoryRequest::fromRequest($request);
        } catch (ValidationException $e) {
            $this->session->flash('error', $this->firstValidationError($e));

            return new Response('', 302, ['Location' => $adminPath . '/cipher-categories/create']);
        }

        $businessError = $dto->validateBusinessRules();

        if ($businessError !== null) {
            $this->session->flash('error', $businessError);

            return new Response('', 302, ['Location' => $adminPath . '/cipher-categories/create']);
        }

        if ($this->categories->existsByAlias($dto->alias())) {
            $this->session->flash('error', 'Категория с таким alias уже существует.');

            return new Response('', 302, ['Location' => $adminPath . '/cipher-categories/create']);
        }

        $now = date('Y-m-d H:i:s');

        $this->categories->insert([
            'alias' => $dto->alias(),
            'sort_order' => $dto->sortOrder(),
            'published' => $dto->published(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->cache->tag('cipher_categories')->flush();

        $this->session->flash('success', 'Категория добавлена.');

        return new Response('', 302, ['Location' => $adminPath . '/cipher-categories']);
    }

    /**
     * Отображает форму редактирования категории.
     */
    public function edit(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');
        $id = (int) $request->route('id');
        $availableLanguages = array_values(array_filter(array_map(
            static fn (mixed $language): string => mb_strtolower(trim((string) $language)),
            (array) config('locale.locales', [])
        ), static fn (string $language): bool => $language !== ''));
        $activeCategory = $this->buildCategoryPayload($id);

        if ($activeCategory === null) {
            $this->session->flash('error', 'Категория не найдена.');

            return new Response('', 302, ['Location' => $adminPath . '/cipher-categories']);
        }

        $this->view
            ->setTitle('Редактировать категорию')
            ->setBreadcrumbs([
                ['label' => 'Категории шифров', 'url' => $adminPath . '/cipher-categories'],
                ['label' => 'Редактировать категорию'],
            ])
            ->setContent($this->view->fetch('admin/cipher_categories/edit.tpl', [
                'active_category' => $activeCategory,
                'available_languages' => $availableLanguages,
                'active_language' => mb_strtolower((string) $request->query('language', '')),
                'admin_path' => $adminPath,
                'error' => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Сохраняет изменения категории.
     */
    public function update(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');
        $id = (int) $request->route('id');

        if ($this->categories->find($id) === null) {
            $this->session->flash('error', 'Категория не найдена.');

            return new Response('', 302, ['Location' => $adminPath . '/cipher-categories']);
        }

        try {
            $dto = CipherCategoryRequest::fromRequest($request);
        } catch (ValidationException $e) {
            $this->session->flash('error', $this->firstValidationError($e));

            return new Response('', 302, ['Location' => $adminPath . '/cipher-categories/' . $id . '/edit']);
        }

        $businessError = $dto->validateBusinessRules();

        if ($businessError !== null) {
            $this->session->flash('error', $businessError);

            return new Response('', 302, ['Location' => $adminPath . '/cipher-categories/' . $id . '/edit']);
        }

        if ($this->categories->existsByAlias($dto->alias(), $id)) {
            $this->session->flash('error', 'Категория с таким alias уже существует.');

            return new Response('', 302, ['Location' => $adminPath . '/cipher-categories/' . $id . '/edit']);
        }

        $this->categories->update($id, [
            'alias' => $dto->alias(),
            'sort_order' => $dto->sortOrder(),
            'published' => $dto->published(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->cache->tag('cipher_categories')->flush();

        $this->session->flash('success', 'Категория обновлена.');

        return new Response('', 302, ['Location' => $adminPath . '/cipher-categories']);
    }

    /**
     * Удаляет категорию.
     */
    public function destroy(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');
        $id = (int) $request->route('id');

        $this->categories->delete($id);
        $this->cache->tag('cipher_categories')->flush();

        $this->session->flash('success', 'Категория удалена.');

        return new Response('', 302, ['Location' => $adminPath . '/cipher-categories']);
    }

    /**
     * Возвращает первое сообщение валидации для flash-ошибки.
     */
    private function firstValidationError(ValidationException $e): string
    {
        foreach ($e->errors() as $messages) {
            if ($messages !== []) {
                return (string) $messages[0];
            }
        }

        return 'Некорректные входные данные.';
    }

    /**
     * Собирает полный payload категории для шаблона редактирования.
     *
     * @return array<string, mixed>|null
     */
    private function buildCategoryPayload(int $categoryId): ?array
    {
        $category = $this->categories->find($categoryId);

        if ($category === null) {
            return null;
        }

        $translationsByLanguage = [];
        foreach ($this->translations->listByCategoryId($categoryId) as $translation) {
            $language = mb_strtolower((string) ($translation['language'] ?? ''));

            if ($language !== '') {
                $translationsByLanguage[$language] = $translation;
            }
        }

        $blocks = $this->categories->listBlocksByCategoryId($categoryId);
        $blockIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $blocks);
        $blockTranslations = $this->groupByEntityAndLanguage(
            $this->categories->listBlockTranslationsByBlockIds($blockIds),
            'block_id',
            'language'
        );

        $tasks = $this->categories->listTasksByCategoryId($categoryId);
        $taskIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $tasks);
        $taskTranslations = $this->groupByEntityAndLanguage(
            $this->categories->listTaskTranslationsByTaskIds($taskIds),
            'task_id',
            'language'
        );

        $usedTogether = $this->categories->listUsedTogetherByCategoryId($categoryId);
        $usedTogetherIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $usedTogether);
        $usedTogetherTranslations = $this->groupByEntityAndLanguage(
            $this->categories->listUsedTogetherTranslationsByIds($usedTogetherIds),
            'used_together_id',
            'language'
        );

        $faq = $this->categories->listFaqByCategoryId($categoryId);
        $faqIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $faq);
        $faqTranslations = $this->groupByEntityAndLanguage(
            $this->categories->listFaqTranslationsByFaqIds($faqIds),
            'faq_id',
            'language'
        );

        return [
            'category' => $category,
            'translations_by_language' => $translationsByLanguage,
            'blocks' => $this->attachTranslations($blocks, $blockTranslations),
            'tasks' => $this->attachTranslations($tasks, $taskTranslations),
            'used_together' => $this->attachTranslations($usedTogether, $usedTogetherTranslations),
            'faq' => $this->attachTranslations($faq, $faqTranslations),
            'category_ciphers' => $this->ciphers->listForSelectByCategoryId($categoryId),
        ];
    }

    /**
     * Группирует строки переводов по сущности и языку.
     *
     * @param  array<int, array<string, mixed>> $rows
     * @return array<int, array<string, array<string, mixed>>>
     */
    private function groupByEntityAndLanguage(array $rows, string $idKey, string $languageKey): array
    {
        $result = [];

        foreach ($rows as $row) {
            $entityId = (int) ($row[$idKey] ?? 0);
            $language = mb_strtolower((string) ($row[$languageKey] ?? ''));

            if ($entityId > 0 && $language !== '') {
                $result[$entityId][$language] = $row;
            }
        }

        return $result;
    }

    /**
     * Добавляет к строкам сущностей карту переводов.
     *
     * @param  array<int, array<string, mixed>>                $rows
     * @param  array<int, array<string, array<string, mixed>>> $translationsMap
     * @return array<int, array<string, mixed>>
     */
    private function attachTranslations(array $rows, array $translationsMap): array
    {
        $result = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $row['translations_by_language'] = $translationsMap[$id] ?? [];
            $result[] = $row;
        }

        return $result;
    }
}
