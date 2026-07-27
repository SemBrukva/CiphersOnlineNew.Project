<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Дополняет базовую конфигурацию tool_ui специфичными для конкретного инструмента
 * полями (лейблы режимов, флаги UI-режимов, переводы таблиц результатов).
 *
 * Логика вынесена из CipherController, чтобы её мог переиспользовать EmbedController
 * и любой другой контроллер, рендерящий виджет инструмента.
 */
final class ToolUiDecorator
{
    /**
     * Возвращает tool_ui, дополненный полями для указанного инструмента.
     *
     * @param  array<string, mixed> $toolUi Базовый tool_ui из BaseToolUiFactory.
     * @return array<string, mixed>         Дополненный tool_ui.
     */
    public function decorate(array $toolUi, string $categoryAlias, string $cipherAlias): array
    {
        if ($cipherAlias === 'frequency-analysis') {
            $toolUi['analysisMode']          = true;
            $toolUi['tabEncode']             = trans('FREQ_TAB_ANALYZE');
            $toolUi['freqEmptyLabel']        = trans('FREQ_EMPTY');
            $toolUi['freqStatsCharsLabel']   = trans('FREQ_STATS_CHARS');
            $toolUi['freqStatsUniqueLabel']  = trans('FREQ_STATS_UNIQUE');
            $toolUi['freqStatLetters']       = trans('FREQ_STAT_LETTERS');
            $toolUi['freqStatWords']         = trans('FREQ_STAT_WORDS');
            $toolUi['freqStatUniqueLetters'] = trans('FREQ_STAT_UNIQUE_LETTERS');
            $toolUi['freqIcLabel']           = trans('FREQ_IC_LABEL');
            $toolUi['freqIcNatural']         = trans('FREQ_IC_NATURAL');
            $toolUi['freqIcPolyalpha']       = trans('FREQ_IC_POLYALPHA');
            $toolUi['freqIcRandom']          = trans('FREQ_IC_RANDOM');
            $toolUi['freqIcShort']           = trans('FREQ_IC_SHORT');
            $toolUi['freqColLetter']         = trans('FREQ_COL_LETTER');
            $toolUi['freqColCount']          = trans('FREQ_COL_COUNT');
            $toolUi['freqColActualPct']      = trans('FREQ_COL_ACTUAL_PCT');
            $toolUi['freqColExpectedPct']    = trans('FREQ_COL_EXPECTED_PCT');
            $toolUi['freqColDiff']           = trans('FREQ_COL_DIFF');
            $toolUi['freqColBigram']         = trans('FREQ_COL_BIGRAM');
            $toolUi['freqColTrigram']        = trans('FREQ_COL_TRIGRAM');
            $toolUi['freqColWord']           = trans('FREQ_COL_WORD');
            $toolUi['freqBigramsTitle']      = trans('FREQ_BIGRAMS_TITLE');
            $toolUi['freqTrigramsTitle']     = trans('FREQ_TRIGRAMS_TITLE');
            $toolUi['freqWordsTitle']        = trans('FREQ_WORDS_TITLE');
            $toolUi['freqLangMatchTitle']    = trans('FREQ_LANG_MATCH_TITLE');
            $toolUi['freqMismatchWarning']   = trans('FREQ_MISMATCH_WARNING');
            $toolUi['freqColDiffTooltip']    = trans('FREQ_COL_DIFF_TOOLTIP');
        }
        if ($cipherAlias === 'letter-frequency') {
            $toolUi['letterFrequencyMode']    = true;
            $toolUi['tabEncode']              = trans('LFREQ_TAB_ANALYZE');
            $toolUi['lfreqEmptyLabel']        = trans('LFREQ_EMPTY');
            $toolUi['lfreqStatLetters']       = trans('LFREQ_STAT_LETTERS');
            $toolUi['lfreqStatUnique']        = trans('LFREQ_STAT_UNIQUE');
            $toolUi['lfreqHeatmapTitle']      = trans('LFREQ_HEATMAP_TITLE');
            $toolUi['lfreqMissingTitle']      = trans('LFREQ_MISSING_TITLE');
            $toolUi['lfreqColLetter']         = trans('LFREQ_COL_LETTER');
            $toolUi['lfreqColCount']          = trans('LFREQ_COL_COUNT');
            $toolUi['lfreqColPct']            = trans('LFREQ_COL_PCT');
            $toolUi['lfreqColExpected']       = trans('LFREQ_COL_EXPECTED');
            $toolUi['lfreqLangDetectedLabel'] = trans('LFREQ_LANG_DETECTED');
            $toolUi['lfreqMismatchWarning']   = trans('LFREQ_MISMATCH_WARNING');
        }
        if ($cipherAlias === 'caesar') {
            $toolUi['relatedToolUrl']   = locale_url('/text-analysis/caesar-brute-force');
            $toolUi['relatedToolLabel'] = trans('CAESAR_HINT_BRUTE_FORCE');
        }
        if ($cipherAlias === 'affine') {
            $toolUi['relatedToolUrl']   = locale_url('/text-analysis/affine-brute-force');
            $toolUi['relatedToolLabel'] = trans('AFFINE_HINT_BRUTE_FORCE');
        }
        if ($cipherAlias === 'vigenere') {
            $toolUi['relatedToolUrl']   = locale_url('/text-analysis/vigenere-cracker');
            $toolUi['relatedToolLabel'] = trans('VIGENERE_HINT_CRACKER');
        }
        if ($cipherAlias === 'caesar-brute-force') {
            $toolUi['bruteForceMode']      = true;
            $toolUi['bruteEmptyLabel']     = trans('CAESAR_BRUTE_EMPTY');
            $toolUi['bruteColShift']       = trans('CAESAR_BRUTE_COL_SHIFT');
            $toolUi['bruteColText']        = trans('CAESAR_BRUTE_COL_TEXT');
            $toolUi['bruteUseLabel']       = trans('CAESAR_BRUTE_USE_LABEL');
            $toolUi['bruteTitle']          = trans('CAESAR_BRUTE_TITLE');
            $toolUi['bruteLikelyKey']      = trans('CAESAR_BRUTE_LIKELY_KEY');
            $toolUi['bruteFitnessLabel']   = trans('CAESAR_BRUTE_FITNESS_LABEL');
            $toolUi['bruteBestBadge']      = trans('CAESAR_BRUTE_BEST_BADGE');
            $toolUi['bruteShortText']      = trans('CAESAR_BRUTE_SHORT_TEXT');
        }
        if ($cipherAlias === 'affine-brute-force') {
            $toolUi['bruteForceMode']      = true;
            $toolUi['affineMode']          = true;
            $toolUi['disableLiveMode']     = true;
            $toolUi['inputMaxLength']      = AffineBruteForceApiCipherTool::MAX_TEXT_LENGTH;
            $toolUi['bruteEmptyLabel']     = trans('AFFINE_BRUTE_EMPTY');
            $toolUi['bruteColShift']       = trans('AFFINE_BRUTE_COL_KEY');
            $toolUi['bruteColText']        = trans('AFFINE_BRUTE_COL_TEXT');
            $toolUi['bruteUseLabel']       = trans('AFFINE_BRUTE_USE_LABEL');
            $toolUi['bruteTitle']          = trans('AFFINE_BRUTE_TITLE');
            $toolUi['bruteLikelyKey']      = trans('AFFINE_BRUTE_LIKELY_KEY');
            $toolUi['bruteFitnessLabel']   = trans('AFFINE_BRUTE_FITNESS_LABEL');
            $toolUi['bruteBestBadge']      = trans('AFFINE_BRUTE_BEST_BADGE');
            $toolUi['bruteShortText']      = trans('AFFINE_BRUTE_SHORT_TEXT');
        }
        if ($cipherAlias === 'vigenere-cracker') {
            $toolUi['vigenereCrackerMode']     = true;
            $toolUi['disableLiveMode']         = true;
            $toolUi['inputMaxLength']          = VigenereCrackerApiCipherTool::MAX_TEXT_LENGTH;
            $toolUi['vcEmptyLabel']            = trans('VIGENERE_CRACK_EMPTY');
            $toolUi['vcTitle']                 = trans('VIGENERE_CRACK_TITLE');
            $toolUi['vcKeyLabel']              = trans('VIGENERE_CRACK_KEY_LABEL');
            $toolUi['vcTextLabel']             = trans('VIGENERE_CRACK_TEXT_LABEL');
            $toolUi['vcColLen']                = trans('VIGENERE_CRACK_COL_LEN');
            $toolUi['vcColKey']                = trans('VIGENERE_CRACK_COL_KEY');
            $toolUi['vcColIc']                 = trans('VIGENERE_CRACK_COL_IC');
            $toolUi['vcColFitness']            = trans('VIGENERE_CRACK_COL_FITNESS');
            $toolUi['vcViewLabel']             = trans('VIGENERE_CRACK_VIEW_LABEL');
            $toolUi['vcBestBadge']             = trans('VIGENERE_CRACK_BEST_BADGE');
            $toolUi['vcShortText']             = trans('VIGENERE_CRACK_SHORT_TEXT');
        }
        if ($cipherAlias === 'numbers-to-letters') {
            $toolUi['numbersToLettersMode']   = true;
            $toolUi['tabEncode']              = trans('NUM2LET_TAB_NUMBERS_TO_LETTERS');
            $toolUi['tabDecode']              = trans('NUM2LET_TAB_LETTERS_TO_NUMBERS');
            $toolUi['placeholderEncode']      = trans('NUM2LET_PLACEHOLDER_NUMBERS');
            $toolUi['placeholderDecode']      = trans('NUM2LET_PLACEHOLDER_LETTERS');
        }
        if (in_array($cipherAlias, ['base32', 'base58', 'base85', 'base45', 'punycode'], true)) {
            $toolUi['baseEncodingMode'] = true;
        }
        if ($cipherAlias === 'book-cipher') {
            $toolUi['bookMode']          = true;
            $toolUi['placeholderEncode'] = trans('BOOK_PLACEHOLDER_ENCODE');
            $toolUi['placeholderDecode'] = trans('BOOK_PLACEHOLDER_DECODE');
            $toolUi['bookErrUncovered']  = trans('BOOK_ERR_UNCOVERED');
            $toolUi['bookErrNoBook']     = trans('BOOK_ERR_NO_BOOK');
        }
        if ($cipherAlias === 'timestamp-converter') {
            $toolUi['timestampConverterMode']         = true;
            $toolUi['tabEncode']                      = trans('TIMESTAMP_CONVERTER_TAB_TO_DATE');
            $toolUi['tabDecode']                      = trans('TIMESTAMP_CONVERTER_TAB_TO_TS');
            $toolUi['placeholderEncode']              = trans('TIMESTAMP_CONVERTER_PLACEHOLDER_TS');
            $toolUi['placeholderDecode']              = trans('TIMESTAMP_CONVERTER_PLACEHOLDER_DATE');
            $toolUi['tsErrInvalidTs']                 = trans('TIMESTAMP_CONVERTER_ERR_INVALID_TS');
            $toolUi['tsErrInvalidDate']               = trans('TIMESTAMP_CONVERTER_ERR_INVALID_DATE');
            $toolUi['tsNowLabel']                     = trans('TIMESTAMP_CONVERTER_NOW_LABEL');
            $toolUi['tsLabelUtc']                     = trans('TIMESTAMP_CONVERTER_LABEL_UTC');
            $toolUi['tsLabelLocal']                   = trans('TIMESTAMP_CONVERTER_LABEL_LOCAL');
            $toolUi['tsLabelIso']                     = trans('TIMESTAMP_CONVERTER_LABEL_ISO');
            $toolUi['tsLabelRelative']                = trans('TIMESTAMP_CONVERTER_LABEL_RELATIVE');
            $toolUi['tsLabelDay']                     = trans('TIMESTAMP_CONVERTER_LABEL_DAY');
            $toolUi['tsLabelUnixSec']                 = trans('TIMESTAMP_CONVERTER_LABEL_UNIX_SEC');
            $toolUi['tsLabelUnixMs']                  = trans('TIMESTAMP_CONVERTER_LABEL_UNIX_MS');
            $toolUi['tsResultSeconds']                = trans('TIMESTAMP_CONVERTER_RESULT_SECONDS');
            $toolUi['tsResultMs']                     = trans('TIMESTAMP_CONVERTER_RESULT_MS');
        }
        if ($cipherAlias === 'json-formatter') {
            $toolUi['jsonFormatterMode']          = true;
            $toolUi['tabEncode']                  = trans('JSON_FORMATTER_TAB_FORMAT');
            $toolUi['tabDecode']                  = trans('JSON_FORMATTER_TAB_MINIFY');
            $toolUi['placeholderEncode']          = trans('JSON_FORMATTER_PLACEHOLDER_FORMAT');
            $toolUi['placeholderDecode']          = trans('JSON_FORMATTER_PLACEHOLDER_MINIFY');
            $toolUi['jsonFormatterErrInvalid']    = trans('JSON_FORMATTER_ERR_INVALID');
            $toolUi['jsonFormatterErrAt']         = trans('JSON_FORMATTER_ERR_AT');
            $toolUi['jsonFormatterViewText']      = trans('JSON_FORMATTER_VIEW_TEXT');
            $toolUi['jsonFormatterViewTree']      = trans('JSON_FORMATTER_VIEW_TREE');
            $toolUi['jsonFormatterWarnDuplicate'] = trans('JSON_FORMATTER_WARN_DUPLICATE');
            $toolUi['jsonFormatterStatObjects']   = trans('JSON_FORMATTER_STAT_OBJECTS');
            $toolUi['jsonFormatterStatArrays']    = trans('JSON_FORMATTER_STAT_ARRAYS');
            $toolUi['jsonFormatterStatKeys']      = trans('JSON_FORMATTER_STAT_KEYS');
            $toolUi['jsonFormatterStatDepth']     = trans('JSON_FORMATTER_STAT_DEPTH');
            $toolUi['jsonFormatterSortLabel']     = trans('JSON_FORMATTER_SORT_KEYS');
            $toolUi['jsonFormatterDownloadLabel'] = trans('JSON_FORMATTER_DOWNLOAD');
        }
        if ($cipherAlias === 'dancing-men') {
            $toolUi['dancingMenMode']          = true;
            $toolUi['oneWayMode']              = true;
            $toolUi['placeholderEncode']       = trans('DANCING_MEN_PLACEHOLDER');
            $toolUi['dancingMenEmptyLabel']      = trans('DANCING_MEN_EMPTY');
            $toolUi['dancingMenWarnUnsupported'] = trans('DANCING_MEN_WARN_UNSUPPORTED');
            $toolUi['dancingMenDownloaded']      = trans('DANCING_MEN_DOWNLOADED');
            $toolUi['dancingMenDownloadLabel']   = trans('DANCING_MEN_DOWNLOAD_BTN');
        }
        if ($cipherAlias === 'pigpen') {
            $toolUi['pigpenMode']            = true;
            $toolUi['placeholderEncode']     = trans('PIGPEN_PLACEHOLDER');
            $toolUi['pigpenEmptyLabel']      = trans('PIGPEN_EMPTY');
            $toolUi['pigpenWarnUnsupported'] = trans('PIGPEN_WARN_UNSUPPORTED');
            $toolUi['visualDownloadLabel']   = trans('PIGPEN_DOWNLOAD_BTN');
            $toolUi['pigpenKeyboardHint']    = trans('PIGPEN_KEYBOARD_HINT');
            $toolUi['pigpenSpaceLabel']      = trans('PIGPEN_SPACE');
            $toolUi['pigpenBackspaceLabel']  = trans('PIGPEN_BACKSPACE');
            $toolUi['pigpenClearLabel']      = trans('PIGPEN_CLEAR');
        }
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
        if ($cipherAlias === 'alberti') {
            $toolUi['albertiWheelMode']        = true;
            $toolUi['albertiWheelDiskLabel']    = trans('ALBERTI_WHEEL_DISK_LABEL');
            $toolUi['albertiWheelMappingLabel'] = trans('ALBERTI_WHEEL_MAPPING_LABEL');
        }
        if ($cipherAlias === 'enigma') {
            $toolUi['enigmaMode']            = true;
            $toolUi['enigmaVisualTitle']     = trans('ENIGMA_VISUAL_TITLE');
            $toolUi['enigmaVisualRotors']    = trans('ENIGMA_VISUAL_ROTOR_LABEL');
            $toolUi['enigmaVisualReflector'] = trans('ENIGMA_VISUAL_REFLECTOR_LABEL');
            $toolUi['enigmaVisualPlugboard'] = trans('ENIGMA_VISUAL_PLUGBOARD_LABEL');
            $toolUi['enigmaVisualStart']     = trans('ENIGMA_VISUAL_START_LABEL');
            $toolUi['enigmaVisualFinal']     = trans('ENIGMA_VISUAL_FINAL_LABEL');
            $toolUi['enigmaVisualLetters']   = trans('ENIGMA_VISUAL_LETTERS_LABEL');
            $toolUi['enigmaVisualEmpty']     = trans('ENIGMA_VISUAL_PLUGBOARD_EMPTY');
            $toolUi['enigmaVisualReset']     = trans('ENIGMA_VISUAL_RESET_LABEL');
            $toolUi['enigmaVisualRandom']    = trans('ENIGMA_VISUAL_RANDOM_LABEL');
        }
        if ($cipherAlias === 'anagram-solver') {
            $toolUi['anagramMode']            = true;
            $toolUi['disableLiveMode']        = true;
            $toolUi['inputMaxLength']         = AnagramSolverApiCipherTool::MAX_TEXT_LENGTH;
            $toolUi['tabEncode']              = trans('ANAGRAM_TAB_SOLVE');
            $toolUi['placeholderEncode']      = trans('ANAGRAM_PLACEHOLDER');
            $toolUi['anagramEmptyLabel']      = trans('ANAGRAM_EMPTY');
            $toolUi['anagramNoMatchesLabel']  = trans('ANAGRAM_NO_MATCHES');
            $toolUi['anagramFoundLabel']      = trans('ANAGRAM_FOUND');
            $toolUi['anagramTruncatedLabel']  = trans('ANAGRAM_TRUNCATED');
            $toolUi['anagramCopyLabel']       = trans('ANAGRAM_COPY');
            $toolUi['anagramAdvancedLabel']   = trans('ANAGRAM_ADVANCED');
            $toolUi['anagramAnyLabel']        = trans('ANAGRAM_ANY');
            $toolUi['anagramMinLengthLabel']  = trans('ANAGRAM_MIN_LENGTH');
            $toolUi['anagramMaxLengthLabel']  = trans('ANAGRAM_MAX_LENGTH');
            $toolUi['anagramStartsWithLabel'] = trans('ANAGRAM_STARTS_WITH');
            $toolUi['anagramEndsWithLabel']   = trans('ANAGRAM_ENDS_WITH');
            $toolUi['anagramContainsLabel']   = trans('ANAGRAM_CONTAINS');
            $toolUi['anagramMaxResultsLabel'] = trans('ANAGRAM_MAX_RESULTS');
            $toolUi['anagramMaxWordsLabel']   = trans('ANAGRAM_MAX_WORDS');
            $toolUi['anagramSortLabel']       = trans('ANAGRAM_SORT');
            $toolUi['anagramSortLength']      = trans('ANAGRAM_SORT_LENGTH');
            $toolUi['anagramSortScore']       = trans('ANAGRAM_SORT_SCORE');
            $toolUi['anagramSortAlpha']       = trans('ANAGRAM_SORT_ALPHA');
        }
        if ($categoryAlias === 'hashing') {
            $toolUi = match ($cipherAlias) {
                'hmac' => HmacToolUi::apply($toolUi),
                'pbkdf2', 'bcrypt', 'argon2' => KdfToolUi::apply($toolUi, $cipherAlias),
                default => HashingToolUi::apply($toolUi, $cipherAlias),
            };
        }
        if ($cipherAlias === 'cipher-identifier') {
            $toolUi['identifierMode']            = true;
            $toolUi['disableLiveMode']           = true;
            $toolUi['inputMaxLength']            = CipherIdentifierApiCipherTool::MAX_TEXT_LENGTH;
            $toolUi['cidEmptyLabel']             = trans('CIPHER_IDENTIFIER_EMPTY_LABEL');
            $toolUi['cidNoCandidatesMsg']        = trans('CIPHER_IDENTIFIER_NO_CANDIDATES');
            $toolUi['cidAutoResultTitle']        = trans('CIPHER_IDENTIFIER_AUTO_RESULT_TITLE');
            $toolUi['cidCandidatesTitle']        = trans('CIPHER_IDENTIFIER_CANDIDATES_TITLE');
            $toolUi['cidColCipher']              = trans('CIPHER_IDENTIFIER_COLUMN_CIPHER');
            $toolUi['cidColConfidence']          = trans('CIPHER_IDENTIFIER_COLUMN_CONFIDENCE');
            $toolUi['cidColEvidence']            = trans('CIPHER_IDENTIFIER_COLUMN_EVIDENCE');
            $toolUi['cidColAction']              = trans('CIPHER_IDENTIFIER_COLUMN_ACTION');
            $toolUi['cidOpenTool']               = trans('CIPHER_IDENTIFIER_OPEN_TOOL');
            $toolUi['cidCrackBtn']               = trans('CIPHER_IDENTIFIER_CRACK_BTN');
            $toolUi['cidCrackRunning']           = trans('CIPHER_IDENTIFIER_CRACK_RUNNING');
            $toolUi['cidCrackFailed']            = trans('CIPHER_IDENTIFIER_CRACK_FAILED');
            $toolUi['cidCrackKey']               = trans('CIPHER_IDENTIFIER_CRACK_KEY');
            $toolUi['cidTranslations']           = self::cipherNameTranslations();
        }
        if ($cipherAlias === 'cipher-solver') {
            $toolUi['solverMode']            = true;
            $toolUi['disableLiveMode']       = true;
            $toolUi['inputMaxLength']        = SolverApiCipherTool::MAX_TEXT_LENGTH;
            $toolUi['solverEmptyLabel']      = trans('SOLVER_EMPTY_LABEL');
            $toolUi['solverNoAnswersMsg']    = trans('SOLVER_NO_ANSWERS');
            $toolUi['solverBestTitle']       = trans('SOLVER_BEST_TITLE');
            $toolUi['solverAnswersTitle']    = trans('SOLVER_ANSWERS_TITLE');
            $toolUi['solverTypesToggle']     = trans('SOLVER_TYPES_TOGGLE');
            $toolUi['solverOpenTool']        = trans('SOLVER_OPEN_TOOL');
            $toolUi['solverCopyLabel']       = trans('SOLVER_COPY_LABEL');
            $toolUi['solverColCipher']       = trans('CIPHER_IDENTIFIER_COLUMN_CIPHER');
            $toolUi['solverColConfidence']   = trans('CIPHER_IDENTIFIER_COLUMN_CONFIDENCE');
            $toolUi['solverColEvidence']     = trans('CIPHER_IDENTIFIER_COLUMN_EVIDENCE');
            $toolUi['solverTranslations']    = self::cipherNameTranslations();
        }
        if ($cipherAlias === 'text-diff') {
            $toolUi['diffMode']                = true;
            $toolUi['diffGranularityLabel']    = trans('DIFF_GRANULARITY_LABEL');
            $toolUi['diffGranularityWord']     = trans('DIFF_GRANULARITY_WORD');
            $toolUi['diffGranularityChar']     = trans('DIFF_GRANULARITY_CHAR');
            $toolUi['diffGranularityLine']     = trans('DIFF_GRANULARITY_LINE');
            $toolUi['diffViewLabel']           = trans('DIFF_VIEW_LABEL');
            $toolUi['diffViewSplit']           = trans('DIFF_VIEW_SPLIT');
            $toolUi['diffViewInline']          = trans('DIFF_VIEW_INLINE');
            $toolUi['diffIgnoreCase']          = trans('DIFF_IGNORE_CASE');
            $toolUi['diffIgnoreWhitespace']    = trans('DIFF_IGNORE_WHITESPACE');
            $toolUi['diffTrim']                = trans('DIFF_TRIM');
            $toolUi['diffIgnoreEmpty']         = trans('DIFF_IGNORE_EMPTY');
            $toolUi['diffSortLines']           = trans('DIFF_SORT_LINES');
            $toolUi['diffOnlyChanges']         = trans('DIFF_ONLY_CHANGES');
            $toolUi['diffOriginalLabel']       = trans('DIFF_ORIGINAL_LABEL');
            $toolUi['diffChangedLabel']        = trans('DIFF_CHANGED_LABEL');
            $toolUi['diffLinesLabel']          = trans('DIFF_LINES_LABEL');
            $toolUi['diffPlaceholderOriginal'] = trans('DIFF_PLACEHOLDER_ORIGINAL');
            $toolUi['diffPlaceholderChanged']  = trans('DIFF_PLACEHOLDER_CHANGED');
            $toolUi['diffSwapLabel']           = trans('DIFF_SWAP_LABEL');
            $toolUi['diffCopyLabel']           = trans('DIFF_COPY_LABEL');
            $toolUi['diffPrevLabel']           = trans('DIFF_PREV_LABEL');
            $toolUi['diffNextLabel']           = trans('DIFF_NEXT_LABEL');
            $toolUi['diffEmptyLabel']          = trans('DIFF_EMPTY_LABEL');
            $toolUi['diffIdentical']           = trans('DIFF_IDENTICAL');
            $toolUi['diffSimilarity']          = trans('DIFF_SIMILARITY');
            $toolUi['diffStatAdded']           = trans('DIFF_STAT_ADDED');
            $toolUi['diffStatRemoved']         = trans('DIFF_STAT_REMOVED');
            $toolUi['diffStatModified']        = trans('DIFF_STAT_MODIFIED');
            $toolUi['diffUnchangedGap']        = trans('DIFF_UNCHANGED_GAP');
            $toolUi['diffChangePos']           = trans('DIFF_CHANGE_POS');
            $toolUi['diffCopied']              = trans('DIFF_COPIED');
            $toolUi['diffCopyFailed']          = trans('DIFF_COPY_FAILED');
        }
        if ($cipherAlias === 'uuid-generator') {
            $toolUi['uuidMode']               = true;
            $toolUi['disableLiveMode']        = true;
            $toolUi['uuidVersionLabel']       = trans('UUID_VERSION_LABEL');
            $toolUi['uuidVersionV4']          = trans('UUID_VERSION_V4');
            $toolUi['uuidVersionV7']          = trans('UUID_VERSION_V7');
            $toolUi['uuidVersionV1']          = trans('UUID_VERSION_V1');
            $toolUi['uuidVersionV3']          = trans('UUID_VERSION_V3');
            $toolUi['uuidVersionV5']          = trans('UUID_VERSION_V5');
            $toolUi['uuidVersionNil']         = trans('UUID_VERSION_NIL');
            $toolUi['uuidVersionMax']         = trans('UUID_VERSION_MAX');
            $toolUi['uuidCountLabel']         = trans('UUID_COUNT_LABEL');
            $toolUi['uuidNamespaceLabel']     = trans('UUID_NAMESPACE_LABEL');
            $toolUi['uuidNamespaceDns']       = trans('UUID_NAMESPACE_DNS');
            $toolUi['uuidNamespaceUrl']       = trans('UUID_NAMESPACE_URL');
            $toolUi['uuidNamespaceOid']       = trans('UUID_NAMESPACE_OID');
            $toolUi['uuidNamespaceX500']      = trans('UUID_NAMESPACE_X500');
            $toolUi['uuidNamespaceCustom']    = trans('UUID_NAMESPACE_CUSTOM');
            $toolUi['uuidNamespacePlaceholder'] = trans('UUID_NAMESPACE_PLACEHOLDER');
            $toolUi['uuidNameLabel']          = trans('UUID_NAME_LABEL');
            $toolUi['uuidNamePlaceholder']    = trans('UUID_NAME_PLACEHOLDER');
            $toolUi['uuidFormatLabel']        = trans('UUID_FORMAT_LABEL');
            $toolUi['uuidFormatUppercase']    = trans('UUID_FORMAT_UPPERCASE');
            $toolUi['uuidFormatHyphens']      = trans('UUID_FORMAT_HYPHENS');
            $toolUi['uuidFormatBraces']       = trans('UUID_FORMAT_BRACES');
            $toolUi['uuidFormatUrn']          = trans('UUID_FORMAT_URN');
            $toolUi['uuidGenerateLabel']      = trans('UUID_GENERATE');
            $toolUi['uuidRegenerateLabel']    = trans('UUID_REGENERATE');
            $toolUi['uuidCopyAllLabel']       = trans('UUID_COPY_ALL');
            $toolUi['uuidDownloadLabel']      = trans('UUID_DOWNLOAD');
            $toolUi['uuidCopiedLabel']        = trans('UUID_COPIED');
            $toolUi['uuidErrNameRequired']    = trans('UUID_ERR_NAME_REQUIRED');
            $toolUi['uuidErrNamespaceInvalid'] = trans('UUID_ERR_NAMESPACE_INVALID');
        }
        if ($cipherAlias === 'password-generator') {
            $toolUi['passwordMode']            = true;
            $toolUi['disableLiveMode']         = true;
            $toolUi['pwdModePassword']         = trans('PASSWORD_MODE_PASSWORD');
            $toolUi['pwdModePassphrase']       = trans('PASSWORD_MODE_PASSPHRASE');
            $toolUi['pwdLengthLabel']          = trans('PASSWORD_LENGTH_LABEL');
            $toolUi['pwdCountLabel']           = trans('PASSWORD_COUNT_LABEL');
            $toolUi['pwdSetsLabel']            = trans('PASSWORD_SETS_LABEL');
            $toolUi['pwdSetLower']             = trans('PASSWORD_SET_LOWER');
            $toolUi['pwdSetUpper']             = trans('PASSWORD_SET_UPPER');
            $toolUi['pwdSetDigits']            = trans('PASSWORD_SET_DIGITS');
            $toolUi['pwdSetSymbols']           = trans('PASSWORD_SET_SYMBOLS');
            $toolUi['pwdOptExcludeSimilar']    = trans('PASSWORD_OPT_EXCLUDE_SIMILAR');
            $toolUi['pwdOptExcludeAmbiguous']  = trans('PASSWORD_OPT_EXCLUDE_AMBIGUOUS');
            $toolUi['pwdOptNoRepeats']         = trans('PASSWORD_OPT_NO_REPEATS');
            $toolUi['pwdWordsLabel']           = trans('PASSWORD_WORDS_LABEL');
            $toolUi['pwdSeparatorLabel']       = trans('PASSWORD_SEPARATOR_LABEL');
            $toolUi['pwdSepHyphen']            = trans('PASSWORD_SEP_HYPHEN');
            $toolUi['pwdSepDot']               = trans('PASSWORD_SEP_DOT');
            $toolUi['pwdSepSpace']             = trans('PASSWORD_SEP_SPACE');
            $toolUi['pwdSepUnderscore']        = trans('PASSWORD_SEP_UNDERSCORE');
            $toolUi['pwdCaseLabel']            = trans('PASSWORD_CASE_LABEL');
            $toolUi['pwdCaseLower']            = trans('PASSWORD_CASE_LOWER');
            $toolUi['pwdCaseCapitalize']       = trans('PASSWORD_CASE_CAPITALIZE');
            $toolUi['pwdCaseUpper']            = trans('PASSWORD_CASE_UPPER');
            $toolUi['pwdOptAddNumber']         = trans('PASSWORD_OPT_ADD_NUMBER');
            $toolUi['pwdGenerateLabel']        = trans('PASSWORD_GENERATE');
            $toolUi['pwdStrengthLabel']        = trans('PASSWORD_STRENGTH_LABEL');
            $toolUi['pwdStrength0']            = trans('PASSWORD_STRENGTH_0');
            $toolUi['pwdStrength1']            = trans('PASSWORD_STRENGTH_1');
            $toolUi['pwdStrength2']            = trans('PASSWORD_STRENGTH_2');
            $toolUi['pwdStrength3']            = trans('PASSWORD_STRENGTH_3');
            $toolUi['pwdStrength4']            = trans('PASSWORD_STRENGTH_4');
            $toolUi['pwdEntropyLabel']         = trans('PASSWORD_ENTROPY_LABEL');
            $toolUi['pwdBitsLabel']            = trans('PASSWORD_BITS_LABEL');
            $toolUi['pwdCrackLabel']           = trans('PASSWORD_CRACK_LABEL');
            $toolUi['pwdCrackInstant']         = trans('PASSWORD_CRACK_INSTANT');
            $toolUi['pwdCrackCenturies']       = trans('PASSWORD_CRACK_CENTURIES');
            $toolUi['pwdCopiedLabel']          = trans('PASSWORD_COPIED');
            $toolUi['pwdErrNoCharset']         = trans('PASSWORD_ERR_NO_CHARSET');
            $toolUi['pwdErrTooLongNoRepeat']   = trans('PASSWORD_ERR_TOO_LONG_NO_REPEAT');
        }

        return $toolUi;
    }

