<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cipher\BaseToolUiFactory;
use App\Cipher\HashingToolUi;
use App\Http\Request;
use App\Http\Response;
use App\Repository\CipherCategoryRepository;
use App\Repository\CipherRepository;
use App\Repository\SystemPageRepository;
use App\View\View;

/**
 * Контроллер публичных страниц категорий шифров и системных страниц.
 */
final readonly class CipherCategoryController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private View $view,
        private CipherCategoryRepository $categories,
        private CipherRepository $ciphers,
        private SystemPageRepository $pages,
        private BaseToolUiFactory $uiFactory
    ) {
    }

    /**
     * Отображает системную страницу или страницу категории по alias и текущей локали.
     */
    public function show(Request $request): Response
    {
        $alias    = (string) $request->route('alias', '');
        $language = locale();

        $page = $this->pages->findPublishedByAliasAndLanguage($alias, $language)
            ?? $this->pages->findPublishedByAlias($alias);

        if ($page !== null) {
            $this->view
                ->setTitle($page['name'])
                ->setBreadcrumbs([['label' => (string) $page['name']]])
                ->setRobots('noindex,follow')
                ->setContent($this->view->fetch('page/show.tpl', ['page' => $page]));

            return new Response($this->view->render());
        }

        $category = $this->categories->findPublishedCategoryPageByAliasAndLanguage($alias, $language);

        if ($category === null) {
            $category = $this->categories->findPublishedCategoryPageByAlias($alias);
        }

        if ($category === null) {
            $this->view
                ->setTitle(trans('ERROR_404_TITLE'))
                ->setContent($this->view->fetch('errors/404.tpl'));

            return new Response($this->view->render(), 404);
        }

        $title = (string) ($category['name'] ?? $category['alias']);
        $metaDescription = (string) ($category['meta_description'] ?? '');

        $defaultLanguage = (string) config('locale.locale', 'en');
        $tools = $this->ciphers->findPublishedByCategoryWithTranslation(
            (int) $category['id'],
            $language,
            $defaultLanguage
        );
        $blocks = $this->categories->findBlocksByCategoryIdWithTranslation(
            (int) $category['id'],
            $language,
            $defaultLanguage
        );
        $tasks = $this->categories->findTasksByCategoryIdWithTranslationAndCipher(
            (int) $category['id'],
            $language,
            $defaultLanguage
        );
        $usedTogether = $this->categories->findUsedTogetherByCategoryIdWithTranslationAndCiphers(
            (int) $category['id'],
            $language,
            $defaultLanguage
        );
        $faq = $this->categories->findFaqByCategoryIdWithTranslation(
            (int) $category['id'],
            $language,
            $defaultLanguage
        );

        $cipherIds = array_map(static fn (array $t) => (int) $t['id'], $tools);
        $tagsByCipher = $this->ciphers->findTagsGroupedByCipherIds($cipherIds, $language, $defaultLanguage);

        foreach ($tools as &$tool) {
            $tool['tags'] = $tagsByCipher[(int) $tool['id']] ?? [];
        }
        unset($tool);

        foreach ($tasks as &$task) {
            $task['icon'] = $this->iconForCipherAlias((string) ($task['cipher_alias'] ?? ''));
        }
        unset($task);

        $hero = $alias === 'hashing'
            ? $this->buildHashingHero()
            : null;

        $this->view
            ->setTitle($title)
            ->setMeta($metaDescription)
            ->setBreadcrumbs([
                ['label' => (string) (($category['name_short'] ?? '') !== '' ? $category['name_short'] : ($category['name'] ?? $category['alias']))],
            ])
            ->setStructuredData($this->buildStructuredData($category, $tools, $faq, $alias))
            ->setContent($this->view->fetch('cipher_category/show.tpl', [
                'category' => $category,
                'tools' => $tools,
                'blocks' => $blocks,
                'tasks' => $tasks,
                'used_together' => $usedTogether,
                'faq' => $faq,
                'hero_cipher' => $hero['cipher'] ?? null,
                'hero_tool_slug' => $hero['tool_slug'] ?? null,
                'hero_tool_ui' => $hero['tool_ui'] ?? null,
                'hero_tool_ui_json' => $hero['tool_ui_json'] ?? null,
            ]));

        return new Response($this->view->render());
    }

    /**
     * Готовит данные hero-калькулятора для категории «hashing».
     * Использует slug `hashing/sha256` (декодер уже зарегистрирован), но подменяет
     * заголовок и описание на универсальные — чтобы пользователь видел «Hash Calculator»,
     * а не SHA-256-специфичные тексты.
     *
     * @return array{cipher: array<string, mixed>, tool_slug: string, tool_ui: array<string, mixed>, tool_ui_json: string}
     */
    private function buildHashingHero(): array
    {
        $toolSlug  = 'hashing/sha256';
        $cipherAlias = 'sha256';

        $heroCipher = [
            'name'        => trans('HASH_HERO_TITLE'),
            'description' => trans('HASH_HERO_DESC'),
        ];

        $toolUi = HashingToolUi::apply($this->uiFactory->build($toolSlug, 'client'), $cipherAlias);

        return [
            'cipher'       => $heroCipher,
            'tool_slug'    => $toolSlug,
            'tool_ui'      => $toolUi,
            'tool_ui_json' => (string) json_encode($toolUi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * Строит массив Schema.org объектов для страницы хаба:
     * BreadcrumbList, CollectionPage и (при наличии) FAQPage.
     *
     * @param array<string, mixed>             $category
     * @param array<int, array<string, mixed>> $tools
     * @param array<int, array<string, mixed>> $faq
     * @return array<int, array<string, mixed>>
     */
    private function buildStructuredData(array $category, array $tools, array $faq, string $alias): array
    {
        $appUrl      = rtrim((string) config('app.url', ''), '/');
        $categoryUrl = $appUrl . locale_url('/' . $alias);

        $categoryLabel = (string) (($category['name_short'] ?? '') !== ''
            ? $category['name_short']
            : ($category['name'] ?? $alias));

        $schemas = [];

        $schemas[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => trans('BREADCRUMB_HOME'), 'item' => $appUrl . locale_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $categoryLabel, 'item' => $categoryUrl],
            ],
        ];

        $collectionSchema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            'name'        => (string) ($category['name'] ?? $alias),
            'description' => (string) ($category['meta_description'] ?: ($category['description'] ?? '')),
            'url'         => $categoryUrl,
        ];

        if (!empty($tools)) {
            $collectionSchema['hasPart'] = array_map(static fn (array $tool): array => [
                '@type' => 'WebApplication',
                'name'  => (string) ($tool['name'] ?? ''),
                'url'   => $appUrl . locale_url('/' . $alias . '/' . ($tool['alias'] ?? '')),
            ], $tools);
        }

        $schemas[] = $collectionSchema;

        if (!empty($faq)) {
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

    /**
     * Возвращает CSS-класс иконки по alias шифра.
     */
    private function iconForCipherAlias(string $alias): string
    {
        return match ($alias) {
            'base64' => 'fa-solid fa-code',
            'url-encode' => 'fa-solid fa-percent',
            'jwt-decoder' => 'fa-solid fa-key',
            'hex' => 'fa-solid fa-magnifying-glass',
            default => 'fa-solid fa-bolt',
        };
    }
}
