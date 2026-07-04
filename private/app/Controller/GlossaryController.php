<?php

declare(strict_types=1);

namespace App\Controller;

use App\Glossary\GlossaryRepository;
use App\Http\Request;
use App\Http\Response;
use App\View\View;

/**
 * Контроллер публичного глоссария терминов (файловый контент, без БД).
 */
final readonly class GlossaryController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private View $view,
        private GlossaryRepository $glossary
    ) {
    }

    /**
     * Отображает страницу-индекс глоссария: термины, сгруппированные по категориям.
     */
    public function index(Request $request): Response
    {
        $locale = locale();
        $terms  = $this->glossary->index($locale);

        $grouped = [];
        foreach ($terms as $term) {
            $grouped[$term['category']][] = $term;
        }

        $sections = [];
        foreach (GlossaryRepository::CATEGORY_ORDER as $category) {
            if (!empty($grouped[$category])) {
                $sections[] = [
                    'key'   => $category,
                    'label' => trans('GLOSSARY_CAT_' . strtoupper($category)),
                    'terms' => $grouped[$category],
                ];
                unset($grouped[$category]);
            }
        }

        // Категории вне заранее заданного порядка — добавляем в конец.
        foreach ($grouped as $category => $list) {
            $sections[] = ['key' => $category, 'label' => ucfirst((string) $category), 'terms' => $list];
        }

        $this->view
            ->setTitle(trans('GLOSSARY_INDEX_TITLE'))
            ->setMeta(trans('GLOSSARY_INDEX_META'))
            ->setBreadcrumbs([['label' => trans('GLOSSARY_BREADCRUMB')]])
            ->setStructuredData($this->buildIndexStructuredData($terms))
            ->setContent($this->view->fetch('glossary/index.tpl', [
                'sections' => $sections,
                'total'    => count($terms),
            ]));

        return new Response($this->view->render());
    }

    /**
     * Отображает страницу термина.
     */
    public function show(Request $request): Response
    {
        $slug   = (string) $request->route('term', '');
        $locale = locale();

        $data = $this->glossary->find($slug, $locale);

        if ($data === null) {
            $this->view
                ->setTitle(trans('ERROR_404_TITLE'))
                ->setContent($this->view->fetch('errors/404.tpl'));

            return new Response($this->view->render(), 404);
        }

        $term = (array) ($data['term'] ?? []);
        $name = (string) ($term['name'] ?? $slug);

        $body = (array) ($data['body'] ?? []);
        usort($body, static fn (array $a, array $b): int => ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)));

        $faq           = (array) ($data['faq'] ?? []);
        $relatedTerms  = $this->resolveRelatedTerms((array) ($data['related_terms'] ?? []), $locale);
        $relatedTools  = $this->resolveRelatedTools((array) ($data['related_tools'] ?? []));

        $title           = (string) ($term['meta_title'] ?? '') ?: $name;
        $metaDescription = (string) ($term['meta_description'] ?? '') ?: (string) ($term['short'] ?? '');

        $this->view
            ->setTitle($title)
            ->setMeta($metaDescription)
            ->setBreadcrumbs([
                ['label' => trans('GLOSSARY_BREADCRUMB'), 'url' => locale_url('/glossary')],
                ['label' => $name],
            ])
            ->setStructuredData($this->buildTermStructuredData($slug, $term, $faq))
            ->setContent($this->view->fetch('glossary/show.tpl', [
                'term'          => $term,
                'body'          => $body,
                'faq'           => $faq,
                'related_terms' => $relatedTerms,
                'related_tools' => $relatedTools,
            ]));

        return new Response($this->view->render());
    }

    /**
     * Резолвит slug'и связанных терминов в пары {name, url} по кэшированному индексу; отсутствующие пропускает.
     *
     * @param  array<int, mixed> $slugs
     * @return array<int, array{name: string, url: string}>
     */
    private function resolveRelatedTerms(array $slugs, string $locale): array
    {
        if ($slugs === []) {
            return [];
        }

        $bySlug = [];
        foreach ($this->glossary->index($locale) as $term) {
            $bySlug[$term['slug']] = $term;
        }

        $result = [];
        foreach ($slugs as $slug) {
            $slug = (string) $slug;
            if (isset($bySlug[$slug])) {
                $result[] = ['name' => $bySlug[$slug]['name'], 'url' => $bySlug[$slug]['url']];
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
     * Строит Schema.org разметку для страницы-индекса: BreadcrumbList + DefinedTermSet.
     *
     * @param  array<int, array{slug: string, name: string, short: string, category: string, url: string}> $terms
     * @return array<int, array<string, mixed>>
     */
    private function buildIndexStructuredData(array $terms): array
    {
        $appUrl      = rtrim((string) config('app.url', ''), '/');
        $glossaryUrl = $appUrl . locale_url('/glossary');

        $schemas = [];

        $schemas[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => trans('BREADCRUMB_HOME'), 'item' => $appUrl . locale_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => trans('GLOSSARY_BREADCRUMB'), 'item' => $glossaryUrl],
            ],
        ];

        $schemas[] = [
            '@context'      => 'https://schema.org',
            '@type'         => 'DefinedTermSet',
            'name'          => trans('GLOSSARY_INDEX_TITLE'),
            'url'           => $glossaryUrl,
            'hasDefinedTerm' => array_map(static fn (array $term): array => [
                '@type' => 'DefinedTerm',
                'name'  => $term['name'],
                'url'   => $appUrl . $term['url'],
            ], $terms),
        ];

        return $schemas;
    }

    /**
     * Строит Schema.org разметку страницы термина: BreadcrumbList, DefinedTerm и (при наличии) FAQPage.
     *
     * @param  array<string, mixed>             $term
     * @param  array<int, array<string, mixed>> $faq
     * @return array<int, array<string, mixed>>
     */
    private function buildTermStructuredData(string $slug, array $term, array $faq): array
    {
        $appUrl      = rtrim((string) config('app.url', ''), '/');
        $glossaryUrl = $appUrl . locale_url('/glossary');
        $termUrl     = $appUrl . locale_url('/glossary/' . $slug);
        $name        = (string) ($term['name'] ?? $slug);

        $schemas = [];

        $schemas[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => trans('BREADCRUMB_HOME'), 'item' => $appUrl . locale_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => trans('GLOSSARY_BREADCRUMB'), 'item' => $glossaryUrl],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => $termUrl],
            ],
        ];

        $schemas[] = [
            '@context'         => 'https://schema.org',
            '@type'            => 'DefinedTerm',
            'name'             => $name,
            'description'      => (string) ($term['short'] ?? ''),
            'termCode'         => $slug,
            'url'              => $termUrl,
            'inDefinedTermSet' => $glossaryUrl,
        ];

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
