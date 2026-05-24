<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Config\Config;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет загрузку и чтение конфигурации.
 */
final class ConfigTest extends TestCase
{
    /**
     * Проверяет чтение вложенных значений через точечную нотацию.
     */
    public function testGetReturnsNestedValueByDotNotation(): void
    {
        $config = new Config([
            'app' => ['name' => 'Skeleton'],
        ]);

        self::assertSame('Skeleton', $config->get('app.name'));
    }

    /**
     * Проверяет возврат значения по умолчанию для отсутствующего ключа.
     */
    public function testGetReturnsDefaultForMissingKey(): void
    {
        $config = new Config();

        self::assertSame('fallback', $config->get('missing.key', 'fallback'));
    }

    /**
     * Проверяет загрузку php-файлов конфигурации из директории.
     */
    public function testLoadReadsPhpFilesAsTopLevelKeys(): void
    {
        $dir = sys_get_temp_dir() . '/config-test-' . bin2hex(random_bytes(4));
        mkdir($dir);

        file_put_contents($dir . '/app.php', "<?php return ['debug' => true];");
        file_put_contents($dir . '/db.php', "<?php return ['driver' => 'sqlite'];");

        $config = new Config();
        $config->load($dir);

        self::assertTrue($config->get('app.debug'));
        self::assertSame('sqlite', $config->get('db.driver'));

        unlink($dir . '/app.php');
        unlink($dir . '/db.php');
        rmdir($dir);
    }

    /**
     * Проверяет, что all() возвращает полный набор элементов конфигурации.
     */
    public function testAllReturnsAllItems(): void
    {
        $items = ['app' => ['env' => 'test']];
        $config = new Config($items);

        self::assertSame($items, $config->all());
    }

    /**
     * Проверяет загрузку конфигурации из кеш-файла.
     */
    public function testLoadFromCacheReadsArrayFromPhpFile(): void
    {
        $cacheFile = sys_get_temp_dir() . '/config-cache-test-' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($cacheFile, "<?php return ['app' => ['env' => 'production']];");

        $config = new Config();
        $loaded = $config->loadFromCache($cacheFile);

        self::assertTrue($loaded);
        self::assertSame('production', $config->get('app.env'));

        unlink($cacheFile);
    }
}
