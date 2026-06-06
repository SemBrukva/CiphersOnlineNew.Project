<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Repository\AnalyticsRepository;
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
        private readonly View $view,
        private readonly UserRepository $users,
        private readonly AnalyticsRepository $analyticsRepo,
    ) {
    }

    /**
     * Отображает дашборд: список пользователей и статистику использования инструментов.
     */
    public function index(Request $request): Response
    {
        $users = $this->users->listForDashboard();

        $this->view
            ->setTitle('Панель администратора')
            ->setBreadcrumbs([['label' => 'Дашборд']])
            ->setContent($this->view->fetch('admin/dashboard/index.tpl', [
                'users'              => $users ?: [],
                'admin_path'         => config('admin.path', '/admin'),
                'analytics_enabled'  => config('analytics.enabled', true),
                'analytics_top'      => config('analytics.enabled', true) ? $this->analyticsRepo->topTools(10, 30) : [],
                'analytics_total_30' => config('analytics.enabled', true) ? $this->analyticsRepo->totalCount(30) : 0,
                'analytics_total_7'  => config('analytics.enabled', true) ? $this->analyticsRepo->totalCount(7) : 0,
                'analytics_tools_30' => config('analytics.enabled', true) ? $this->analyticsRepo->uniqueToolsCount(30) : 0,
                'analytics_daily_json' => config('analytics.enabled', true)
                    ? (string) json_encode($this->analyticsRepo->dailyUsage(30), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : '{}',
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }
}
