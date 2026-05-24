<?php

declare(strict_types=1);

namespace App\I18n;

/**
 * Форматирует строки в подмножестве стандарта ICU MessageFormat.
 *
 * Поддерживаемые конструкции:
 *   {var}                             — простая подстановка переменной
 *   {var, plural, one {…} other {…}}  — выбор формы по числу; # внутри = значение
 *   {var, select, val {…} other {…}}  — выбор по строковому значению
 *
 * В plural поддерживаются CLDR-категории (zero/one/few/many/other) и точные
 * значения (=0, =1, …). Паттерны могут быть вложены рекурсивно.
 */
final class IcuFormatter
{
    /**
     * Форматирует паттерн с подстановкой параметров.
     *
     * @param string               $pattern ICU-строка с плейсхолдерами.
     * @param string               $locale  Код локали для plural-правил.
     * @param array<string, mixed> $params  Именованные параметры.
     */
    public static function format(string $pattern, string $locale, array $params): string
    {
        return self::parseSegment($pattern, $locale, $params);
    }

    // ── Парсинг сегмента ─────────────────────────────────────────────────────

    /**
     * Обходит строку посимвольно и обрабатывает каждый плейсхолдер верхнего уровня.
     *
     * @param array<string, mixed> $params
     */
    private static function parseSegment(string $pattern, string $locale, array $params): string
    {
        $result = '';
        $len    = strlen($pattern);
        $i      = 0;

        while ($i < $len) {
            if ($pattern[$i] !== '{') {
                $result .= $pattern[$i++];
                continue;
            }

            // Ищем закрывающую «}» с учётом вложенности
            $depth = 1;
            $j     = $i + 1;
            while ($j < $len && $depth > 0) {
                if ($pattern[$j] === '{') {
                    $depth++;
                } elseif ($pattern[$j] === '}') {
                    $depth--;
                }
                $j++;
            }

            // Содержимое без внешних скобок
            $content = substr($pattern, $i + 1, $j - $i - 2);
            $result .= self::processPlaceholder($content, $locale, $params);
            $i = $j;
        }

        return $result;
    }

    // ── Обработка плейсхолдера ────────────────────────────────────────────────

    /**
     * Разбирает содержимое одного плейсхолдера и возвращает подставленное значение.
     *
     * @param array<string, mixed> $params
     */
    private static function processPlaceholder(string $content, string $locale, array $params): string
    {
        // Ищем первую «,» не внутри вложенных {}
        $depth   = 0;
        $commaAt = -1;

        for ($i = 0, $len = strlen($content); $i < $len; $i++) {
            if ($content[$i] === '{') {
                $depth++;
            } elseif ($content[$i] === '}') {
                $depth--;
            } elseif ($content[$i] === ',' && $depth === 0) {
                $commaAt = $i;
                break;
            }
        }

        if ($commaAt === -1) {
            // Простая переменная {varName}
            $varName = trim($content);
            return isset($params[$varName]) ? (string) $params[$varName] : '{' . $varName . '}';
        }

        $varName = trim(substr($content, 0, $commaAt));
        $rest    = ltrim(substr($content, $commaAt + 1));
        $comma2  = strpos($rest, ',');

        if ($comma2 === false) {
            return isset($params[$varName]) ? (string) $params[$varName] : '{' . $content . '}';
        }

        $type     = strtolower(trim(substr($rest, 0, $comma2)));
        $casesStr = ltrim(substr($rest, $comma2 + 1));
        $value    = $params[$varName] ?? null;

        return match ($type) {
            'plural' => self::processPluralCases($casesStr, $locale, $value, $params),
            'select' => self::processSelectCases($casesStr, $locale, (string) ($value ?? ''), $params),
            default  => '{' . $content . '}',
        };
    }

    // ── Plural ────────────────────────────────────────────────────────────────

    /**
     * Выбирает нужный plural-кейс и рекурсивно форматирует его текст.
     *
     * @param array<string, mixed> $params
     */
    private static function processPluralCases(
        string $casesStr,
        string $locale,
        mixed $value,
        array $params,
    ): string {
        $count    = (int) ($value ?? 0);
        $category = PluralRules::category($locale, $count);
        $cases    = self::parseCases($casesStr);

        // Точное совпадение =N имеет приоритет над категорией
        $text = $cases['=' . $count]
            ?? $cases[$category]
            ?? $cases['other']
            ?? '';

        // «#» внутри plural-кейса — сокращение для числа
        $text = str_replace('#', (string) $count, $text);

        return self::parseSegment($text, $locale, array_merge($params, ['#' => $count]));
    }

    // ── Select ────────────────────────────────────────────────────────────────

    /**
     * Выбирает нужный select-кейс и рекурсивно форматирует его текст.
     *
     * @param array<string, mixed> $params
     */
    private static function processSelectCases(
        string $casesStr,
        string $locale,
        string $value,
        array $params,
    ): string {
        $cases = self::parseCases($casesStr);
        $text  = $cases[$value] ?? $cases['other'] ?? '';

        return self::parseSegment($text, $locale, $params);
    }

    // ── Парсинг кейсов ────────────────────────────────────────────────────────

    /**
     * Разбирает строку кейсов «key1 {text1} key2 {text2} …» в ассоциативный массив.
     *
     * @return array<string, string>
     */
    private static function parseCases(string $casesStr): array
    {
        $cases = [];
        $i     = 0;
        $len   = strlen($casesStr);

        while ($i < $len) {
            // Пропустить пробелы
            while ($i < $len && ctype_space($casesStr[$i])) {
                $i++;
            }
            if ($i >= $len) {
                break;
            }

            // Читать ключ до «{»
            $keyStart = $i;
            while ($i < $len && $casesStr[$i] !== '{') {
                $i++;
            }
            $key = trim(substr($casesStr, $keyStart, $i - $keyStart));

            if ($i >= $len || $key === '') {
                break;
            }

            // Читать значение {…} с учётом вложенных скобок
            $i++;           // пропустить «{»
            $depth      = 1;
            $valueStart = $i;

            while ($i < $len && $depth > 0) {
                if ($casesStr[$i] === '{') {
                    $depth++;
                } elseif ($casesStr[$i] === '}') {
                    $depth--;
                }
                $i++;
            }

            $cases[$key] = substr($casesStr, $valueStart, $i - $valueStart - 1);
        }

        return $cases;
    }
}
