<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use RuntimeException;

/**
 * Скачивает Hunspell-словари (`.aff` и `.dic`) из npm-пакета
 * @see https://github.com/wooorm/dictionaries в каталог
 * `private/storage/dictionaries/raw/{lang}/`.
 *
 * Использование:
 *   php bin/console dictionary:download <lang>|all
 */
final readonly class DictionaryDownloadCommand implements CommandInterface
{
    /** @var array<string, string> Карта язык → имя пакета wooorm/dictionaries. */
    private const array PACKAGES = [
        'en' => 'en',
        'ru' => 'ru',
        'es' => 'es',
        'fr' => 'fr',
        'de' => 'de',
        'it' => 'it',
        'pt' => 'pt',
        'tr' => 'tr',
    ];

    private const string BASE_URL = 'https://raw.githubusercontent.com/wooorm/dictionaries/main/dictionaries';

    /**
     * Выполняет загрузку словарей.
     *
     * @param string[] $args
     */
    public function handle(array $args): int
    {
        $target = (string) ($args[0] ?? '');
        if ($target === '') {
            $this->usage('Не указан язык словаря.');
            return 1;
        }

        $languages = $target === 'all' ? array_keys(self::PACKAGES) : [$target];

        foreach ($languages as $lang) {
            if (!isset(self::PACKAGES[$lang])) {
                echo "Неподдерживаемый язык: {$lang}\n";
                return 1;
            }

            $pkg     = self::PACKAGES[$lang];
            $dir     = STORAGE_PATH . '/dictionaries/raw/' . $lang;
            if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                throw new RuntimeException("Не удалось создать каталог: {$dir}");
            }

            foreach (['index.aff', 'index.dic'] as $file) {
                $url  = self::BASE_URL . '/' . $pkg . '/' . $file;
                $dest = $dir . '/' . $file;
                echo "Скачивание {$lang}/{$file}…\n";
                $this->download($url, $dest);
            }
        }

        echo "Готово.\n";
        return 0;
    }

    /**
     * Скачивает один файл по HTTP в локальный путь.
     */
    private function download(string $url, string $destination): void
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException("Не удалось инициализировать curl для {$url}");
        }

        $handle = fopen($destination, 'wb');
        if ($handle === false) {
            curl_close($ch);
            throw new RuntimeException("Не удалось открыть файл для записи: {$destination}");
        }

        curl_setopt($ch, CURLOPT_FILE, $handle);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        $ok = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($handle);

        if ($ok === false) {
            @unlink($destination);
            throw new RuntimeException("Ошибка загрузки {$url}: {$err}");
        }
    }

    /**
     * Выводит справку.
     */
    private function usage(string $error): void
    {
        echo 'Ошибка: ' . $error . PHP_EOL . PHP_EOL;
        echo 'Использование:' . PHP_EOL;
        echo '  php bin/console dictionary:download <lang>|all' . PHP_EOL;
        echo '  Поддерживаемые языки: ' . implode(', ', array_keys(self::PACKAGES)) . PHP_EOL;
    }
}
