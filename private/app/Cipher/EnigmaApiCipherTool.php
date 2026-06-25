<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент симулятора шифровальной машины Enigma.
 */
final readonly class EnigmaApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента Enigma.
     */
    public function __construct(
        private EnigmaCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'enigma';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text     = (string) ($payload['text'] ?? '');
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];

        $reflector   = strtoupper(trim((string) ($settings['enigma_reflector'] ?? 'B')));
        $rotorLeft   = strtoupper(trim((string) ($settings['enigma_rotor_left']   ?? 'I')));
        $rotorMiddle = strtoupper(trim((string) ($settings['enigma_rotor_middle'] ?? 'II')));
        $rotorRight  = strtoupper(trim((string) ($settings['enigma_rotor_right']  ?? 'III')));
        $ringLeft    = $this->normalizeLetter($settings['enigma_ring_left']   ?? 'A');
        $ringMiddle  = $this->normalizeLetter($settings['enigma_ring_middle'] ?? 'A');
        $ringRight   = $this->normalizeLetter($settings['enigma_ring_right']  ?? 'A');
        $posLeft     = $this->normalizeLetter($settings['enigma_pos_left']    ?? 'A');
        $posMiddle   = $this->normalizeLetter($settings['enigma_pos_middle']  ?? 'A');
        $posRight    = $this->normalizeLetter($settings['enigma_pos_right']   ?? 'A');
        $plugboardRaw = (string) ($settings['enigma_plugboard'] ?? '');

        $errors = [];

        if ($text === '') {
            $errors['text'][] = trans('ENIGMA_ERR_TEXT_REQUIRED');
        }

        $availableRotors    = $this->cipher->availableRotors();
        $availableReflectors = $this->cipher->availableReflectors();

        if (!in_array($reflector, $availableReflectors, true)) {
            $errors['settings.enigma_reflector'][] = trans('ENIGMA_ERR_REFLECTOR_INVALID');
        }
        foreach (['left' => $rotorLeft, 'middle' => $rotorMiddle, 'right' => $rotorRight] as $slot => $name) {
            if (!in_array($name, $availableRotors, true)) {
                $errors['settings.enigma_rotor_' . $slot][] = trans('ENIGMA_ERR_ROTOR_INVALID');
            }
        }

        $picked = [$rotorLeft, $rotorMiddle, $rotorRight];
        if (count($picked) !== count(array_unique($picked))) {
            $errors['settings.enigma_rotor_left'][] = trans('ENIGMA_ERR_ROTORS_DUPLICATE');
        }

        [$plugErr, $plugboardNormalized, $plugboardMap] = $this->cipher->parsePlugboard($plugboardRaw);
        if ($plugErr !== null) {
            $errors['settings.enigma_plugboard'][] = trans($plugErr);
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('ENIGMA_ERR_INVALID'), ['errors' => $errors]);
        }

        $result = $this->cipher->process(
            $text,
            ['left' => $rotorLeft, 'middle' => $rotorMiddle, 'right' => $rotorRight],
            ['left' => $ringLeft, 'middle' => $ringMiddle, 'right' => $ringRight],
            ['left' => $posLeft, 'middle' => $posMiddle, 'right' => $posRight],
            $reflector,
            $plugboardMap,
        );

        return [
            'ok'                => true,
            'result'            => $result['output'],
            'final_positions'   => $result['final_positions'],
            'letters_processed' => $result['letters_processed'],
            'plugboard_normalized' => $plugboardNormalized,
        ];
    }

    /**
     * Приводит вход к одной заглавной латинской букве A–Z (fallback — 'A').
     */
    private function normalizeLetter(mixed $value): string
    {
        $letter = strtoupper(trim((string) $value));
        if ($letter === '' || strlen($letter) > 1 || !ctype_alpha($letter)) {
            return 'A';
        }
        return $letter;
    }
}
