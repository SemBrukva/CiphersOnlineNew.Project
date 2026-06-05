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
     * Возвращает XSL-стилизацию для браузерного просмотра XML-карты сайта.
     */
    public function xsl(Request $request): Response
    {
        return new Response($this->buildXsl(), 200, [
            'Content-Type' => 'text/xsl; charset=utf-8',
        ]);
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
        $w->writePi('xml-stylesheet', 'type="text/xsl" href="/sitemap.xsl"');

        $w->startElement('urlset');
        $w->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
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

    /**
     * Строит XSL-документ для человекочитаемого просмотра sitemap в браузере.
     */
    private function buildXsl(): string
    {
        return <<<'XSL'
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <xsl:output method="html" encoding="UTF-8" indent="yes"/>
    <xsl:template match="/">
        <html lang="en">
            <head>
                <meta charset="UTF-8"/>
                <title>XML Sitemap</title>
                <style>
                    :root {
                        color-scheme: light;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                        color: #172033;
                        background: #f6f8fb;
                    }

                    body {
                        margin: 0;
                        padding: 32px;
                    }

                    main {
                        max-width: 1180px;
                        margin: 0 auto;
                    }

                    h1 {
                        margin: 0 0 8px;
                        font-size: 28px;
                        line-height: 1.2;
                    }

                    p {
                        margin: 0 0 24px;
                        color: #647084;
                    }

                    table {
                        width: 100%;
                        border-collapse: collapse;
                        background: #fff;
                        border: 1px solid #d9e0ea;
                    }

                    th,
                    td {
                        padding: 10px 12px;
                        border-bottom: 1px solid #e6ebf2;
                        text-align: left;
                        vertical-align: top;
                    }

                    th {
                        font-size: 12px;
                        text-transform: uppercase;
                        letter-spacing: .04em;
                        color: #526078;
                        background: #eef2f7;
                    }

                    a {
                        color: #0b63ce;
                        text-decoration: none;
                        overflow-wrap: anywhere;
                    }

                    a:hover {
                        text-decoration: underline;
                    }

                    .meta {
                        white-space: nowrap;
                        color: #526078;
                    }

                    @media (max-width: 760px) {
                        body {
                            padding: 18px;
                        }

                        table,
                        thead,
                        tbody,
                        tr,
                        th,
                        td {
                            display: block;
                        }

                        thead {
                            display: none;
                        }

                        tr {
                            border-bottom: 1px solid #d9e0ea;
                        }

                        td {
                            border-bottom: 0;
                        }
                    }
                </style>
            </head>
            <body>
                <main>
                    <h1>XML Sitemap</h1>
                    <p>
                        <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/>
                        URLs listed for search engines.
                    </p>
                    <table>
                        <thead>
                            <tr>
                                <th>URL</th>
                                <th>Last modified</th>
                                <th>Change frequency</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            <xsl:for-each select="sitemap:urlset/sitemap:url">
                                <tr>
                                    <td>
                                        <a href="{sitemap:loc}">
                                            <xsl:value-of select="sitemap:loc"/>
                                        </a>
                                    </td>
                                    <td class="meta">
                                        <xsl:value-of select="sitemap:lastmod"/>
                                    </td>
                                    <td class="meta">
                                        <xsl:value-of select="sitemap:changefreq"/>
                                    </td>
                                    <td class="meta">
                                        <xsl:value-of select="sitemap:priority"/>
                                    </td>
                                </tr>
                            </xsl:for-each>
                        </tbody>
                    </table>
                </main>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
XSL;
    }
}
