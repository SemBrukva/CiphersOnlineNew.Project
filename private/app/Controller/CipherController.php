<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cipher\AffineBruteForceApiCipherTool;
use App\Cipher\BaseToolUiFactory;
use App\Cipher\HashingToolUi;
use App\Cipher\HmacToolUi;
use App\Cipher\KdfToolUi;
use App\Cipher\ToolRegistry;
use App\Cipher\VigenereCrackerApiCipherTool;
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
        private BaseToolUiFactory $uiFactory
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

        $related = $this->buildRelatedTools($toolSlug, $cipherAlias, (int) $cipher['category_id'], $language, $defaultLanguage);

        $title = (string) ($cipher['meta_title'] ?: $cipher['name']);
        $metaDescription = (string) ($cipher['meta_description'] ?: $cipher['description']);
        $calculationMode = (string) ($cipher['calculation_mode'] ?? 'client');
        $toolUi = $this->uiFactory->build($toolSlug, $calculationMode);
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
            $toolUi['inputMaxLength']         = \App\Cipher\AnagramSolverApiCipherTool::MAX_TEXT_LENGTH;
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
            $toolUi['inputMaxLength']            = \App\Cipher\CipherIdentifierApiCipherTool::MAX_TEXT_LENGTH;
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
            $toolUi['cidTranslations']           = [
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

        foreach ((array) ($toolUi['settings'] ?? []) as $setting) {
            if (!is_array($setting)) {
                continue;
            }
            $id = (string) ($setting['id'] ?? '');
            $label = (string) ($setting['label'] ?? '');
            if ($id !== '' && $label !== '') {
                $idToLabel[$id] = $label;
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
                $badges[] = ['label' => $idToLabel[$fieldId], 'value' => $scalar];
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
