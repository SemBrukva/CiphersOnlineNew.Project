<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Request\CipherCategoryTranslationRequest;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Repository\CipherCategoryRepository;
use App\Repository\CipherCategoryTranslationRepository;
use App\Validation\ValidationException;
use App\View\View;

/**
 * Контроллер управления переводами категорий шифров в админке.
 */
final class CipherCategoryTranslationController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private readonly View $view,
        private readonly CipherCategoryTranslationRepository $translations,
        private readonly CipherCategoryRepository $categories,
        private readonly Session $session
    ) {
    }

    /**
     * Отображает список переводов.
     */
    public function index(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');

        $this->view
            ->setTitle('Переводы категорий')
            ->setBreadcrumbs([['label' => 'Переводы категорий']])
            ->setContent($this->view->fetch('admin/cipher_category_translations/index.tpl', [
                'translations' => $this->translations->listForAdmin(),
                'admin_path' => $adminPath,
                'success' => $this->session->getFlash('success'),
                'error' => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Отображает форму создания перевода.
     */
    public function create(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');
        $prefillCategoryId = (int) $request->query('category_id', 0);
        $prefillLanguage = mb_strtolower(trim((string) $request->query('language', '')));
        $prefill = null;

        if ($prefillCategoryId > 0 || $prefillLanguage !== '') {
            $prefill = [
                'category_id' => $prefillCategoryId > 0 ? $prefillCategoryId : null,
                'language' => $prefillLanguage,
                'name' => '',
                'description' => '',
                'meta_title' => '',
                'meta_description' => '',
            ];
        }

        $this->view
            ->setTitle('Добавить перевод категории')
            ->setBreadcrumbs([
                ['label' => 'Переводы категорий', 'url' => $adminPath . '/cipher-category-translations'],
                ['label' => 'Добавить перевод'],
            ])
            ->setContent($this->view->fetch('admin/cipher_category_translations/form.tpl', [
                'translation' => $prefill,
                'categories' => $this->categories->listForSelect(),
                'admin_path' => $adminPath,
                'error' => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Сохраняет новый перевод категории.
     */
    public function store(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');

        try {
            $dto = CipherCategoryTranslationRequest::fromRequest($request);
        } catch (ValidationException $e) {
            $this->session->flash('error', $this->firstValidationError($e));

            return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations/create']);
        }

        $businessError = $dto->validateBusinessRules();

        if ($businessError !== null) {
            $this->session->flash('error', $businessError);

            return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations/create']);
        }

        if ($this->categories->find($dto->categoryId()) === null) {
            $this->session->flash('error', 'Выбранная категория не найдена.');

            return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations/create']);
        }

        if ($this->translations->existsByCategoryAndLanguage($dto->categoryId(), $dto->language())) {
            $this->session->flash('error', 'Перевод для этой категории и языка уже существует.');

            return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations/create']);
        }

        $now = date('Y-m-d H:i:s');

        $this->translations->insert([
            'category_id' => $dto->categoryId(),
            'language' => $dto->language(),
            'name' => $dto->name(),
            'description' => $dto->description(),
            'meta_title' => $dto->metaTitle(),
            'meta_description' => $dto->metaDescription(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->session->flash('success', 'Перевод категории добавлен.');

        return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations']);
    }

    /**
     * Отображает форму редактирования перевода.
     */
    public function edit(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');
        $id = (int) $request->route('id');
        $translation = $this->translations->find($id);

        if ($translation === null) {
            $this->session->flash('error', 'Перевод категории не найден.');

            return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations']);
        }

        $this->view
            ->setTitle('Редактировать перевод категории')
            ->setBreadcrumbs([
                ['label' => 'Переводы категорий', 'url' => $adminPath . '/cipher-category-translations'],
                ['label' => 'Редактировать перевод'],
            ])
            ->setContent($this->view->fetch('admin/cipher_category_translations/form.tpl', [
                'translation' => $translation,
                'categories' => $this->categories->listForSelect(),
                'admin_path' => $adminPath,
                'error' => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Сохраняет изменения перевода категории.
     */
    public function update(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');
        $id = (int) $request->route('id');

        if ($this->translations->find($id) === null) {
            $this->session->flash('error', 'Перевод категории не найден.');

            return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations']);
        }

        try {
            $dto = CipherCategoryTranslationRequest::fromRequest($request);
        } catch (ValidationException $e) {
            $this->session->flash('error', $this->firstValidationError($e));

            return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations/' . $id . '/edit']);
        }

        $businessError = $dto->validateBusinessRules();

        if ($businessError !== null) {
            $this->session->flash('error', $businessError);

            return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations/' . $id . '/edit']);
        }

        if ($this->categories->find($dto->categoryId()) === null) {
            $this->session->flash('error', 'Выбранная категория не найдена.');

            return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations/' . $id . '/edit']);
        }

        if ($this->translations->existsByCategoryAndLanguage($dto->categoryId(), $dto->language(), $id)) {
            $this->session->flash('error', 'Перевод для этой категории и языка уже существует.');

            return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations/' . $id . '/edit']);
        }

        $this->translations->update($id, [
            'category_id' => $dto->categoryId(),
            'language' => $dto->language(),
            'name' => $dto->name(),
            'description' => $dto->description(),
            'meta_title' => $dto->metaTitle(),
            'meta_description' => $dto->metaDescription(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->session->flash('success', 'Перевод категории обновлён.');

        return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations']);
    }

    /**
     * Удаляет перевод категории.
     */
    public function destroy(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');
        $id = (int) $request->route('id');

        $this->translations->delete($id);

        $this->session->flash('success', 'Перевод категории удалён.');

        return new Response('', 302, ['Location' => $adminPath . '/cipher-category-translations']);
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
