<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Строит базовую конфигурацию tool_ui (labels, trust, settings, examples).
 * Контроллеры дополняют результат специфичными для инструмента полями.
 */
final readonly class BaseToolUiFactory
{
    /**
     * @param ToolRegistry $toolRegistry
     */
    public function __construct(private ToolRegistry $toolRegistry)
    {
    }

    /**
     * Возвращает базовый массив tool_ui для указанного инструмента.
     *
     * @return array<string, mixed>
     */
    public function build(string $toolSlug, string $calculationMode): array
    {
        return [
            'toolSlug' => $toolSlug,
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
            'exampleEmptyInputLabel' => trans('CIPHER_TOOL_EXAMPLE_EMPTY_INPUT'),
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
            'settings' => $this->toolRegistry->settings($toolSlug),
            'exampleChips' => $this->toolRegistry->exampleChips($toolSlug),
            'decodeNote'   => $this->toolRegistry->decodeNote($toolSlug),
            'exampleKeyLabel'   => $this->toolRegistry->exampleKeyLabel($toolSlug),
            'exampleKeyInputId' => $this->toolRegistry->exampleKeyInputId($toolSlug),
        ];
    }
}
