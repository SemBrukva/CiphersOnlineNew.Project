<?php

declare(strict_types=1);

namespace App\Guide;

use App\Cache\CacheInterface;

/**
 * Файловый репозиторий гайдов и статей.
 *
 * Источник истины — JSON-файлы в storage/content/guides/{slug}/{locale}.json.
 * БД не используется: чтение файлов кэшируется через CacheInterface. Публикуются
 * только статьи со статусом «published»; черновики (draft) недоступны публично.
 * По устройству повторяет App\Glossary\GlossaryRepository.
 */
final class GuideRepository
{
    /** Время жизни кэша записей и индекса (сек). */
    private const int CACHE_TTL = 3600;

    /** Порядок вывода категорий на странице-индексе. */
    public const array CATEGORY_ORDER = ['how-to', 'deep-dive', 'history', 'lists'];

    /** Абсолютный путь к каталогу с контентом гайдов. */
    private string $basePath;

    /**
     * Создаёт репозиторий.
     */
    public function __construct(private readonly CacheInterface $cache)
    {
        $this->basePath = STORAGE_PATH . '/content/guides';
    }

    /**
     * Возвращает опубликованную статью для локали или null (черновик / отсутствие файла = null, без fallback).
     *
     * @return array<string, mixed>|null
     */
    public function find(string $slug, string $locale): ?array
    {
        $data = $this->readRaw($slug, $locale);

        if ($data === null) {
            return null;
        }

        if ((string) ($data['meta']['status'] ?? '') !== 'published') {
            return null;
        }

        return $data;
    }

    /**
     * Возвращает индекс опубликованных статей для локали (краткие карточки), отсортированный по дате публикации (свежие сверху).
     *
     * @return array<int, array{slug: string, title: string, excerpt: string, category: string, reading_time: int, updated_at: string, url: string}>
     */
    public function index(string $locale): array
    {
        /** @var array<int, array{slug: string, title: string, excerpt: string, category: string, reading_time: int, updated_at: string, url: string}> $result */
        $result = $this->cache->remember(
            "guides.index.{$locale}",
            self::CACHE_TTL,
            function () use ($locale): array {
                $guides = [];

                foreach ($this->allSlugs() as $slug) {
                    $data = $this->find($slug, $locale);
                    if ($data === null) {
                        continue;
                    }

                    $guide = (array) ($data['guide'] ?? []);
                    $meta  = (array) ($data['meta'] ?? []);

                    $guides[] = [
                        'slug'         => $slug,
                        'title'        => (string) ($guide['title'] ?? $slug),
                        'excerpt'      => (string) ($guide['excerpt'] ?? ''),
                        'category'     => (string) ($data['category'] ?? 'how-to'),
                        'reading_time' => (int) ($guide['reading_time'] ?? 0),
                        'updated_at'   => (string) ($meta['published_at'] ?? $meta['updated_at'] ?? ''),
                        'url'          => locale_url('/guides/' . $slug),
                    ];
                }

                // Свежие публикации — выше; при равенстве дат стабилизируем по заголовку.
                usort($guides, static function (array $a, array $b): int {
                    return strcmp($b['updated_at'], $a['updated_at'])
                        ?: strcmp($a['title'], $b['title']);
                });

                return $guides;
            }
        );

        return $result;
    }

    /**
     * Возвращает slug'и статей, опубликованных в указанной локали (по умолчанию — базовой).
     * Используется для карты сайта.
     *
     * @return string[]
     */
    public function publishedSlugs(?string $locale = null): array
    {
        $locale ??= (string) config('locale.locale', 'en');

        return array_map(
            static fn (array $guide): string => $guide['slug'],
            $this->index($locale)
        );
    }

    /**
     * Проверяет наличие статьи (в любом статусе) хотя бы в одной локали.
     */
    public function exists(string $slug): bool
    {
        if (!$this->isValidSlug($slug)) {
            return false;
        }

        return is_dir($this->basePath . '/' . $slug);
    }

    /**
     * Читает и декодирует файл статьи для локали (сырые данные, без фильтра статуса), кэширует результат.
     *
     * @return array<string, mixed>|null
     */
    private function readRaw(string $slug, string $locale): ?array
    {
        if (!$this->isValidSlug($slug) || !$this->isValidLocale($locale)) {
            return null;
        }

        /** @var array<string, mixed>|null $data */
        $data = $this->cache->remember(
            "guides.raw.{$slug}.{$locale}",
            self::CACHE_TTL,
            function () use ($slug, $locale): ?array {
                $file = $this->basePath . '/' . $slug . '/' . $locale . '.json';
                if (!is_file($file)) {
                    return null;
                }

                $decoded = json_decode((string) file_get_contents($file), true);

                return is_array($decoded) ? $decoded : null;
            }
        );

        return $data;
    }

    /**
     * Возвращает список slug'ов всех статей (имена подкаталогов).
     *
     * @return string[]
     */
    private function allSlugs(): array
    {
        if (!is_dir($this->basePath)) {
            return [];
        }

        $dirs = glob($this->basePath . '/*', GLOB_ONLYDIR) ?: [];

        return array_values(array_filter(
            array_map(static fn (string $dir): string => basename($dir), $dirs),
            fn (string $slug): bool => $this->isValidSlug($slug)
        ));
    }

    /**
     * Валидирует slug (защита от обхода каталога).
     */
    private function isValidSlug(string $slug): bool
    {
        return $slug !== '' && preg_match('/^[a-z0-9-]+$/', $slug) === 1;
    }

    /**
     * Проверяет, что локаль входит в список поддерживаемых.
     */
    private function isValidLocale(string $locale): bool
    {
        return in_array($locale, (array) config('locale.locales', []), true);
    }
}
