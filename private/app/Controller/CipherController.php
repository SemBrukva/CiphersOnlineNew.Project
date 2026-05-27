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
        $examples = $this->ciphers->findExamplesByCipherIdWithTranslation((int) $cipher['id'], $language, $defaultLanguage);

        $toolsInCategory = $this->ciphers->findPublishedByCategoryWithTranslation(
            (int) $cipher['category_id'],
            $language,
            $defaultLanguage
        );
        $related = array_values(array_filter(
            $toolsInCategory,
            static fn (array $tool): bool => (string) ($tool['alias'] ?? '') !== $cipherAlias
        ));

        $title = (string) ($cipher['meta_title'] ?: $cipher['name']);
        $metaDescription = (string) ($cipher['meta_description'] ?: $cipher['description']);
        $toolSlug = $categoryAlias.'/'.$cipherAlias;
        $toolUi = $this->buildToolUi(
            $toolSlug,
            (string) ($cipher['calculation_mode'] ?? 'client')
        );
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
            'feedbackResultCopied' => trans('CIPHER_TOOL_FEEDBACK_RESULT_COPIED'),
            'feedbackCopyFailed' => trans('CIPHER_TOOL_FEEDBACK_RESULT_COPY_FAILED'),
            'feedbackUrlCopied' => trans('CIPHER_TOOL_FEEDBACK_URL_COPIED'),
            'feedbackUrlCopyFailed' => trans('CIPHER_TOOL_FEEDBACK_URL_COPY_FAILED'),
            'trustItems' => [
                trans('CIPHER_TOOL_TRUST_LOCAL'),
                trans('CIPHER_TOOL_TRUST_UTF8'),
                trans('CIPHER_TOOL_TRUST_API'),
                trans('CIPHER_TOOL_TRUST_PRIVATE'),
            ],
            'calculationMode' => in_array($calculationMode, ['api', 'client'], true) ? $calculationMode : 'client',
            'apiAction' => $this->toolRegistry->apiAction($toolSlug),
            'runLabel' => locale() === 'ru' ? 'Выполнить' : 'Run',
            'settings' => $this->buildToolSettings($toolSlug),
            'exampleChips' => $this->toolRegistry->exampleChips($toolSlug),
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
}
