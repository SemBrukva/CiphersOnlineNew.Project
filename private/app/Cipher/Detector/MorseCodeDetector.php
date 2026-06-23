<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор кода Морзе.
 *
 * Структурный фильтр: только точки, тире, пробелы и слэши; токены 1–5 знаков
 * (стандарт ITU, длиннее 5 знаков в коде Морзе не бывает кроме пунктуации,
 * которой мы в этом фильтре пренебрегаем). Decode-check через встроенную
 * таблицу A–Z/0–9 отсекает случайные «....-» из URL или ASCII-art.
 */
final readonly class MorseCodeDetector implements CipherDetectorInterface
{
    /**
     * Базовая таблица международного кода Морзе: латинские буквы и цифры.
     *
     * @var array<string, string>
     */
    private const array MORSE_TABLE = [
        '.-'    => 'A', '-...'  => 'B', '-.-.'  => 'C', '-..'   => 'D',
        '.'     => 'E', '..-.'  => 'F', '--.'   => 'G', '....'  => 'H',
        '..'    => 'I', '.---'  => 'J', '-.-'   => 'K', '.-..'  => 'L',
        '--'    => 'M', '-.'    => 'N', '---'   => 'O', '.--.'  => 'P',
        '--.-'  => 'Q', '.-.'   => 'R', '...'   => 'S', '-'     => 'T',
        '..-'   => 'U', '...-'  => 'V', '.--'   => 'W', '-..-'  => 'X',
        '-.--'  => 'Y', '--..'  => 'Z',
        '-----' => '0', '.----' => '1', '..---' => '2', '...--' => '3',
        '....-' => '4', '.....' => '5', '-....' => '6', '--...' => '7',
        '---..' => '8', '----.' => '9',
    ];

    /** Минимальная доля валидно декодируемых токенов. */
    private const float MIN_DECODED_RATIO = 0.80;

    /**
     * {@inheritDoc}
     */
    public function detect(IdentificationContext $ctx): ?CipherDetection
    {
        $trimmed = trim($ctx->text);
        if ($trimmed === '') {
            return null;
        }

        if (!preg_match('/^[.\-\/\s]+$/', $trimmed)) {
            return null;
        }

        // Должна быть хотя бы одна точка или тире.
        if (!preg_match('/[.\-]/', $trimmed)) {
            return null;
        }

        $tokens = preg_split('/\s+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) < 1) {
            return null;
        }

        $structurallyValid = 0;
        $decoded           = 0;
        foreach ($tokens as $token) {
            if ($token === '/') {
                $structurallyValid++;
                continue;
            }
            if (!preg_match('/^[.\-]{1,5}$/', $token)) {
                continue;
            }
            $structurallyValid++;
            if (isset(self::MORSE_TABLE[$token])) {
                $decoded++;
            }
        }

        $structuralRatio = $structurallyValid / count($tokens);
        if ($structuralRatio < 0.85) {
            return null;
        }

        // Без декода: confidence среднее; с декодом — высокое.
        // На случай шума вроде ".-/-./-" даём confidence ≥ AUTO_THRESHOLD только
        // если ≥ 80% токенов реально декодируются в букву или цифру.
        $tokensExceptSeparator = max(1, $structurallyValid - substr_count($trimmed, '/'));
        $decodedRatio          = $decoded / $tokensExceptSeparator;

        if ($decodedRatio >= self::MIN_DECODED_RATIO) {
            $confidence = 0.95;
        } elseif ($decodedRatio >= 0.50) {
            $confidence = 0.75;
        } else {
            $confidence = 0.45;
        }

        return new CipherDetection(
            toolSlug: 'codes-and-alphabets/morse-code',
            cipherKey: 'CIPHER_NAME_MORSE_CODE',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_MORSE'],
        );
    }
}
