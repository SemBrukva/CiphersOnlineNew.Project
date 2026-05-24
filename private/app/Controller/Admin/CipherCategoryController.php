<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Cache\CacheInterface;
use App\Controller\Admin\Request\CipherCategoryRequest;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Repository\CipherCategoryRepository;
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
        $category = $this->categories->find($id);
        $availableLanguages = array_values(array_filter(array_map(
            static fn (mixed $language): string => mb_strtolower(trim((string) $language)),
            (array) config('locale.locales', [])
        ), static fn (string $language): bool => $language !== ''));

        if ($category === null) {
            $this->session->flash('error', 'Категория не найдена.');

            return new Response('', 302, ['Location' => $adminPath . '/cipher-categories']);
        }

        $translationsByLanguage = [];
        foreach ($this->translations->listByCategoryId($id) as $translation) {
            $language = mb_strtolower((string) ($translation['language'] ?? ''));

            if ($language !== '') {
                $translationsByLanguage[$language] = $translation;
            }
        }

        $this->view
            ->setTitle('Редактировать категорию')
            ->setBreadcrumbs([
                ['label' => 'Категории шифров', 'url' => $adminPath . '/cipher-categories'],
                ['label' => 'Редактировать категорию'],
            ])
            ->setContent($this->view->fetch('admin/cipher_categories/edit.tpl', [
                'category' => $category,
                'translations_by_language' => $translationsByLanguage,
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
}
