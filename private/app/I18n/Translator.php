<?php

declare(strict_types=1);

namespace App\I18n;

/**
 * Сервис переводов приложения.
 *
 * Загружает файл переводов для текущей локали и возвращает строки по ключам.
 * Поддерживает подстановку параметров в двух форматах:
 *   - Классический: «:param» → значение (обратная совместимость).
 *   - ICU MessageFormat: «{param}», «{n, plural, …}», «{v, select, …}».
 *
 * Является изменяемым синглтоном — локаль устанавливается один раз за запрос
 * через LocaleMiddleware.
 */
final class Translator
{
    /** @var array<string, string> Загруженные переводы для текущей локали. */
    private array $translations = [];

    /** @var bool Флаг того, что файл переводов уже загружен. */
    private bool $loaded = false;

    /** @var string Текущая активная локаль. */
    private string $locale;

    /**
     * Создаёт экземпляр сервиса переводов.
     *
     * @param array<string, mixed> $config Конфигурация из config/locale.php.
     */
    public function __construct(private readonly array $config)
    {
        $this->locale = (string) ($config['locale'] ?? ($config['locales'][0] ?? ''));
    }

    /**
     * Устанавливает текущую локаль и сбрасывает кэш переводов.
     */
    public function setLocale(string $locale): void
    {
        if ($this->locale !== $locale) {
            $this->locale       = $locale;
            $this->loaded       = false;
            $this->translations = [];
        }
    }

    /**
     * Возвращает текущую локаль.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Возвращает локаль по умолчанию из конфигурации.
     */
    public function getDefaultLocale(): string
    {
        return (string) ($this->config['locale'] ?? ($this->config['locales'][0] ?? ''));
    }

    /**
     * Возвращает true, если мультиязычность включена.
     */
    public function isMultilang(): bool
    {
        return (bool) ($this->config['multilang'] ?? false);
    }

    /**
     * Возвращает список доступных локалей.
     *
     * @return string[]
     */
    public function getLocales(): array
    {
        return $this->config['locales'] ?? [];
    }

    /**
     * Возвращает перевод по ключу.
     * Если ключ не найден — возвращает сам ключ.
     *
     * Поддерживаются два формата подстановки параметров:
     *   - Классический: «:param» → значение (если в строке нет ICU-паттернов).
     *   - ICU: «{param}», «{n, plural, one {…} other {…}}», «{v, select, …}»
     *     (активируется автоматически, если строка содержит «{letter»).
     *
     * @param array<string, mixed> $replace Параметры для подстановки.
     */
    public function get(string $key, array $replace = []): string
    {
        $this->load();

        $text = $this->translations[$key] ?? $key;

        if (self::hasIcuPattern($text)) {
            return IcuFormatter::format($text, $this->locale, $replace);
        }

        foreach ($replace as $param => $value) {
            $text = str_replace(':' . $param, (string) $value, $text);
        }

        return $text;
    }

    /**
     * Возвращает перевод в нужной форме множественного числа.
     *
     * Строка перевода должна содержать формы, разделённые символом «|»:
     *   - 2 формы (en, de, …): «:count item|:count items»
     *   - 3 формы (ru, uk, pl, …): «:count элемент|:count элемента|:count элементов»
     *
     * Параметр «count» добавляется в $replace автоматически.
     * Поддерживает как «:param», так и «{param}» в формах.
     *
     * @param int|float            $count   Число для определения формы.
     * @param array<string, mixed> $replace Дополнительные параметры для подстановки.
     */
    public function choice(string $key, int|float $count, array $replace = []): string
    {
        $this->load();

        $raw   = $this->translations[$key] ?? $key;
        $forms = explode('|', $raw);
        $index = PluralRules::select($this->locale, $count);
        $text  = $forms[min($index, count($forms) - 1)];

        $replace = array_merge(['count' => $count], $replace);

        if (self::hasIcuPattern($text)) {
            return IcuFormatter::format($text, $this->locale, $replace);
        }

        foreach ($replace as $param => $value) {
            $text = str_replace(':' . $param, (string) $value, $text);
        }

        return $text;
    }

    /**
     * Возвращает true, если строка содержит ICU-плейсхолдер ({letter…}).
     *
     * Проверяет наличие «{» за которой сразу идёт буква или «_»,
     * чтобы избежать ложных срабатываний на произвольные «{» в тексте.
     */
    private static function hasIcuPattern(string $text): bool
    {
        return (bool) preg_match('/\{[a-zA-Z_]/', $text);
    }

    /**
     * Возвращает все переводы текущей локали.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        $this->load();

        return $this->translations;
    }

    /**
     * Загружает файл переводов для текущей локали (ленивая загрузка).
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;
        $file = rtrim($this->config['path'] ?? '', '/') . '/' . $this->locale . '.php';

        if (is_file($file)) {
            $this->translations = require $file;
        }
    }
}
