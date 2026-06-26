<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Config\Config;
use App\Database\Database;
use App\Database\Migrator;
use App\Http\RequestContext;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет точечный запуск миграций.
 */
final class MigratorTest extends TestCase
{
    private Database $db;
    private string $migrationPath;

    /**
     * Готовит временную директорию миграций и in-memory SQLite базу.
     */
    protected function setUp(): void
    {
        global $config;

        $config = new Config([
            'database' => ['default' => 'sqlite'],
        ]);

        $this->migrationPath = sys_get_temp_dir() . '/ciphers_migrator_test_' . bin2hex(random_bytes(6));
        mkdir($this->migrationPath);

        $this->db = new Database(
            ['driver' => 'sqlite', 'database' => ':memory:'],
            new RequestContext('test', microtime(true), false)
        );
    }

    /**
     * Удаляет временные файлы миграций.
     */
    protected function tearDown(): void
    {
        foreach (glob($this->migrationPath . '/*.php') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->migrationPath)) {
            rmdir($this->migrationPath);
        }
    }

    /**
     * Проверяет, что runOne() применяет только указанную миграцию.
     */
    public function testRunOneAppliesOnlySelectedMigration(): void
    {
        $this->writeMigration(
            '2026_01_01_000001_create_single_migration_table',
            'CreateSingleMigrationTable',
            'CREATE TABLE selected_migration (id INTEGER PRIMARY KEY AUTOINCREMENT)'
        );
        $this->writeMigration(
            '2026_01_01_000002_create_other_migration_table',
            'CreateOtherMigrationTable',
            'CREATE TABLE other_migration (id INTEGER PRIMARY KEY AUTOINCREMENT)'
        );

        $migrator = new Migrator($this->db, $this->migrationPath);

        self::assertTrue($migrator->runOne('2026_01_01_000001_create_single_migration_table.php'));
        self::assertFalse($migrator->runOne('2026_01_01_000001_create_single_migration_table'));

        $status = $migrator->status();

        self::assertSame([
            [
                'migration' => '2026_01_01_000001_create_single_migration_table',
                'ran' => true,
                'batch' => 1,
            ],
            [
                'migration' => '2026_01_01_000002_create_other_migration_table',
                'ran' => false,
                'batch' => null,
            ],
        ], $status);
    }

    /**
     * Создаёт временный файл миграции для теста.
     */
    private function writeMigration(string $name, string $className, string $upSql): void
    {
        $code = <<<PHP
<?php

declare(strict_types=1);

use App\Database\Migration;

final class {$className} extends Migration
{
    public function up(): void
    {
        \$this->db->execute('{$upSql}');
    }

    public function down(): void
    {
    }
}
PHP;

        file_put_contents($this->migrationPath . '/' . $name . '.php', $code);
    }
}
