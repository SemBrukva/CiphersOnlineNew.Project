<?php

declare(strict_types=1);

namespace App\Glossary;

use App\Cache\CacheInterface;

/**
 * Файловый репозиторий терминов глоссария.
 *
 * Источник истины — JSON-файлы в storage/content/glossary/{slug}/{locale}.json.
 * БД не используется: чтение файлов кэшируется через CacheInterface. Публикуются
 * только термины со статусом «published»; черновики (draft) недоступны публично.
 */
final class GlossaryRepository
{
    /** Время жизни кэша записей и индекса (сек). */
    private const int CACHE_TTL = 3600;

    /** Порядок вывода категорий на странице-индексе. */
    public const array CATEGORY_ORDER = ['concepts', 'classical', 'cryptanalysis', 'encoding', 'hashing'];

    /** Абсолютный путь к каталогу с контентом глоссария. */
    private string $basePath;

    /**
     * Создаёт репозиторий.
     */
    public function __construct(private readonly CacheInterface $cache)
    {
        $this->basePath = STORAGE_PATH . '/content/glossary';
    }

    /**
     * Возвращает опубликованный термин для локали или null (черновик / отсутствие файла = null, без fallback).
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
     * Возвращает индекс опубликованных терминов для локали (краткие карточки), отсортированный по названию.
     *
     * @return array<int, array{slug: string, name: string, short: string, category: string, url: string}>
     */
    public function index(string $locale): array
    {
        /** @var array<int, array{slug: string, name: string, short: string, category: string, url: string}> $result */
        $result = $this->cache->remember(
            "glossary.index.{$locale}",
            self::CACHE_TTL,
            function () use ($locale): array {
                $terms = [];

                foreach ($this->allSlugs() as $slug) {
                    $data = $this->find($slug, $locale);
                    if ($data === null) {
                        continue;
                    }

                    $terms[] = [
                        'slug'     => $slug,
                        'name'     => (string) ($data['term']['name'] ?? $slug),
                        'short'    => (string) ($data['term']['short'] ?? ''),
                        'category' => (string) ($data['category'] ?? 'concepts'),
                        'url'      => locale_url('/glossary/' . $slug),
                    ];
                }

                usort($terms, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

                return $terms;
            }
        );

        return $result;
    }

    /**
     * Возвращает slug'и терминов, опубликованных в указанной локали (по умолчанию — базовой).
     * Используется для карты сайта.
     *
     * @return string[]
     */
    public function publishedSlugs(?string $locale = null): array
    {
        $locale ??= (string) config('locale.locale', 'en');

        return array_map(
            static fn (array $term): string => $term['slug'],
            $this->index($locale)
        );
    }

    /**
     * Проверяет наличие термина (в любом статусе) хотя бы в одной локали.
     */
    public function exists(string $slug): bool
    {
        if (!$this->isValidSlug($slug)) {
            return false;
        }

        return is_dir($this->basePath . '/' . $slug);
    }

    /**
     * Читает и декодирует файл термина для локали (сырые данные, без фильтра статуса), кэширует результат.
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
            "glossary.raw.{$slug}.{$locale}",
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
     * Возвращает список slug'ов всех терминов (имена подкаталогов).
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
