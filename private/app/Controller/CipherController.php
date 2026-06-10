<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cipher\ToolRegistry;
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
        private ToolRegistry $toolRegistry
    ) {
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

        $toolsInCategory = $this->ciphers->findPublishedByCategoryWithTranslation(
            (int) $cipher['category_id'],
            $language,
            $defaultLanguage
        );
        $related = array_slice(array_filter(
            $toolsInCategory,
            static fn (array $tool): bool => (string) ($tool['alias'] ?? '') !== $cipherAlias
        ), 0, 6);

        $title = (string) ($cipher['meta_title'] ?: $cipher['name']);
        $metaDescription = (string) ($cipher['meta_description'] ?: $cipher['description']);
        $calculationMode = (string) ($cipher['calculation_mode'] ?? 'client');
        $toolUi = $this->buildToolUi($toolSlug, $calculationMode);
        if ($cipherAlias === 'morse-code') {
            $toolUi['placeholderEncode']  = trans('MORSE_PLACEHOLDER_ENCODE');
            $toolUi['placeholderDecode']  = trans('MORSE_PLACEHOLDER_DECODE');
            $toolUi['morsePlayLabel']     = trans('MORSE_PLAY');
            $toolUi['morseStopLabel']     = trans('MORSE_STOP');
            $toolUi['morseDownloadLabel'] = trans('MORSE_DOWNLOAD');
            $toolUi['morseSpeedLabel']    = trans('MORSE_SPEED_LABEL');
            $toolUi['morseFreqLabel']     = trans('MORSE_FREQ_LABEL');
            $toolUi['morseFreqLow']       = trans('MORSE_FREQ_LOW');
            $toolUi['morseFreqMed']       = trans('MORSE_FREQ_MED');
            $toolUi['morseFreqHigh']          = trans('MORSE_FREQ_HIGH');
            $toolUi['morseErrInvalidFormat']  = trans('MORSE_ERR_INVALID_FORMAT');
            $toolUi['morseWarnUnknownChars']  = trans('MORSE_WARN_UNKNOWN_CHARS');
            $toolUi['morseInfoDecodedUnknown'] = trans('MORSE_INFO_DECODED_UNKNOWN');
        }
        $allInCategoryLabel = str_replace(
            ':category',
            (string) ($category['name'] ?? $categoryAlias),
            trans('CIPHER_TOOL_ALL_IN_CATEGORY')
        );

        $this->view
            ->setTitle($title)
            ->setMeta($metaDescription)
            ->setBreadcrumbs([
                ['label' => (string) (($category['name_short'] ?? '') !== '' ? $category['name_short'] : ($category['name'] ?? $categoryAlias)), 'url' => '/'.$categoryAlias],
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
            ]));

        return new Response($this->view->render());
    }

    /**
     * Возвращает UI-конфигурацию рабочей области для инструмента.
     *
     * @return array<string, mixed>
     */
    private function buildToolUi(string $toolSlug, string $calculationMode): array
    {
        return [
            'tabEncode' => trans('CIPHER_TOOL_TAB_ENCODE'),
            'tabDecode' => trans('CIPHER_TOOL_TAB_DECODE'),
            'inputLabelEncode' => trans('CIPHER_TOOL_INPUT_LABEL_ENCODE'),
            'inputLabelDecode' => trans('CIPHER_TOOL_INPUT_LABEL_DECODE'),
            'placeholderEncode' => trans('CIPHER_TOOL_PLACEHOLDER_ENCODE'),
            'placeholderDecode' => trans('CIPHER_TOOL_PLACEHOLDER_DECODE'),
            'placeholderOutput' => trans('CIPHER_TOOL_PLACEHOLDER_OUTPUT'),
            'resultLabel' => trans('CIPHER_TOOL_RESULT_LABEL'),
            'tryLabel' => trans('CIPHER_TOOL_TRY_LABEL'),
            'copyLabel' => trans('CIPHER_TOOL_COPY_LABEL'),
            'shareLabel' => trans('CIPHER_TOOL_SHARE_LABEL'),
            'clearLabel' => trans('CIPHER_TOOL_CLEAR_LABEL'),
            'charsLabel' => trans('CIPHER_TOOL_CHARS_LABEL'),
            'bytesLabel' => trans('CIPHER_TOOL_BYTES_LABEL'),
            'examplesTitle' => trans('CIPHER_TOOL_EXAMPLES_TITLE'),
            'faqTitle' => trans('CIPHER_TOOL_FAQ_TITLE'),
            'relatedTitle' => trans('CIPHER_TOOL_RELATED_TITLE'),
            'infoTitle' => trans('CIPHER_TOOL_INFO_TITLE'),
            'inputTag' => trans('CIPHER_TOOL_INPUT_TAG'),
            'outputTag' => trans('CIPHER_TOOL_OUTPUT_TAG'),
            'useExampleLabel' => trans('CIPHER_TOOL_USE_EXAMPLE'),
            'feedbackInvalidInput' => trans('CIPHER_TOOL_FEEDBACK_INVALID'),
            'feedbackNotJson' => trans('CIPHER_TOOL_FEEDBACK_NOT_JSON'),
            'feedbackResultCopied' => trans('CIPHER_TOOL_FEEDBACK_RESULT_COPIED'),
            'feedbackCopyFailed' => trans('CIPHER_TOOL_FEEDBACK_RESULT_COPY_FAILED'),
            'feedbackUrlCopied' => trans('CIPHER_TOOL_FEEDBACK_URL_COPIED'),
            'feedbackUrlCopyFailed' => trans('CIPHER_TOOL_FEEDBACK_URL_COPY_FAILED'),
            'favoriteAddLabel' => trans('CIPHER_TOOL_FAVORITE_ADD'),
            'favoriteRemoveLabel' => trans('CIPHER_TOOL_FAVORITE_REMOVE'),
            'feedbackFavoriteAdded' => trans('CIPHER_TOOL_FEEDBACK_FAVORITE_ADDED'),
            'feedbackFavoriteRemoved' => trans('CIPHER_TOOL_FEEDBACK_FAVORITE_REMOVED'),
            'trustItems' => $this->toolRegistry->trustItems($toolSlug, $calculationMode),
            'calculationMode' => in_array($calculationMode, ['api', 'client'], true) ? $calculationMode : 'client',
            'locale' => locale(),
            'apiAction' => $this->toolRegistry->apiAction($toolSlug),
            'runLabel' => locale() === 'ru' ? 'Выполнить' : 'Run',
            'settings' => $this->buildToolSettings($toolSlug),
            'exampleChips' => $this->toolRegistry->exampleChips($toolSlug),
            'decodeNote'   => $this->toolRegistry->decodeNote($toolSlug),
            'exampleKeyLabel'   => $this->toolRegistry->exampleKeyLabel($toolSlug),
            'exampleKeyInputId' => $this->toolRegistry->exampleKeyInputId($toolSlug),
        ];
    }

    /**
     * Возвращает схему полей настроек для конкретного инструмента.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildToolSettings(string $toolSlug): array
    {
        return $this->toolRegistry->settings($toolSlug);
    }

    /**
     * Добавляет поле `matrix_key` к примерам для инструментов с матричным ключом.
     *
     * @param  array<int, array<string, mixed>> $examples
     * @return array<int, array<string, mixed>>
     */
    private function enrichExamples(string $toolSlug, array $examples): array
    {
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
}
