<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Контракт детектора шифра/кодировки для инструмента CipherIdentifier.
 */
interface CipherDetectorInterface
{
    /**
     * Решает, насколько текст похож на «свой» шифр/кодировку.
     *
     * Все общие величины (длина без пробелов, подсчёт букв, IoC) предвычислены
     * в контексте — детектор должен брать их оттуда, а не пересчитывать.
     *
     * @return CipherDetection|null null, если шифр-кандидат заведомо не подходит.
     */
    public function detect(IdentificationContext $ctx): ?CipherDetection;
}
