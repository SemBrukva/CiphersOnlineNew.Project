<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\I18n\Translator;
use App\Repository\SystemPageRepository;
use App\View\View;
use XMLWriter;

/**
 * Контроллер карты сайта.
 *
 * Генерирует HTML-карту (для текущей локали) и XML-карту (для всех языков).
 */
final readonly class SitemapController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private View       $view,
        private SystemPageRepository $pages,
        private Translator $translator
    ) {
    }

    /**
     * Отображает HTML-карту сайта для текущей локали.
     */
    public function html(Request $request): Response
    {
        $pages = $this->loadPages();

        $this->view
            ->setTitle(trans('SITEMAP_TITLE'))
            ->setContent($this->view->fetch('sitemap/html.tpl', [
                'pages' => $pages,
            ]));

        return new Response($this->view->render());
    }

    /**
     * Возвращает XML-карту сайта для всех активных языков.
     *
     * Формат: Sitemap Protocol 0.9 с xhtml:link для мультиязычности.
     */
    public function xml(Request $request): Response
    {
        $appUrl    = rtrim(config('app.url', ''), '/');
        $multilang = $this->translator->isMultilang();
        $locales   = $this->translator->getLocales();
        $default   = $this->translator->getDefaultLocale();
        $pages     = $this->loadPages();

        $paths = [
            ['path' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['path' => '/contacts', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ];

        foreach ($pages as $page) {
            $paths[] = [
                'path'       => '/page/' . $page['alias'],
                'priority'   => '0.7',
                'changefreq' => 'monthly',
            ];
        }

        return Response::xml($this->buildXml($appUrl, $paths, $multilang, $locales, $default));
    }

    /**
     * Строит XML-содержимое карты сайта через XMLWriter.
     *
     * Экранирование и форматирование делегируются XMLWriter.
     * При мультиязычности каждый путь раскрывается в N записей (по числу локалей)
     * с xhtml:link-альтернативами во всех записях.
     *
     * @param array<int, array{path: string, priority: string, changefreq: string}> $paths
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

        foreach ($paths as ['path' => $path, 'priority' => $priority, 'changefreq' => $changefreq]) {
            $localeList = $withAlternates ? $locales : [$default];

            foreach ($localeList as $locale) {
                $localePath = ($locale !== $default ? '/' . $locale : '') . $path;

                $w->startElement('url');

                $w->writeElement('loc', $appUrl . $localePath);

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
     * Загружает опубликованные системные страницы из базы данных.
     *
     * @return array<int, array{alias: string, name: string}>
     */
    private function loadPages(): array
    {
        try {
            return $this->pages->listPublishedForNavigation();
        } catch (\Throwable) {
            return [];
        }
    }
}
