<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Repository\ToolsOverviewRepository;
use App\View\View;
use App\Yandex\WebmasterClient;
use Throwable;

/**
 * Контроллер аналитического обзора инструментов в административной панели.
 */
final class ToolsOverviewController
{
    /**
     * Создаёт контроллер.
     */
    public function __construct(
        private readonly View $view,
        private readonly ToolsOverviewRepository $overviewRepo,
        private readonly WebmasterClient $webmaster,
        private readonly Session $session,
    ) {
    }

    /**
     * Показывает аналитический обзор всех инструментов.
     */
    public function index(Request $request): Response
    {
        $adminPath = (string) config('admin.path', '/admin');
        $locales = $this->locales();
        $since30d = date('Y-m-d H:i:s', strtotime('-30 days'));

        $tools = $this->overviewRepo->listTools($since30d);
        $completeness = $this->overviewRepo->translationCompleteness();
        $rankStats = $this->overviewRepo->latestRankStats();
        $indexation = $this->overviewRepo->indexationBySlug('yandex');

        $toolsData = $this->assembleToolsData($tools, $completeness, $rankStats, $indexation, $locales);
        $summary = $this->buildSummary($toolsData, $locales);

        $this->view
            ->setTitle('Обзор инструментов')
            ->setBreadcrumbs([['label' => 'Обзор инструментов']])
            ->setContent($this->view->fetch('admin/tools_overview/index.tpl', [
                'admin_path'        => $adminPath,
                'tools'             => $toolsData,
                'summary'           => $summary,
                'locales'           => $locales,
                'yandex_configured' => $this->webmaster->isConfigured(),
                'success'           => $this->session->getFlash('success'),
                'error'             => $this->session->getFlash('error'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Запускает обновление статусов индексации через Яндекс Вебмастер API.
     */
    public function refreshIndexation(Request $request): Response
    {
        $adminPath = (string) config('admin.path', '/admin');

        if (!$this->webmaster->isConfigured()) {
            $this->session->flash('error', 'Яндекс Вебмастер не настроен. Проверьте переменные YANDEX_WEBMASTER_TOKEN, YANDEX_WEBMASTER_USER_ID, YANDEX_WEBMASTER_HOST_ID в .env.');
            return new Response('', 302, ['Location' => $adminPath . '/tools-overview']);
        }

        try {
            $saved = $this->runIndexationRefresh();
            $this->session->flash('success', "Индексация обновлена: обработано {$saved} страниц из мониторинга Яндекса.");
        } catch (Throwable $e) {
            $this->session->flash('error', 'Ошибка при запросе к Яндекс Вебмастер API: ' . $e->getMessage());
        }

        return new Response('', 302, ['Location' => $adminPath . '/tools-overview']);
    }

    /**
     * Запрашивает список URL из мониторинга Яндекса, сопоставляет с инструментами и сохраняет статусы.
     *
     * @return int Количество сохранённых записей.
     */
    private function runIndexationRefresh(): int
    {
        $locales = $this->locales();
        $appUrl = rtrim((string) config('app.url', ''), '/');
        $isMultilang = (bool) config('locale.multilang', false);
        $defaultLocale = (string) config('locale.locale', 'en');

        $toolsByCategoryAlias = $this->buildToolUrlMap($locales, $appUrl, $isMultilang, $defaultLocale);

        $offset = 0;
        $limit = 100;
        $urlIndexMap = [];

        do {
            $page = $this->webmaster->urlMonitoringList($limit, $offset);
            $urls = is_array($page['urls'] ?? null) ? $page['urls'] : [];

            foreach ($urls as $entry) {
                $url = (string) ($entry['url'] ?? '');
                if ($url === '') {
                    continue;
                }

                $urlIndexMap[$url] = [
                    'indexing_status' => (string) ($entry['status'] ?? $entry['indexing_status'] ?? ''),
                    'http_code'       => isset($entry['code']) ? (int) $entry['code'] : (isset($entry['http_code']) ? (int) $entry['http_code'] : null),
                    'crawl_date'      => (string) ($entry['date'] ?? $entry['crawl_date'] ?? ''),
                ];
            }

            $total = (int) ($page['count'] ?? 0);
            $offset += $limit;
        } while ($offset < $total && count($urls) === $limit);

        $saved = 0;

        foreach ($toolsByCategoryAlias as $toolSlug => $localeUrls) {
            foreach ($localeUrls as $locale => $fullUrl) {
                $match = $urlIndexMap[$fullUrl] ?? null;

                if ($match === null) {
                    continue;
                }

                $this->overviewRepo->upsertIndexation([
                    'tool_slug'       => $toolSlug,
                    'locale'          => $locale,
                    'url'             => $fullUrl,
                    'provider'        => 'yandex',
                    'indexing_status' => $match['indexing_status'] !== '' ? $match['indexing_status'] : null,
                    'http_code'       => $match['http_code'],
                    'crawl_date'      => $match['crawl_date'] !== '' ? $match['crawl_date'] : null,
                ]);

                $saved++;
            }
        }

        return $saved;
    }

    /**
     * Строит карту tool_slug → [locale => full_url] для всех инструментов.
     *
     * @param  string[] $locales Список локалей.
     * @return array<string, array<string, string>>
     */
    private function buildToolUrlMap(array $locales, string $appUrl, bool $isMultilang, string $defaultLocale): array
    {
        $rows = $this->overviewRepo->listPublishedWithCategoryAlias();

        $result = [];

        foreach ($rows as $row) {
            $toolSlug = (string) ($row['tool_slug'] ?? '');
            $catAlias = (string) ($row['category_alias'] ?? '');

            if ($toolSlug === '' || $catAlias === '') {
                continue;
            }

            foreach ($locales as $locale) {
                $prefix = ($isMultilang && $locale !== $defaultLocale) ? '/' . $locale : '';
                $result[$toolSlug][$locale] = $appUrl . $prefix . '/' . $catAlias . '/' . $toolSlug;
            }
        }

        return $result;
    }

    /**
     * Собирает финальный массив данных по каждому инструменту для шаблона.
     *
     * @param  array<int, array<string, mixed>>                                      $tools
     * @param  array<int, array<string, array<string, mixed>>>                       $completeness
     * @param  array<string, array<string, mixed>>                                   $rankStats
     * @param  array<string, array<string, array<string, mixed>>>                    $indexation
     * @param  string[]                                                              $locales
     * @return array<int, array<string, mixed>>
     */
    private function assembleToolsData(
        array $tools,
        array $completeness,
        array $rankStats,
        array $indexation,
        array $locales
    ): array {
        $result = [];

        foreach ($tools as $tool) {
            $id = (int) ($tool['id'] ?? 0);
            $slug = (string) ($tool['alias'] ?? '');

            $langData = [];
            foreach ($locales as $locale) {
                $langData[$locale] = $completeness[$id][$locale] ?? null;
            }

            $rank = $rankStats[$slug] ?? null;
            $idx = $indexation[$slug] ?? [];

            $result[] = [
                'id'              => $id,
                'alias'           => $slug,
                'published'       => (bool) ($tool['published'] ?? false),
                'calculation_mode' => (string) ($tool['calculation_mode'] ?? ''),
                'updated_at'      => (string) ($tool['updated_at'] ?? ''),
                'category_alias'  => (string) ($tool['category_alias'] ?? ''),
                'blocks_count'    => (int) ($tool['blocks_count'] ?? 0),
                'faq_count'       => (int) ($tool['faq_count'] ?? 0),
                'examples_count'  => (int) ($tool['examples_count'] ?? 0),
                'tags_count'      => (int) ($tool['tags_count'] ?? 0),
                'usage_30d'       => (int) ($tool['usage_30d'] ?? 0),
                'clusters_count'  => (int) ($tool['clusters_count'] ?? 0),
                'queries_count'   => (int) ($tool['queries_count'] ?? 0),
                'semantic_score'  => (int) ($tool['semantic_score'] ?? 0),
                'avg_position'    => $rank['avg_position'] ?? null,
                'total_impressions' => $rank['total_impressions'] ?? 0,
                'total_clicks'    => $rank['total_clicks'] ?? 0,
                'rank_checked_at' => $rank['last_checked_at'] ?? null,
                'languages'       => $langData,
                'indexation'      => $idx,
            ];
        }

        return $result;
    }

    /**
     * Вычисляет итоговую сводку по всем инструментам.
     *
     * @param  array<int, array<string, mixed>> $tools
     * @param  string[]                         $locales
     * @return array<string, mixed>
     */
    private function buildSummary(array $tools, array $locales): array
    {
        $total = count($tools);
        $published = 0;
        $withSemantic = 0;
        $withRank = 0;
        $withIndexation = 0;
        $missingTranslations = 0;

        foreach ($tools as $tool) {
            if ($tool['published']) {
                $published++;
            }
            if ((int) $tool['clusters_count'] > 0) {
                $withSemantic++;
            }
            if ($tool['avg_position'] !== null) {
                $withRank++;
            }
            if ($tool['indexation'] !== []) {
                $withIndexation++;
            }

            foreach ($locales as $locale) {
                if ($tool['languages'][$locale] === null) {
                    $missingTranslations++;
                    break;
                }
            }
        }

        return [
            'total'               => $total,
            'published'           => $published,
            'with_semantic'       => $withSemantic,
            'with_rank'           => $withRank,
            'with_indexation'     => $withIndexation,
            'missing_translations' => $missingTranslations,
        ];
    }

    /**
     * Возвращает список доступных локалей.
     *
     * @return string[]
     */
    private function locales(): array
    {
        return array_values(array_filter(
            array_map(
                static fn (mixed $l): string => mb_strtolower(trim((string) $l)),
                (array) config('locale.locales', [])
            ),
            static fn (string $l): bool => $l !== ''
        ));
    }

}