    /**
     * Возвращает карту «ключ перевода → перевод» для названий шифров и evidence-меток.
     *
     * Используется и определителем шифра, и авто-солвером: оба рендерят на клиенте
     * имена шифров-кандидатов и объяснения-свидетельства по ключам из ответа API.
     *
     * @return array<string, string>
     */
    private static function cipherNameTranslations(): array
    {
        return [
            'CIPHER_NAME_BASE64'               => trans('CIPHER_NAME_BASE64'),
            'CIPHER_NAME_HEX'                  => trans('CIPHER_NAME_HEX'),
            'CIPHER_NAME_BINARY'               => trans('CIPHER_NAME_BINARY'),
            'CIPHER_NAME_MORSE_CODE'           => trans('CIPHER_NAME_MORSE_CODE'),
            'CIPHER_NAME_BACON'                => trans('CIPHER_NAME_BACON'),
            'CIPHER_NAME_A1Z26'                => trans('CIPHER_NAME_A1Z26'),
            'CIPHER_NAME_POLYBIUS_SQUARE'      => trans('CIPHER_NAME_POLYBIUS_SQUARE'),
            'CIPHER_NAME_URL_ENCODE'           => trans('CIPHER_NAME_URL_ENCODE'),
            'CIPHER_NAME_JWT'                  => trans('CIPHER_NAME_JWT'),
            'CIPHER_NAME_UNICODE'              => trans('CIPHER_NAME_UNICODE'),
            'CIPHER_NAME_CAESAR'               => trans('CIPHER_NAME_CAESAR'),
            'CIPHER_NAME_ROT13'                => trans('CIPHER_NAME_ROT13'),
            'CIPHER_NAME_ROT47'                => trans('CIPHER_NAME_ROT47'),
            'CIPHER_NAME_ATBASH'               => trans('CIPHER_NAME_ATBASH'),
            'CIPHER_NAME_AFFINE'               => trans('CIPHER_NAME_AFFINE'),
            'CIPHER_NAME_SIMPLE_SUBSTITUTION'  => trans('CIPHER_NAME_SIMPLE_SUBSTITUTION'),
            'CIPHER_NAME_XOR'                  => trans('CIPHER_NAME_XOR'),
            'CIPHER_NAME_VIGENERE'             => trans('CIPHER_NAME_VIGENERE'),
            'CIPHER_NAME_BEAUFORT'             => trans('CIPHER_NAME_BEAUFORT'),
            'CIPHER_NAME_AUTOKEY'              => trans('CIPHER_NAME_AUTOKEY'),
            'CIPHER_NAME_GRONSFELD'            => trans('CIPHER_NAME_GRONSFELD'),
            'CIPHER_NAME_ALBERTI'              => trans('CIPHER_NAME_ALBERTI'),
            'CIPHER_NAME_BIFID'                => trans('CIPHER_NAME_BIFID'),
            'CIPHER_NAME_TRIFID'               => trans('CIPHER_NAME_TRIFID'),
            'CIPHER_NAME_RAIL_FENCE'           => trans('CIPHER_NAME_RAIL_FENCE'),
            'CIPHER_NAME_COLUMNAR_TRANSPOSITION' => trans('CIPHER_NAME_COLUMNAR_TRANSPOSITION'),
            'CIPHER_NAME_PLAYFAIR'             => trans('CIPHER_NAME_PLAYFAIR'),
            'CIPHER_NAME_HILL'                 => trans('CIPHER_NAME_HILL'),
            'CIPHER_NAME_VERNAM'               => trans('CIPHER_NAME_VERNAM'),
            'CID_EV_CHARSET_LETTERS'           => trans('CID_EV_CHARSET_LETTERS'),
            'CID_EV_CHARSET_HEX'               => trans('CID_EV_CHARSET_HEX'),
            'CID_EV_CHARSET_BASE64'            => trans('CID_EV_CHARSET_BASE64'),
            'CID_EV_CHARSET_BINARY'            => trans('CID_EV_CHARSET_BINARY'),
            'CID_EV_CHARSET_MORSE'             => trans('CID_EV_CHARSET_MORSE'),
            'CID_EV_CHARSET_BACON'             => trans('CID_EV_CHARSET_BACON'),
            'CID_EV_CHARSET_NUMBERS'           => trans('CID_EV_CHARSET_NUMBERS'),
            'CID_EV_LENGTH_MULTIPLE_OF'        => trans('CID_EV_LENGTH_MULTIPLE_OF'),
            'CID_EV_IOC_MONO'                  => trans('CID_EV_IOC_MONO'),
            'CID_EV_IOC_POLY'                  => trans('CID_EV_IOC_POLY'),
            'CID_EV_IOC_PRESERVED'             => trans('CID_EV_IOC_PRESERVED'),
            'CID_EV_CHISQ_BEST_SHIFT'          => trans('CID_EV_CHISQ_BEST_SHIFT'),
            'CID_EV_AMBIGUOUS_POLYALPHA'       => trans('CID_EV_AMBIGUOUS_POLYALPHA'),
            'CID_EV_LOW_SAMPLE'                => trans('CID_EV_LOW_SAMPLE'),
            'CID_EV_BIGRAM_READABLE'           => trans('CID_EV_BIGRAM_READABLE'),
            'CID_EV_IOC_COLUMNS_PEAK'          => trans('CID_EV_IOC_COLUMNS_PEAK'),
            'CID_EV_KASISKI_AGREE'             => trans('CID_EV_KASISKI_AGREE'),
            'CID_EV_COMMON_WORDS'              => trans('CID_EV_COMMON_WORDS'),
            'CID_EV_FILE_SIGNATURE'            => trans('CID_EV_FILE_SIGNATURE'),
        ];
    }
}
