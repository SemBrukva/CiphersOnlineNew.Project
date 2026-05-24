<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Config\Config;
use App\Console\CommandInterface;

/**
 * Команда сборки кеша конфигурации приложения.
 */
final readonly class ConfigCacheCommand implements CommandInterface
{
    /** Путь к директории кеша приложения. */
    private const CACHE_DIR = __DIR__ . '/../../../storage/cache';

    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Config $config)
    {
    }

    /**
     * Собирает единый кеш-файл конфигурации для production.
     */
    public function handle(array $args): int
    {
        $items = $this->config->all();
        unset($items['services']);

        $cachePath = self::CACHE_DIR . '/config.php';
        $dir       = dirname($cachePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $payload = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($items, true) . ";\n";
        file_put_contents($cachePath, $payload);

        echo "Кеш конфигурации создан: {$cachePath}" . PHP_EOL;

        return 0;
    }
}
