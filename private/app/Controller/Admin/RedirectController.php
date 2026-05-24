<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Cache\CacheInterface;
use App\Controller\Admin\Request\RedirectRequest;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Repository\RedirectRepository;
use App\Validation\ValidationException;
use App\View\View;

/**
 * Контроллер управления HTTP-редиректами в панели администратора.
 */
final class RedirectController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private readonly View           $view,
        private readonly RedirectRepository $redirects,
        private readonly Session        $session,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Отображает список всех редиректов.
     */
    public function index(Request $request): Response
    {
        $redirects = $this->redirects->listForAdmin();

        $this->view
            ->setTitle('Редиректы')
            ->setBreadcrumbs([['label' => 'Редиректы']])
            ->setContent($this->view->fetch('admin/redirects/index.tpl', [
                'redirects'  => $redirects ?: [],
                'admin_path' => config('admin.path', '/admin'),
                'success'    => $this->session->getFlash('success'),
                'error'      => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Отображает форму создания нового редиректа.
     */
    public function create(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');

        $this->view
            ->setTitle('Добавить редирект')
            ->setBreadcrumbs([
                ['label' => 'Редиректы', 'url' => $adminPath . '/redirects'],
                ['label' => 'Добавить редирект'],
            ])
            ->setContent($this->view->fetch('admin/redirects/form.tpl', [
                'redirect'   => null,
                'admin_path' => $adminPath,
                'error'      => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Сохраняет новый редирект в базу данных.
     */
    public function store(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');

        try {
            $dto = RedirectRequest::fromRequest($request);
        } catch (ValidationException $e) {
            $this->session->flash('error', $this->firstValidationError($e));
            return new Response('', 302, ['Location' => $adminPath . '/redirects/create']);
        }

        $error = $dto->validateBusinessRules();

        if ($error !== null) {
            $this->session->flash('error', $error);
            return new Response('', 302, ['Location' => $adminPath . '/redirects/create']);
        }

        $now = date('Y-m-d H:i:s');

        $this->redirects->insert([
            'from_path' => $dto->fromPath(),
            'to_path' => $dto->toPath(),
            'status_code' => $dto->statusCode(),
            'is_active' => $dto->isActive(),
            'hit_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->cache->tag('redirects')->flush();

        $this->session->flash('success', 'Редирект добавлен.');
        return new Response('', 302, ['Location' => $adminPath . '/redirects']);
    }

    /**
     * Отображает форму редактирования существующего редиректа.
     */
    public function edit(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');
        $id        = (int) $request->route('id');
        $redirect  = $this->redirects->find($id);

        if ($redirect === null) {
            $this->session->flash('error', 'Редирект не найден.');
            return new Response('', 302, ['Location' => $adminPath . '/redirects']);
        }

        $this->view
            ->setTitle('Редактировать редирект')
            ->setBreadcrumbs([
                ['label' => 'Редиректы', 'url' => $adminPath . '/redirects'],
                ['label' => 'Редактировать редирект'],
            ])
            ->setContent($this->view->fetch('admin/redirects/form.tpl', [
                'redirect'   => $redirect,
                'admin_path' => $adminPath,
                'error'      => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Сохраняет изменения существующего редиректа.
     */
    public function update(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');
        $id        = (int) $request->route('id');

        if ($this->redirects->find($id) === null) {
            $this->session->flash('error', 'Редирект не найден.');
            return new Response('', 302, ['Location' => $adminPath . '/redirects']);
        }

        try {
            $dto = RedirectRequest::fromRequest($request);
        } catch (ValidationException $e) {
            $this->session->flash('error', $this->firstValidationError($e));
            return new Response('', 302, ['Location' => $adminPath . '/redirects/' . $id . '/edit']);
        }

        $error = $dto->validateBusinessRules();

        if ($error !== null) {
            $this->session->flash('error', $error);
            return new Response('', 302, ['Location' => $adminPath . '/redirects/' . $id . '/edit']);
        }

        $this->redirects->update($id, [
            'from_path' => $dto->fromPath(),
            'to_path' => $dto->toPath(),
            'status_code' => $dto->statusCode(),
            'is_active' => $dto->isActive(),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->cache->tag('redirects')->flush();

        $this->session->flash('success', 'Редирект обновлён.');
        return new Response('', 302, ['Location' => $adminPath . '/redirects']);
    }

    /**
     * Удаляет редирект из базы данных.
     */
    public function destroy(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');
        $id        = (int) $request->route('id');

        $this->redirects->delete($id);
        $this->cache->tag('redirects')->flush();

        $this->session->flash('success', 'Редирект удалён.');
        return new Response('', 302, ['Location' => $adminPath . '/redirects']);
    }

    /**
     * Возвращает первое сообщение из ValidationException для flash-ошибки.
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
