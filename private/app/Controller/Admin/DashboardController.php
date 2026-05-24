<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Repository\UserRepository;
use App\View\View;

/**
 * Контроллер главной страницы панели администратора.
 */
final class DashboardController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private readonly View     $view,
        private readonly UserRepository $users
    ) {
    }

    /**
     * Отображает дашборд: список пользователей и базовую статистику.
     */
    public function index(Request $request): Response
    {
        $users = $this->users->listForDashboard();

        $this->view
            ->setTitle('Панель администратора')
            ->setBreadcrumbs([['label' => 'Дашборд']])
            ->setContent($this->view->fetch('admin/dashboard/index.tpl', [
                'users'      => $users ?: [],
                'admin_path' => config('admin.path', '/admin'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }
}
