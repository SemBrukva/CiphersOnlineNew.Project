<?php

declare(strict_types=1);

namespace App\Queue\Jobs;

use App\Container\Container;
use App\Queue\ContainerAwareJobInterface;
use App\Repository\ToolsOverviewRepository;
use App\Yandex\WebmasterClient;

/**
 * Задача очереди для обновления статусов индексации инструментов через Яндекс Вебмастер API.
 *
 * Обходит /indexing/samples постранично, сопоставляет URL инструментов
 * с найденными страницами и сохраняет статус INDEXED / NOT_INDEXED в БД.
 */
final class RefreshIndexationJob implements ContainerAwareJobInterface
{
    private ?Container $container = null;

    /** @var string[] Список локалей для построения URL. */
    private array $locales;

    /** Базовый URL сайта без trailing slash. */
    private string $appUrl;

    /** Использует ли сайт мультиязычные URL-префиксы. */
    private bool $isMultilang;

    /** Локаль по умолчанию (не получает префикс в URL). */
    private string $defaultLocale;

    /**
     * Создаёт задачу обновления индексации.
     *
     * @param string[] $locales       Список активных локалей.
     * @param string   $appUrl        Базовый URL сайта.
     * @param bool     $isMultilang   Включён ли мультиязычный режим.
     * @param string   $defaultLocale Локаль по умолчанию.
     */
    public function __construct(
        array $locales,
        string $appUrl,
        bool $isMultilang,
        string $defaultLocale
    ) {
        $this->locales = $locales;
        $this->appUrl = $appUrl;
        $this->isMultilang = $isMultilang;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Сохраняет контейнер для получения зависимостей в handle().
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Выполняет обновление индексации: запрашивает семплы, сопоставляет, сохраняет.
     */
    public function handle(): void
    {
        if ($this->container === null) {
            throw new \RuntimeException('RefreshIndexationJob требует контейнер; запускайте через воркер.');
        }

        /** @var WebmasterClient $webmaster */
        $webmaster = $this->container->get(WebmasterClient::class);

        /** @var ToolsOverviewRepository $repo */
        $repo = $this->container->get(ToolsOverviewRepository::class);

        $urlToTool = $this->buildUrlToToolMap($repo);

        // Постранично собираем все проиндексированные URL из Яндекса
        $offset = 0;
        $limit = 100;
        $indexedUrls = [];

        do {
            $page = $webmaster->indexingSamples($limit, $offset);
            $samples = is_array($page['samples'] ?? null) ? $page['samples'] : [];

            foreach ($samples as $sample) {
                $rawUrl = (string) ($sample['url'] ?? '');
                if ($rawUrl === '') {
                    continue;
                }

                $normalized = rtrim($rawUrl, '/');
                $indexedUrls[$normalized] = [
                    'http_code'  => isset($sample['http_code']) ? (int) $sample['http_code'] : null,
                    'crawl_date' => (string) ($sample['ycrawler_show_date'] ?? $sample['crawl_date'] ?? ''),
                ];
            }

            $total = (int) ($page['count_total'] ?? $page['count'] ?? 0);
            $offset += $limit;
        } while ($offset < $total && count($samples) === $limit);

        // Для каждого инструмента × локаль — сохраняем статус
        foreach ($urlToTool as $fullUrl => $toolInfo) {
            $normalized = rtrim($fullUrl, '/');
            $match = $indexedUrls[$normalized] ?? null;

            $repo->upsertIndexation([
                'tool_slug'       => $toolInfo['tool_slug'],
                'locale'          => $toolInfo['locale'],
                'url'             => $fullUrl,
                'provider'        => 'yandex',
                'indexing_status' => $match !== null ? 'INDEXED' : 'NOT_INDEXED',
                'http_code'       => $match['http_code'] ?? null,
                'crawl_date'      => ($match['crawl_date'] ?? '') !== '' ? $match['crawl_date'] : null,
            ]);
        }
    }

    /**
     * Строит обратный словарь full_url → [tool_slug, locale].
     *
     * @return array<string, array{tool_slug: string, locale: string}>
     */
    private function buildUrlToToolMap(ToolsOverviewRepository $repo): array
    {
        $rows = $repo->listPublishedWithCategoryAlias();
        $result = [];

        foreach ($rows as $row) {
            $toolSlug = (string) ($row['tool_slug'] ?? '');
            $catAlias = (string) ($row['category_alias'] ?? '');

            if ($toolSlug === '' || $catAlias === '') {
                continue;
            }

            foreach ($this->locales as $locale) {
                $prefix = ($this->isMultilang && $locale !== $this->defaultLocale) ? '/' . $locale : '';
                $fullUrl = $this->appUrl . $prefix . '/' . $catAlias . '/' . $toolSlug;
                $result[$fullUrl] = ['tool_slug' => $toolSlug, 'locale' => $locale];
            }
        }

        return $result;
    }

    /**
     * Поля для сериализации (без контейнера).
     *
     * @return string[]
     */
    public function __sleep(): array
    {
        return ['locales', 'appUrl', 'isMultilang', 'defaultLocale'];
    }
}
