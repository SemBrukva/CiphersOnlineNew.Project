<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cipher\BaseToolUiFactory;
use App\Cipher\ToolRegistry;
use App\Cipher\ToolUiDecorator;
use App\Glossary\GlossaryRepository;
use App\Http\Request;
use App\Http\Response;
use App\Repository\CipherCategoryRepository;
use App\Repository\CipherRepository;
use App\View\View;

/**
 * Контроллер публичной страницы конкретного инструмента шифрования/кодирования.
 */
final readonly class CipherController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private View $view,
        private CipherRepository $ciphers,
        private CipherCategoryRepository $categories,
        private ToolRegistry $toolRegistry,
        private BaseToolUiFactory $uiFactory,
        private ToolUiDecorator $uiDecorator,
        private GlossaryRepository $glossary
    ) {
    }

    /**
     * Возвращает связанные термины глоссария для инструмента (из config/glossary_related.php).
     * Резолвит slug'и в пары {name, url} по опубликованному индексу; отсутствующие пропускает.
     *
     * @return array<int, array{name: string, url: string}>
     */
    private function buildGlossaryLinks(string $toolSlug, string $language): array
    {
        $slugs = (array) (config('glossary_related.' . $toolSlug) ?? []);
        if ($slugs === []) {
            return [];
        }

        $bySlug = [];
        foreach ($this->glossary->index($language) as $term) {
            $bySlug[$term['slug']] = $term;
        }

        $links = [];
        foreach ($slugs as $slug) {
            $slug = (string) $slug;
            if (isset($bySlug[$slug])) {
                $links[] = ['name' => $bySlug[$slug]['name'], 'url' => $bySlug[$slug]['url']];
            }
        }

        return $links;
    }

    /**
     * Отображает страницу инструмента по alias категории и инструмента.
     */
    public function show(Request $request): Response
    {
        $categoryAlias = (string) $request->route('category', '');
        $cipherAlias = (string) $request->route('cipher', '');
        $language = locale();
        $defaultLanguage = (string) config('locale.locale', 'en');

        $cipher = $this->ciphers->findPublishedCipherPageByAliases(
            $categoryAlias,
            $cipherAlias,
            $language,
            $defaultLanguage
        );

        if ($cipher === null) {
            $this->view
                ->setTitle(trans('ERROR_404_TITLE'))
                ->setContent($this->view->fetch('errors/404.tpl'));

            return new Response($this->view->render(), 404);
        }

        $category = $this->categories->findPublishedCategoryPageByAliasAndLanguage($categoryAlias, $language)
            ?? $this->categories->findPublishedCategoryPageByAlias($categoryAlias);

        $blocks = $this->ciphers->findBlocksByCipherIdWithTranslation((int) $cipher['id'], $language, $defaultLanguage);
        $faq = $this->ciphers->findFaqByCipherIdWithTranslation((int) $cipher['id'], $language, $defaultLanguage);
        $examples = $this->enrichExamples(
            $toolSlug = $categoryAlias.'/'.$cipherAlias,
            $this->ciphers->findExamplesByCipherIdWithTranslation((int) $cipher['id'], $language, $defaultLanguage)
        );

        $related = $this->buildRelatedTools($toolSlug, $cipherAlias, (int) $cipher['category_id'], $language, $defaultLanguage);

        $title = (string) ($cipher['meta_title'] ?: $cipher['name']);
        $metaDescription = (string) ($cipher['meta_description'] ?: $cipher['description']);
        $calculationMode = (string) ($cipher['calculation_mode'] ?? 'client');
        $toolUi = $this->uiDecorator->decorate(
            $this->uiFactory->build($toolSlug, $calculationMode),
            $categoryAlias,
            $cipherAlias
        );
        $allInCategoryLabel = str_replace(
            ':category',
            (string) ($category['name'] ?? $categoryAlias),
            trans('CIPHER_TOOL_ALL_IN_CATEGORY')
        );

        $examples = $this->attachSettingsBadges($examples, $toolUi);

        $this->view
            ->setTitle($title)
            ->setMeta($metaDescription)
            ->setBreadcrumbs([
                ['label' => (string) (($category['name_short'] ?? '') !== '' ? $category['name_short'] : ($category['name'] ?? $categoryAlias)), 'url' => locale_url('/'.$categoryAlias)],
                ['label' => (string) ($cipher['name_short'] ?? $cipher['name'])],
            ])
            ->setStructuredData($this->buildStructuredData($cipher, $category, $faq, $categoryAlias, $cipherAlias))
            ->setContent($this->view->fetch('cipher/show.tpl', [
                'cipher' => $cipher,
                'category' => $category,
                'blocks' => $blocks,
                'faq' => $faq,
                'examples' => $examples,
                'related' => $related,
                'tool_slug' => $toolSlug,
                'tool_ui' => $toolUi,
                'tool_ui_json' => (string) json_encode($toolUi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'all_in_category_label' => $allInCategoryLabel,
                'glossary_links' => $this->buildGlossaryLinks($toolSlug, $language),
            ]));

        return new Response($this->view->render());
    }

    /**
     * Добавляет поле `matrix_key` к примерам для инструментов с матричным ключом.
     *
     * @param  array<int, array<string, mixed>> $examples
     * @return array<int, array<string, mixed>>
     */
    private function enrichExamples(string $toolSlug, array $examples): array
    {
        foreach ($examples as &$example) {
            $settings = $example['settings'] ?? null;
            if (is_array($settings) && $settings !== []) {
                $example['settings_json'] = (string) json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
        unset($example);

        if ($toolSlug === 'classical-ciphers/trifid' && locale() === 'ru') {
            foreach ($examples as &$example) {
                $example['alphabet'] = 'en';
            }
            unset($example);
        }

        if ($toolSlug === 'codes-and-alphabets/anagram-solver') {
            foreach ($examples as &$example) {
                $input = (string) ($example['input'] ?? '');
                if (str_contains($input, '?')) {
                    $example['anagram_mode'] = 'pattern';
                } elseif (preg_match('/\s/u', $input) === 1) {
                    $example['anagram_mode'] = 'multi-word';
                } elseif (mb_strlen($input) >= 8) {
                    $example['anagram_mode'] = 'word-finder';
                } else {
                    $example['anagram_mode'] = 'anagram';
                }
            }
            unset($example);
        }

        if ($toolSlug === 'classical-ciphers/enigma') {
            foreach ($examples as &$example) {
                $this->enrichEnigmaExample($example);
            }
            unset($example);
        }

        if (!$this->toolRegistry->exampleKeyIsMatrix($toolSlug)) {
            return $examples;
        }

        foreach ($examples as &$example) {
            $key = trim((string) ($example['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $rows = array_values(array_filter(array_map('trim', explode(';', $key))));
            $example['matrix_key'] = array_map(
                static fn (string $row): array => array_values(
                    array_map('intval', preg_split('/\s+/u', trim($row)))
                ),
                $rows
            );
        }

        return $examples;
    }

    /**
     * Добавляет к каждому примеру массив `settings_badges` (метка + значение) на основе
     * `tool_ui.settings` (карта id → label). Используется для отрисовки бейджей в карточке примера.
     *
     * @param  array<int, array<string, mixed>> $examples
     * @param  array<string, mixed>             $toolUi
     * @return array<int, array<string, mixed>>
     */
    private function attachSettingsBadges(array $examples, array $toolUi): array
    {
        $idToLabel = [];
        $idToOptionLabels = [];

        foreach ((array) ($toolUi['settings'] ?? []) as $setting) {
            if (!is_array($setting)) {
                continue;
            }
            $id = (string) ($setting['id'] ?? '');
            $label = (string) ($setting['label'] ?? '');
            if ($id !== '' && $label !== '') {
                $idToLabel[$id] = $label;
            }
            foreach ((array) ($setting['options'] ?? []) as $option) {
                if (!is_array($option)) {
                    continue;
                }
                $optionValue = (string) ($option['value'] ?? '');
                $optionLabel = (string) ($option['label'] ?? '');
                if ($id !== '' && $optionValue !== '' && $optionLabel !== '') {
                    $idToOptionLabels[$id][$optionValue] = $optionLabel;
                }
            }
        }

        if ($idToLabel === []) {
            return $examples;
        }

        foreach ($examples as &$example) {
            $settings = $example['settings'] ?? null;
            if (!is_array($settings) || $settings === []) {
                continue;
            }

            $badges = [];

            foreach ($settings as $fieldId => $value) {
                if (!isset($idToLabel[$fieldId])) {
                    continue;
                }
                $scalar = is_scalar($value) ? (string) $value : '';
                if ($scalar === '') {
                    continue;
                }
                $display = $idToOptionLabels[$fieldId][$scalar] ?? $scalar;
                $badges[] = ['label' => $idToLabel[$fieldId], 'value' => $display];
            }

            if ($badges !== []) {
                $example['settings_badges'] = $badges;
            }
        }
        unset($example);

        return $examples;
    }

    /**
     * Раскладывает поле key примера Enigma в data-атрибуты для настроек.
     *
     * Формат key в БД: rotorL,rotorM,rotorR|ringL,ringM,ringR|posL,posM,posR|reflector|plugboard
     * Поле key переписывается в человекочитаемое представление для UI карточки.
     *
     * @param array<string, mixed> $example
     */
    private function enrichEnigmaExample(array &$example): void
    {
        $raw = trim((string) ($example['key'] ?? ''));
        if ($raw === '') {
            return;
        }

        $parts = array_pad(explode('|', $raw), 5, '');
        $rotors    = array_pad(array_map('trim', explode(',', $parts[0])), 3, '');
        $rings     = array_pad(array_map('trim', explode(',', $parts[1])), 3, '');
        $positions = array_pad(array_map('trim', explode(',', $parts[2])), 3, '');
        $reflector = strtoupper(trim($parts[3])) ?: 'B';
        $plugboard = trim($parts[4]);

        $example['enigma_reflector']    = $reflector;
        $example['enigma_rotor_left']   = strtoupper($rotors[0]) ?: 'I';
        $example['enigma_rotor_middle'] = strtoupper($rotors[1]) ?: 'II';
        $example['enigma_rotor_right']  = strtoupper($rotors[2]) ?: 'III';
        $example['enigma_ring_left']    = strtoupper($rings[0]) ?: 'A';
        $example['enigma_ring_middle']  = strtoupper($rings[1]) ?: 'A';
        $example['enigma_ring_right']   = strtoupper($rings[2]) ?: 'A';
        $example['enigma_pos_left']     = strtoupper($positions[0]) ?: 'A';
        $example['enigma_pos_middle']   = strtoupper($positions[1]) ?: 'A';
        $example['enigma_pos_right']    = strtoupper($positions[2]) ?: 'A';
        $example['enigma_plugboard']    = $plugboard;

        $display = sprintf(
            '%s-%s-%s · %s-%s-%s · UKW-%s%s',
            $example['enigma_rotor_left'],
            $example['enigma_rotor_middle'],
            $example['enigma_rotor_right'],
            $example['enigma_pos_left'],
            $example['enigma_pos_middle'],
            $example['enigma_pos_right'],
            $reflector,
            $plugboard !== '' ? ' · ' . $plugboard : ''
        );
        $example['key'] = $display;
    }

    /**
     * Строит массив Schema.org объектов для страницы инструмента:
     * BreadcrumbList, WebApplication и (при наличии) FAQPage.
     *
     * @param array<string, mixed>      $cipher
     * @param array<string, mixed>|null $category
     * @param array<int, array<string, mixed>> $faq
     * @return array<int, array<string, mixed>>
     */
    private function buildStructuredData(
        array $cipher,
        ?array $category,
        array $faq,
        string $categoryAlias,
        string $cipherAlias
    ): array {
        $appUrl      = rtrim((string) config('app.url', ''), '/');
        $categoryUrl = $appUrl . locale_url('/' . $categoryAlias);
        $toolUrl     = $appUrl . locale_url('/' . $categoryAlias . '/' . $cipherAlias);

        $categoryLabel = (string) (($category['name_short'] ?? '') !== ''
            ? $category['name_short']
            : ($category['name'] ?? $categoryAlias));

        $schemas = [];

        $schemas[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => trans('BREADCRUMB_HOME'), 'item' => $appUrl . locale_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $categoryLabel, 'item' => $categoryUrl],
                ['@type' => 'ListItem', 'position' => 3, 'name' => (string) ($cipher['name_short'] ?? $cipher['name']), 'item' => $toolUrl],
            ],
        ];

        $schemas[] = [
            '@context'            => 'https://schema.org',
            '@type'               => 'WebApplication',
            'name'                => (string) $cipher['name'],
            'description'         => (string) ($cipher['meta_description'] ?: $cipher['description']),
            'url'                 => $toolUrl,
            'applicationCategory' => 'UtilityApplication',
            'operatingSystem'     => 'Web',
            'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
        ];

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
     * Формирует список связанных инструментов: сначала ручные привязки из конфига,
     * затем добирает до 6 из той же категории (исключая текущий и уже добавленные).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRelatedTools(
        string $currentSlug,
        string $currentAlias,
        int $categoryId,
        string $language,
        string $defaultLanguage
    ): array {
        $manualSlugs = (array) (config('cipher_related.' . $currentSlug) ?? []);

        $pinned = $manualSlugs !== []
            ? $this->ciphers->findPublishedBySlugsWithTranslation($manualSlugs, $language, $defaultLanguage)
            : [];

        $remaining = 6 - count($pinned);
        if ($remaining <= 0) {
            return array_slice($pinned, 0, 6);
        }

        $excludeAliases = array_merge(
            [$currentAlias],
            array_map(static fn (array $t): string => (string) ($t['alias'] ?? ''), $pinned)
        );

        $fromCategory = array_values(array_filter(
            $this->ciphers->findPublishedByCategoryWithTranslation($categoryId, $language, $defaultLanguage),
            static fn (array $t): bool => !in_array((string) ($t['alias'] ?? ''), $excludeAliases, true)
        ));

        return array_merge($pinned, array_slice($fromCategory, 0, $remaining));
    }
}
