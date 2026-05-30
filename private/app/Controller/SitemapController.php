<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cache\CacheInterface;
use App\Http\Request;
use App\Http\Response;
use App\I18n\Translator;
use App\Repository\CipherCategoryRepository;
use App\Repository\CipherRepository;
use App\View\View;
use XMLWriter;

/**
 * Контроллер карты сайта.
 *
 * Генерирует HTML-карту (категории и инструменты для текущей локали)
 * и XML-карту (категории и инструменты для всех языков).
 */
final readonly class SitemapController
{
    private const int CACHE_TTL = 3600;

    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private View                     $view,
        private CipherCategoryRepository $categories,
        private CipherRepository         $ciphers,
        private Translator               $translator,
        private CacheInterface           $cache,
    ) {
    }

    /**
     * Отображает HTML-карту сайта для текущей локали.
     */
    public function html(Request $request): Response
    {
        $language        = locale();
        $defaultLanguage = (string) config('locale.locale', 'en');

        $publishedCategories = $this->categories->findPublishedCategoriesForHome($language, $defaultLanguage);

        $categoriesWithTools = [];
        foreach ($publishedCategories as $category) {
            $tools = $this->ciphers->findPublishedByCategoryWithTranslation(
                (int) $category['id'],
                $language,
                $defaultLanguage,
            );

            if ($tools !== []) {
                $category['tools'] = $tools;
                $categoriesWithTools[] = $category;
            }
        }

        $this->view
            ->setTitle(trans('SITEMAP_TITLE'))
            ->setMeta(trans('SITEMAP_META'))
            ->setBreadcrumbs([
                ['label' => trans('SITEMAP_TITLE')],
            ])
            ->setContent($this->view->fetch('sitemap/html.tpl', [
                'categories' => $categoriesWithTools,
            ]));

        return new Response($this->view->render());
    }

    /**
     * Возвращает XML-карту сайта для всех активных языков.
     *
     * Включает страницы категорий и инструментов по всем языкам с lastmod и xhtml:link.
     */
    public function xml(Request $request): Response
    {
        $appUrl    = rtrim(config('app.url', ''), '/');
        $multilang = $this->translator->isMultilang();
        $locales   = $this->translator->getLocales();
        $default   = $this->translator->getDefaultLocale();

        $paths = $this->cache->remember('sitemap.xml.paths', self::CACHE_TTL, function (): array {
            $grouped    = $this->ciphers->listPublishedForSitemap();
            $categories = $this->categories->listPublishedForSitemap();
            $today      = date('Y-m-d');
            $paths      = [];

            foreach ($categories as $category) {
                $catAlias = (string) $category['alias'];
                $lastmod  = $this->formatLastmod($category['updated_at'], $today);

                $paths[] = [
                    'path'       => '/' . $catAlias,
                    'priority'   => '0.8',
                    'changefreq' => 'weekly',
                    'lastmod'    => $lastmod,
                ];

                foreach ($grouped[$catAlias] ?? [] as $tool) {
                    $paths[] = [
                        'path'       => '/' . $catAlias . '/' . $tool['alias'],
                        'priority'   => '0.9',
                        'changefreq' => 'monthly',
                        'lastmod'    => $this->formatLastmod($tool['updated_at'], $today),
                    ];
                }
            }

            return $paths;
        });

        return Response::xml($this->buildXml($appUrl, $paths, $multilang, $locales, $default));
    }

    /**
     * Форматирует дату lastmod из datetime-строки в формат Y-m-d.
     *
     * @param string|null $datetime Значение поля updated_at из БД.
     * @param string      $fallback Дата по умолчанию, если поле пустое.
     */
    private function formatLastmod(?string $datetime, string $fallback): string
    {
        if ($datetime === null || $datetime === '') {
            return $fallback;
        }

        return substr($datetime, 0, 10);
    }

    /**
     * Строит XML-содержимое карты сайта через XMLWriter.
     *
     * При мультиязычности каждый путь раскрывается в N записей (по числу локалей)
     * с xhtml:link-альтернативами во всех записях.
     *
     * @param array<int, array{path: string, priority: string, changefreq: string, lastmod: string}> $paths
     * @param string[] $locales
     */
    private function buildXml(
        string $appUrl,
        array  $paths,
        bool   $multilang,
        array  $locales,
        string $default
    ): string {
        $withAlternates = $multilang && count($locales) > 1;

        $w = new XMLWriter();
        $w->openMemory();
        $w->setIndent(true);
        $w->setIndentString('    ');
        $w->startDocument('1.0', 'UTF-8');

        $w->startElement('urlset');
        $w->writeAttribute('xmlns', 'https://www.sitemaps.org/schemas/sitemap/0.9');
        if ($withAlternates) {
            $w->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        }

        foreach ($paths as ['path' => $path, 'priority' => $priority, 'changefreq' => $changefreq, 'lastmod' => $lastmod]) {
            $localeList = $withAlternates ? $locales : [$default];

            foreach ($localeList as $locale) {
                $localePath = ($locale !== $default ? '/' . $locale : '') . $path;

                $w->startElement('url');

                $w->writeElement('loc', $appUrl . $localePath);
                $w->writeElement('lastmod', $lastmod);

                if ($withAlternates) {
                    foreach ($locales as $altLocale) {
                        $altPath = ($altLocale !== $default ? '/' . $altLocale : '') . $path;
                        $w->startElement('xhtml:link');
                        $w->writeAttribute('rel', 'alternate');
                        $w->writeAttribute('hreflang', $altLocale);
                        $w->writeAttribute('href', $appUrl . $altPath);
                        $w->endElement();
                    }
                }

                $w->writeElement('changefreq', $changefreq);
                $w->writeElement('priority', $priority);

                $w->endElement(); // url
            }
        }

        $w->endElement(); // urlset
        $w->endDocument();

        return $w->outputMemory();
    }
}
