<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Repository\AnalyticsRepository;
use App\View\View;

/**
 * Контроллер отдельной аналитической страницы флагманского сервиса «Cipher Solver».
 *
 * Каркас: сейчас выводит базовую статистику использования по tool_slug='cipher-solver'
 * из tool_usage_events. Solver-специфичные метрики (распределение типов шифров,
 * доля успешных расшифровок, длины ввода) — заглушки, прорабатываются позже.
 */
final class CipherSolverAnalyticsController
{
    /**
     * Идентификатор инструмента в tool_usage_events (совпадает с API-action).
     */
    private const string TOOL_SLUG = 'cipher-solver';

    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private readonly View $view,
        private readonly AnalyticsRepository $analyticsRepo,
    ) {
    }

    /**
     * Отображает аналитическую страницу solver'а.
     */
    public function index(Request $request): Response
    {
        $enabled = (bool) config('analytics.enabled', true);

        $daily = $enabled ? $this->analyticsRepo->dailyUsageForTool(self::TOOL_SLUG, 30) : [];

        $this->view
            ->setTitle('Аналитика Cipher Solver')
            ->setBreadcrumbs([['label' => 'Аналитика Cipher Solver']])
            ->setContent($this->view->fetch('admin/cipher_solver_analytics/index.tpl', [
                'admin_path'        => config('admin.path', '/admin'),
                'analytics_enabled' => $enabled,
                'tool_slug'         => self::TOOL_SLUG,
                'total_30'          => $enabled ? $this->analyticsRepo->totalCountForTool(self::TOOL_SLUG, 30) : 0,
                'total_7'           => $enabled ? $this->analyticsRepo->totalCountForTool(self::TOOL_SLUG, 7) : 0,
                'source_30'         => $enabled
                    ? $this->analyticsRepo->sourceBreakdownForTool(self::TOOL_SLUG, 30)
                    : ['local' => 0, 'embed' => 0],
                'daily_json'        => (string) json_encode($daily, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }
}
