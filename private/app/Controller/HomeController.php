<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cache\CacheInterface;
use App\Http\Request;
use App\Http\Response;
use App\Repository\CipherCategoryRepository;
use App\Repository\CipherRepository;
use App\View\View;

/**
 * Контроллер главной страницы CiphersOnline.
 *
 * Собирает категории, популярные инструменты, недавние инструменты и
 * сценарии использования для отображения hub-страницы каталога.
 */
final readonly class HomeController
{
    /** @var string[] Алиасы инструментов в hero-блоке быстрого доступа. */
    private const array QUICK_ACCESS_ALIASES = [
        'base64',
        'jwt-decoder',
        'caesar',
        'hex',
        'url-encode',
        'binary-converter',
    ];

    /** @var string[] Алиасы инструментов в секции «Популярные инструменты». */
    private const array POPULAR_ALIASES = [
        'base64',
        'jwt-decoder',
        'caesar',
        'url-encode',
        'hex',
        'binary-converter',
    ];

    /** @var int Количество последних инструментов в секции «Каталог расширяется». */
    private const int RECENT_LIMIT = 3;

    /** @var int TTL кеша данных главной страницы в секундах (30 минут). */
    private const int CACHE_TTL = 1800;

    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private View $view,
        private CipherCategoryRepository $categories,
        private CipherRepository $ciphers,
        private CacheInterface $cache,
    ) {
    }

    /**
     * Отображает главную страницу с каталогом инструментов и категорий.
     */
    public function index(Request $request): Response
    {
        $language = locale();
        $defaultLanguage = (string) config('locale.locale', 'en');

        /** @var array{categories_with_tools: array<mixed>, popular_tools: array<mixed>, recent_tools: array<mixed>, quick_access_tools: array<mixed>} $cached */
        $cached = $this->cache->remember("home:{$language}", self::CACHE_TTL, function () use ($language, $defaultLanguage): array {
            $publishedCategories = $this->categories->findPublishedCategoriesForHome($language, $defaultLanguage);

            $categoriesWithTools = [];
            foreach ($publishedCategories as $category) {
                $tools = $this->ciphers->findPublishedByCategoryWithTranslation(
                    (int) $category['id'],
                    $language,
                    $defaultLanguage,
                );
                $category['tools'] = array_slice($tools, 0, 4);
                $category['tools_count'] = count($tools);
                $categoriesWithTools[] = $category;
            }

            $popularTools = $this->ciphers->findPublishedByAliasesWithTranslation(
                self::POPULAR_ALIASES,
                $language,
                $defaultLanguage,
            );
            $popularIds = array_map(static fn (array $t): int => (int) $t['id'], $popularTools);
            $popularTags = $this->ciphers->findTagsGroupedByCipherIds($popularIds, $language, $defaultLanguage);
            foreach ($popularTools as &$tool) {
                $tool['tags'] = $popularTags[(int) $tool['id']] ?? [];
            }
            unset($tool);

            $recentTools = $this->ciphers->findLatestPublishedWithTranslation(
                self::RECENT_LIMIT,
                $language,
                $defaultLanguage,
            );

            $quickAccessTools = $this->ciphers->findPublishedByAliasesWithTranslation(
                self::QUICK_ACCESS_ALIASES,
                $language,
                $defaultLanguage,
            );

            return [
                'categories_with_tools' => $categoriesWithTools,
                'popular_tools'         => $popularTools,
                'recent_tools'          => $recentTools,
                'quick_access_tools'    => $quickAccessTools,
            ];
        });

        $useCases = $this->buildUseCases($cached['quick_access_tools']);
        $plannedCategories = $this->buildPlannedCategories();

        $this->view
            ->setTitle(trans('HOME_TITLE'))
            ->setMeta(trans('HOME_META_DESCRIPTION'))
            ->setContent($this->view->fetch('home/index.tpl', [
                ...$cached,
                'planned_categories' => $plannedCategories,
                'use_cases'          => $useCases,
            ]));

        return new Response($this->view->render());
    }

    /**
     * Строит список сценариев использования с привязкой к инструментам.
     *
     * @param  array<int, array<string, mixed>> $tools Список инструментов с category_alias.
     * @return array<int, array{title:string, description:string, tool_label:string, url:string}>
     */
    private function buildUseCases(array $tools): array
    {
        $byAlias = [];
        foreach ($tools as $tool) {
            $byAlias[(string) $tool['alias']] = $tool;
        }

        $map = [
            ['alias' => 'jwt-decoder',      'key' => 'HOME_USE_CASE_JWT'],
            ['alias' => 'base64',           'key' => 'HOME_USE_CASE_BASE64'],
            ['alias' => 'caesar',           'key' => 'HOME_USE_CASE_CAESAR'],
            ['alias' => 'binary-converter', 'key' => 'HOME_USE_CASE_BINARY'],
            ['alias' => 'url-encode',       'key' => 'HOME_USE_CASE_URL'],
            ['alias' => 'hex',              'key' => 'HOME_USE_CASE_HEX'],
        ];

        $cases = [];
        foreach ($map as $item) {
            if (!isset($byAlias[$item['alias']])) {
                continue;
            }
            $tool = $byAlias[$item['alias']];
            $cases[] = [
                'title' => trans($item['key'] . '_TITLE'),
                'description' => trans($item['key'] . '_DESC'),
                'tool_label' => (string) $tool['name_short'],
                'url' => '/' . $tool['category_alias'] . '/' . $tool['alias'],
            ];
        }

        return $cases;
    }

    /**
     * Возвращает «coming soon» категории, ещё не представленные в БД.
     *
     * @return array<int, array{name:string, description:string, icon:string}>
     */
    private function buildPlannedCategories(): array
    {
        return [
            [
                'name' => trans('HOME_CATEGORY_HASHING_NAME'),
                'description' => trans('HOME_CATEGORY_HASHING_DESC'),
                'icon' => 'bi-fingerprint',
            ],
            [
                'name' => trans('HOME_CATEGORY_CRYPTANALYSIS_NAME'),
                'description' => trans('HOME_CATEGORY_CRYPTANALYSIS_DESC'),
                'icon' => 'bi-graph-up',
            ],
        ];
    }
}
