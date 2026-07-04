<?php

declare(strict_types=1);

namespace App\Controller;

use App\Guide\GuideRepository;
use App\Http\Request;
use App\Http\Response;
use App\View\View;

/**
 * Контроллер публичных гайдов и статей (файловый контент, без БД).
 */
final readonly class GuidesController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private View $view,
        private GuideRepository $guides
    ) {
    }

    /**
     * Отображает страницу-индекс гайдов: статьи, сгруппированные по категориям.
     */
    public function index(Request $request): Response
    {
        $locale = locale();
        $guides = $this->guides->index($locale);

        $grouped = [];
        foreach ($guides as $guide) {
            $grouped[$guide['category']][] = $guide;
        }

        $sections = [];
        foreach (GuideRepository::CATEGORY_ORDER as $category) {
            if (!empty($grouped[$category])) {
                $sections[] = [
                    'key'    => $category,
                    'label'  => trans('GUIDES_CAT_' . strtoupper(str_replace('-', '_', $category))),
                    'guides' => $grouped[$category],
                ];
                unset($grouped[$category]);
            }
        }

        // Категории вне заранее заданного порядка — добавляем в конец.
        foreach ($grouped as $category => $list) {
            $sections[] = ['key' => $category, 'label' => ucfirst((string) $category), 'guides' => $list];
        }

        $this->view
            ->setTitle(trans('GUIDES_INDEX_TITLE'))
            ->setMeta(trans('GUIDES_INDEX_META'))
            ->setBreadcrumbs([['label' => trans('GUIDES_BREADCRUMB')]])
            ->setStructuredData($this->buildIndexStructuredData($guides))
            ->setContent($this->view->fetch('guides/index.tpl', [
                'sections' => $sections,
                'total'    => count($guides),
            ]));

        return new Response($this->view->render());
    }

    /**
     * Отображает страницу статьи.
     */
    public function show(Request $request): Response
    {
        $slug   = (string) $request->route('slug', '');
        $locale = locale();

        $data = $this->guides->find($slug, $locale);

        if ($data === null) {
            $this->view
                ->setTitle(trans('ERROR_404_TITLE'))
                ->setContent($this->view->fetch('errors/404.tpl'));

            return new Response($this->view->render(), 404);
        }

        $guide = (array) ($data['guide'] ?? []);
        $meta  = (array) ($data['meta'] ?? []);
        $title = (string) ($guide['title'] ?? $slug);

        $body = (array) ($data['body'] ?? []);
        usort($body, static fn (array $a, array $b): int => ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)));

        $faq           = (array) ($data['faq'] ?? []);
        $relatedGuides = $this->resolveRelatedGuides((array) ($data['related_guides'] ?? []), $locale);
        $relatedTools  = $this->resolveRelatedTools((array) ($data['related_tools'] ?? []));
        $relatedTerms  = $this->resolveRelatedTerms((array) ($data['related_terms'] ?? []));

        $metaTitle       = (string) ($guide['meta_title'] ?? '') ?: $title;
        $metaDescription = (string) ($guide['meta_description'] ?? '') ?: (string) ($guide['excerpt'] ?? '');

        $publishedAt = (string) ($meta['published_at'] ?? $meta['updated_at'] ?? '');

        $this->view
            ->setTitle($metaTitle)
            ->setMeta($metaDescription)
            ->setBreadcrumbs([
                ['label' => trans('GUIDES_BREADCRUMB'), 'url' => locale_url('/guides')],
                ['label' => $title],
            ])
            ->setStructuredData($this->buildArticleStructuredData($slug, $guide, $meta, $faq))
            ->setContent($this->view->fetch('guides/show.tpl', [
                'guide'          => $guide,
                'category_label' => trans('GUIDES_CAT_' . strtoupper(str_replace('-', '_', (string) ($data['category'] ?? 'how-to')))),
                'published_at'   => $publishedAt,
                'published_at_display' => $this->formatDate($publishedAt, $locale),
                'body'           => $body,
                'faq'            => $faq,
                'related_guides' => $relatedGuides,
                'related_tools'  => $relatedTools,
                'related_terms'  => $relatedTerms,
            ]));

        return new Response($this->view->render());
    }

    /**
     * Форматирует дату `Y-m-d` в локализованный «длинный» вид для текущей локали
     * (напр. en «July 4, 2026», ru «4 июля 2026 г.», de «4. Juli 2026»).
     * При некорректной дате или отсутствии ext-intl возвращает исходную строку.
     */
    private function formatDate(string $date, string $locale): string
    {
        if ($date === '') {
            return '';
        }

        $timestamp = strtotime($date);
        if ($timestamp === false || !class_exists(\IntlDateFormatter::class)) {
            return $date;
        }

        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE
        );

        $formatted = $formatter->format($timestamp);

        return $formatted !== false ? $formatted : $date;
    }

    /**
     * Резолвит slug'и связанных статей в пары {title, url} по кэшированному индексу; отсутствующие пропускает.
     *
     * @param  array<int, mixed> $slugs
     * @return array<int, array{title: string, url: string}>
     */
    private function resolveRelatedGuides(array $slugs, string $locale): array
    {
        if ($slugs === []) {
            return [];
        }

        $bySlug = [];
        foreach ($this->guides->index($locale) as $guide) {
            $bySlug[$guide['slug']] = $guide;
        }

        $result = [];
        foreach ($slugs as $slug) {
            $slug = (string) $slug;
            if (isset($bySlug[$slug])) {
                $result[] = ['title' => $bySlug[$slug]['title'], 'url' => $bySlug[$slug]['url']];
            }
        }

        return $result;
    }

    /**
     * Преобразует связанные инструменты {slug, label} в {label, url}. URL строится по slug без обращения к БД.
     *
     * @param  array<int, mixed> $tools
     * @return array<int, array{label: string, url: string}>
     */
    private function resolveRelatedTools(array $tools): array
    {
        $result = [];
        foreach ($tools as $tool) {
            if (!is_array($tool)) {
                continue;
            }
            $slug  = (string) ($tool['slug'] ?? '');
            $label = (string) ($tool['label'] ?? '');
            if ($slug === '' || $label === '') {
                continue;
            }
            $result[] = ['label' => $label, 'url' => locale_url('/' . ltrim($slug, '/'))];
        }

        return $result;
    }

    /**
     * Преобразует slug'и связанных терминов глоссария в пары {label, url}.
     *
     * @param  array<int, mixed> $terms
     * @return array<int, array{label: string, url: string}>
     */
    private function resolveRelatedTerms(array $terms): array
    {
        $result = [];
        foreach ($terms as $term) {
            if (is_array($term)) {
                $slug  = (string) ($term['slug'] ?? '');
                $label = (string) ($term['label'] ?? '');
            } else {
                $slug  = (string) $term;
                $label = '';
            }
            if ($slug === '' || preg_match('/^[a-z0-9-]+$/', $slug) !== 1) {
                continue;
            }
            $result[] = [
                'label' => $label !== '' ? $label : ucwords(str_replace('-', ' ', $slug)),
                'url'   => locale_url('/glossary/' . $slug),
            ];
        }

        return $result;
    }

    /**
     * Строит Schema.org разметку для страницы-индекса: BreadcrumbList + CollectionPage со списком статей.
     *
     * @param  array<int, array{slug: string, title: string, excerpt: string, category: string, reading_time: int, updated_at: string, url: string}> $guides
     * @return array<int, array<string, mixed>>
     */
    private function buildIndexStructuredData(array $guides): array
    {
        $appUrl    = rtrim((string) config('app.url', ''), '/');
        $guidesUrl = $appUrl . locale_url('/guides');

        $schemas = [];

        $schemas[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => trans('BREADCRUMB_HOME'), 'item' => $appUrl . locale_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => trans('GUIDES_BREADCRUMB'), 'item' => $guidesUrl],
            ],
        ];

        $schemas[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'CollectionPage',
            'name'            => trans('GUIDES_INDEX_TITLE'),
            'url'             => $guidesUrl,
            'mainEntity'      => [
                '@type'           => 'ItemList',
                'itemListElement' => array_values(array_map(static function (array $guide, int $i) use ($appUrl): array {
                    return [
                        '@type'    => 'ListItem',
                        'position' => $i + 1,
                        'url'      => $appUrl . $guide['url'],
                        'name'     => $guide['title'],
                    ];
                }, $guides, array_keys($guides))),
            ],
        ];

        return $schemas;
    }

    /**
     * Строит Schema.org разметку страницы статьи: BreadcrumbList, Article и (при наличии) FAQPage.
     *
     * @param  array<string, mixed>             $guide
     * @param  array<string, mixed>             $meta
     * @param  array<int, array<string, mixed>> $faq
     * @return array<int, array<string, mixed>>
     */
    private function buildArticleStructuredData(string $slug, array $guide, array $meta, array $faq): array
    {
        $appUrl    = rtrim((string) config('app.url', ''), '/');
        $guidesUrl = $appUrl . locale_url('/guides');
        $guideUrl  = $appUrl . locale_url('/guides/' . $slug);
        $title     = (string) ($guide['title'] ?? $slug);
        $published = (string) ($meta['published_at'] ?? $meta['updated_at'] ?? '');
        $modified  = (string) ($meta['updated_at'] ?? $published);
        $ogImage   = (string) config('app.og_image', '');

        $schemas = [];

        $schemas[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => trans('BREADCRUMB_HOME'), 'item' => $appUrl . locale_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => trans('GUIDES_BREADCRUMB'), 'item' => $guidesUrl],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $title, 'item' => $guideUrl],
            ],
        ];

        $article = [
            '@context'         => 'https://schema.org',
            '@type'            => 'Article',
            'headline'         => $title,
            'description'      => (string) ($guide['meta_description'] ?? $guide['excerpt'] ?? ''),
            'inLanguage'       => (string) ($meta['language'] ?? locale()),
            'url'              => $guideUrl,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $guideUrl],
            'author'           => ['@type' => 'Organization', 'name' => 'CiphersOnline', 'url' => $appUrl . '/'],
            'publisher'        => ['@type' => 'Organization', 'name' => 'CiphersOnline', 'url' => $appUrl . '/'],
        ];

        if ($published !== '') {
            $article['datePublished'] = $published;
        }
        if ($modified !== '') {
            $article['dateModified'] = $modified;
        }
        if ($ogImage !== '') {
            $article['image'] = str_starts_with($ogImage, 'http') ? $ogImage : $appUrl . '/' . ltrim($ogImage, '/');
        }

        $schemas[] = $article;

        if ($faq !== []) {
            $schemas[] = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => array_map(static fn (array $item): array => [
                    '@type'          => 'Question',
                    'name'           => (string) ($item['question'] ?? ''),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags((string) ($item['answer'] ?? ''))],
                ], $faq),
            ];
        }

        return $schemas;
    }
}
