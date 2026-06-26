<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Cipher\Dictionary\WordSignature;
use App\Console\CommandInterface;
use PDO;
use RuntimeException;

/**
 * Строит SQLite-словарь языка из плоского текстового списка слов.
 *
 * Использование:
 *   php bin/console dictionary:build <lang>|all
 *
 * Поток обработки:
 *   1. Запускает Node-скрипт `scripts/expand-hunspell.mjs`, который читает
 *      `storage/dictionaries/raw/{lang}/index.{aff,dic}` и пишет плоский
 *      файл `storage/dictionaries/raw/{lang}/words.txt`.
 *   2. Потоково читает слова, вычисляет сигнатуры через {@see WordSignature}
 *      и пишет их в SQLite-файл `storage/dictionaries/{lang}.sqlite`
 *      одной транзакцией с indexами по signature и length.
 *
 * Запись через PDO батчами по 5000 строк сохраняет память константной даже
 * для словарей в миллионы записей.
 */
final readonly class DictionaryBuildCommand implements CommandInterface
{
    /** @var list<string> Поддерживаемые языки словарей. */
    private const array SUPPORTED = ['en', 'ru', 'es', 'fr', 'de', 'it', 'pt', 'tr'];

    /** Размер пакета строк для INSERT. */
    private const int INSERT_BATCH = 5000;

    /**
     * Создаёт команду.
     */
    public function __construct(
        private WordSignature $signature,
    ) {
    }

    /**
     * Выполняет построение словаря.
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

        $languages = $target === 'all' ? self::SUPPORTED : [$target];

        foreach ($languages as $lang) {
            if (!in_array($lang, self::SUPPORTED, true)) {
                echo "Неподдерживаемый язык: {$lang}\n";
                return 1;
            }

            $rawDir   = STORAGE_PATH . '/dictionaries/raw/' . $lang;
            $affPath  = $rawDir . '/index.aff';
            $dicPath  = $rawDir . '/index.dic';
            $wordsTxt = $rawDir . '/words.txt';

            if (!is_file($affPath) || !is_file($dicPath)) {
                echo "Сначала запустите: php bin/console dictionary:download {$lang}\n";
                return 1;
            }

            if (!is_file($wordsTxt) || filemtime($wordsTxt) < filemtime($dicPath)) {
                echo "Раскрытие аффиксов для {$lang}…\n";
                $this->runExpander($affPath, $dicPath, $wordsTxt);
            } else {
                echo "Используем кешированный {$wordsTxt}.\n";
            }

            echo "Индексация {$lang}…\n";
            $stats      = $this->buildSqlite($wordsTxt, $lang);
            $targetPath = STORAGE_PATH . '/dictionaries/' . $lang . '.sqlite';
            echo "  слов: {$stats['words']}, сигнатур: {$stats['signatures']}\n";
            echo "  записано: {$targetPath}\n";
        }

        echo "Готово.\n";
        return 0;
    }

    /**
     * Запускает Node-раскрутчик аффиксов.
     */
    private function runExpander(string $affPath, string $dicPath, string $outPath): void
    {
        $cmd = sprintf(
            'node %s %s %s %s 2>&1',
            escapeshellarg(BASE_PATH . '/scripts/expand-hunspell.mjs'),
            escapeshellarg($affPath),
            escapeshellarg($dicPath),
            escapeshellarg($outPath),
        );

        $exitCode = 0;
        passthru($cmd, $exitCode);

        if ($exitCode !== 0 || !is_file($outPath)) {
            throw new RuntimeException('Node-раскрутчик аффиксов завершился с ошибкой.');
        }
    }

    /**
     * Создаёт SQLite-файл и записывает в него все слова и их сигнатуры.
     *
     * @return array{words: int, signatures: int}
     */
    private function buildSqlite(string $wordsPath, string $language): array
    {
        $targetDir = STORAGE_PATH . '/dictionaries';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException("Не удалось создать каталог: {$targetDir}");
        }

        $targetPath = $targetDir . '/' . $language . '.sqlite';
        if (is_file($targetPath)) {
            unlink($targetPath);
        }

        $pdo = new PDO('sqlite:' . $targetPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Ускоряем массовую вставку (1M+ строк за несколько секунд).
        $pdo->exec('PRAGMA journal_mode = MEMORY');
        $pdo->exec('PRAGMA synchronous = OFF');
        $pdo->exec('PRAGMA temp_store = MEMORY');

        $pdo->exec('CREATE TABLE words (signature TEXT NOT NULL, word TEXT NOT NULL, length INTEGER NOT NULL)');

        $in = fopen($wordsPath, 'rb');
        if ($in === false) {
            throw new RuntimeException("Не удалось открыть {$wordsPath}");
        }

        $insertedWords = 0;
        $batchValues   = [];
        $pdo->beginTransaction();

        try {
            while (($line = fgets($in)) !== false) {
                $word = trim($line);
                if ($word === '') {
                    continue;
                }
                $signature = $this->signature->compute($word, $language);
                if ($signature === '') {
                    continue;
                }
                $batchValues[] = $signature;
                $batchValues[] = $word;
                $batchValues[] = mb_strlen($signature);
                $insertedWords++;

                if (count($batchValues) >= self::INSERT_BATCH * 3) {
                    $this->flushBatch($pdo, $batchValues);
                    $batchValues = [];
                }
            }
            if ($batchValues !== []) {
                $this->flushBatch($pdo, $batchValues);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            fclose($in);
            throw $e;
        }

        fclose($in);

        // Индексы создаём после массовой загрузки — это быстрее.
        echo "  построение индексов…\n";
        $pdo->exec('CREATE INDEX idx_words_sig ON words(signature)');
        $pdo->exec('CREATE INDEX idx_words_len ON words(length, signature)');
        $pdo->exec('ANALYZE');

        $signatures = (int) $pdo->query('SELECT COUNT(DISTINCT signature) FROM words')->fetchColumn();

        return [
            'words'      => $insertedWords,
            'signatures' => $signatures,
        ];
    }

    /**
     * Вставляет накопленную пачку строк одним INSERT с множеством VALUES.
     *
     * @param list<string|int> $batchValues Плоский список [sig, word, length, sig, word, length, ...]
     */
    private function flushBatch(PDO $pdo, array $batchValues): void
    {
        $rowCount = (int) (count($batchValues) / 3);
        if ($rowCount === 0) {
            return;
        }
        $placeholders = str_repeat('(?, ?, ?),', $rowCount);
        $sql          = 'INSERT INTO words (signature, word, length) VALUES ' . rtrim($placeholders, ',');
        $stmt         = $pdo->prepare($sql);
        $stmt->execute($batchValues);
    }

    /**
     * Выводит справку.
     */
    private function usage(string $error): void
    {
        echo 'Ошибка: ' . $error . PHP_EOL . PHP_EOL;
        echo 'Использование:' . PHP_EOL;
        echo '  php bin/console dictionary:build <lang>|all' . PHP_EOL;
        echo '  Поддерживаемые языки: ' . implode(', ', self::SUPPORTED) . PHP_EOL;
    }
}
