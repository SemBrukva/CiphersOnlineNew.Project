<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис идентификации шифра/кодировки по произвольному тексту.
 *
 * Запускает все зарегистрированные детекторы и возвращает список кандидатов,
 * отсортированных по confidence по убыванию.
 */
final readonly class CipherIdentifierService
{
    /**
     * Порог confidence для автозапуска brute-force.
     */
    public const float AUTO_THRESHOLD = 0.70;

    /**
     * Минимальный отрыв лидера от второго кандидата для автозапуска brute-force.
     */
    public const float AUTO_GAP = 0.10;

    /**
     * Создаёт экземпляр сервиса с зарегистрированными детекторами.
     *
     * @param CipherDetectorInterface[] $detectors Список детекторов.
     */
    public function __construct(
        private array $detectors,
        private LetterFrequencyScorer $scorer,
        private IndexOfCoincidence $ioc,
    ) {
    }

    /**
     * Запускает все детекторы и возвращает кандидатов, отсортированных по confidence desc.
     *
     * @param  string      $text     Исходный текст пользователя.
     * @param  string|null $alphabet Явно заданный алфавит или null (auto).
     * @return CipherDetection[]
     */
    public function identify(string $text, ?string $alphabet): array
    {
        if (trim($text) === '') {
            return [];
        }

        $ctx     = new IdentificationContext($text, $alphabet, $this->scorer, $this->ioc);
        $results = [];

        foreach ($this->detectors as $detector) {
            $detection = $detector->detect($ctx);
            if ($detection !== null) {
                $results[] = $detection;
            }
        }

        usort($results, static fn (CipherDetection $a, CipherDetection $b): int => $b->confidence <=> $a->confidence);

        // Убираем дубликаты по toolSlug, оставляя лучший confidence.
        $seen   = [];
        $unique = [];
        foreach ($results as $detection) {
            if (!isset($seen[$detection->toolSlug])) {
                $seen[$detection->toolSlug] = true;
                $unique[]                   = $detection;
            }
        }

        return $unique;
    }

    /**
     * Возвращает UI-настройки инструмента для ToolRegistry.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-alphabet',
                'key'     => 'alphabet',
                'label'   => trans('CIPHER_TOOL_SETTING_ALPHABET'),
                'options' => [
                    ['value' => 'auto', 'label' => trans('CIPHER_TOOL_SETTING_AUTO'), 'selected' => true],
                    ['value' => 'en',   'label' => trans('LANG_EN')],
                    ['value' => 'ru',   'label' => trans('LANG_RU')],
                    ['value' => 'de',   'label' => trans('LANG_DE')],
                    ['value' => 'es',   'label' => trans('LANG_ES')],
                    ['value' => 'fr',   'label' => trans('LANG_FR')],
                    ['value' => 'it',   'label' => trans('LANG_IT')],
                    ['value' => 'pt',   'label' => trans('LANG_PT')],
                    ['value' => 'tr',   'label' => trans('LANG_TR')],
                ],
                'default' => 'auto',
            ],
        ];
    }

    /**
     * Возвращает trust-items для ToolRegistry.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('CIPHER_IDENTIFIER_TRUST_TYPE'),
            trans('CIPHER_IDENTIFIER_TRUST_MULTI_ALPHA'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
