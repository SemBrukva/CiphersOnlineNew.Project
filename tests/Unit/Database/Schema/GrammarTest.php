<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Schema;

use App\Database\Schema\Blueprint;
use App\Database\Schema\MySqlGrammar;
use App\Database\Schema\RawExpression;
use App\Database\Schema\SqliteGrammar;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет генерацию SQL-выражений грамматиками SQLite и MySQL.
 */
final class GrammarTest extends TestCase
{
    private SqliteGrammar $sqlite;
    private MySqlGrammar  $mysql;

    protected function setUp(): void
    {
        $this->sqlite = new SqliteGrammar();
        $this->mysql  = new MySqlGrammar();
    }

    // -------------------------------------------------------------------------
    // SQLiteGrammar — CREATE TABLE
    // -------------------------------------------------------------------------

    /**
     * Проверяет CREATE TABLE с id(), string() и timestamps() для SQLite.
     */
    public function testSqliteCreateBasicTable(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->string('name', 100);
        $bp->string('email')->unique();
        $bp->timestamps();

        $sqls = $this->sqlite->compileCreate('users', $bp);

        $this->assertCount(1, $sqls);
        $sql = $sqls[0];

        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS users', $sql);
        $this->assertStringContainsString('id INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $this->assertStringContainsString('name TEXT NOT NULL', $sql);
        $this->assertStringContainsString('email TEXT NOT NULL', $sql);
        $this->assertStringContainsString('UNIQUE(email)', $sql);
        $this->assertStringContainsString('created_at TEXT', $sql);
        $this->assertStringContainsString('updated_at TEXT', $sql);
        $this->assertStringNotContainsString('created_at TEXT NOT NULL', $sql);
    }

    /**
     * Проверяет, что явный unique() через Blueprint добавляет ограничение внутри CREATE TABLE.
     */
    public function testSqliteCreateWithExplicitUnique(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->string('language', 2);
        $bp->string('alias', 50);
        $bp->unique(['language', 'alias']);

        $sqls = $this->sqlite->compileCreate('pages', $bp);

        $this->assertCount(1, $sqls);
        $this->assertStringContainsString('UNIQUE(language, alias)', $sqls[0]);
    }

    /**
     * Проверяет, что index() генерирует отдельный CREATE INDEX.
     */
    public function testSqliteCreateWithIndexes(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->string('queue', 100)->default('default');
        $bp->index('queue', 'jobs_queue_idx');

        $sqls = $this->sqlite->compileCreate('jobs', $bp);

        $this->assertCount(2, $sqls);
        $this->assertStringContainsString("DEFAULT 'default'", $sqls[0]);
        $this->assertStringContainsString('CREATE INDEX IF NOT EXISTS jobs_queue_idx ON jobs (queue)', $sqls[1]);
    }

    /**
     * Проверяет, что nullable-столбцы без NOT NULL в SQLite.
     */
    public function testSqliteNullableColumn(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->unsignedInteger('reserved_at')->nullable();

        [$sql] = $this->sqlite->compileCreate('jobs', $bp);

        $this->assertStringContainsString('reserved_at INTEGER', $sql);
        $this->assertStringNotContainsString('NOT NULL', substr($sql, strpos($sql, 'reserved_at')));
    }

    /**
     * Проверяет DEFAULT для числового значения в SQLite.
     */
    public function testSqliteDefaultIntegerValue(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->tinyInteger('attempts')->unsigned()->default(0);

        [$sql] = $this->sqlite->compileCreate('jobs', $bp);

        $this->assertStringContainsString('attempts INTEGER NOT NULL DEFAULT 0', $sql);
    }

    /**
     * Проверяет RawExpression как DEFAULT.
     */
    public function testSqliteRawDefault(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->datetime('created_at')->default(new RawExpression("datetime('now')"));

        [$sql] = $this->sqlite->compileCreate('test', $bp);

        $this->assertStringContainsString("DEFAULT (datetime('now'))", $sql);
    }

    /**
     * Проверяет bigId() для SQLite — тоже INTEGER.
     */
    public function testSqliteBigId(): void
    {
        $bp = new Blueprint();
        $bp->bigId();
        $bp->longText('payload');

        [$sql] = $this->sqlite->compileCreate('failed_jobs', $bp);

        $this->assertStringContainsString('id INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $this->assertStringContainsString('payload TEXT NOT NULL', $sql);
    }

    /**
     * Проверяет DROP TABLE IF EXISTS для SQLite.
     */
    public function testSqliteDropIfExists(): void
    {
        $this->assertSame(
            'DROP TABLE IF EXISTS users',
            $this->sqlite->compileDropIfExists('users')
        );
    }

    /**
     * Проверяет ALTER TABLE ADD COLUMN для SQLite.
     */
    public function testSqliteAlterAddColumn(): void
    {
        $bp = new Blueprint();
        $bp->string('language', 2)->nullable();

        $sqls = $this->sqlite->compileAlter('users', $bp);

        $this->assertCount(1, $sqls);
        $this->assertStringContainsString('ALTER TABLE users ADD COLUMN language TEXT', $sqls[0]);
    }

    /**
     * Проверяет ALTER TABLE DROP COLUMN для SQLite.
     */
    public function testSqliteAlterDropColumn(): void
    {
        $bp = new Blueprint();
        $bp->dropColumn('old_field');

        $sqls = $this->sqlite->compileAlter('users', $bp);

        $this->assertCount(1, $sqls);
        $this->assertSame('ALTER TABLE users DROP COLUMN old_field', $sqls[0]);
    }

    /**
     * Проверяет compileHasTable для SQLite.
     */
    public function testSqliteHasTableSql(): void
    {
        $sql = $this->sqlite->compileHasTable();

        $this->assertStringContainsString('sqlite_master', $sql);
        $this->assertStringContainsString("type = 'table'", $sql);
        $this->assertStringContainsString('?', $sql);
    }

    /**
     * Проверяет compileHasColumn для SQLite.
     */
    public function testSqliteHasColumnSql(): void
    {
        $sql = $this->sqlite->compileHasColumn();

        $this->assertStringContainsString('pragma_table_info', $sql);
        $this->assertStringContainsString('?', $sql);
    }

    // -------------------------------------------------------------------------
    // MySqlGrammar — CREATE TABLE
    // -------------------------------------------------------------------------

    /**
     * Проверяет CREATE TABLE с id(), string() и timestamps() для MySQL.
     */
    public function testMysqlCreateBasicTable(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->string('name', 100);
        $bp->string('email')->unique();
        $bp->timestamps();

        [$sql] = $this->mysql->compileCreate('users', $bp);

        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `users`', $sql);
        $this->assertStringContainsString('`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('`name` varchar(100) NOT NULL', $sql);
        $this->assertStringContainsString('`email` varchar(255) NOT NULL', $sql);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $sql);
        $this->assertStringContainsString('UNIQUE KEY `email_unique`', $sql);
        $this->assertStringContainsString('ENGINE=InnoDB DEFAULT CHARSET=utf8mb4', $sql);
    }

    /**
     * Проверяет bigId() для MySQL — bigint UNSIGNED AUTO_INCREMENT.
     */
    public function testMysqlBigId(): void
    {
        $bp = new Blueprint();
        $bp->bigId();
        $bp->string('queue', 100)->default('default');
        $bp->longText('payload');
        $bp->unsignedTinyInteger('attempts')->default(0);
        $bp->unsignedInteger('available_at');
        $bp->unsignedInteger('reserved_at')->nullable();

        [$sql] = $this->mysql->compileCreate('jobs', $bp);

        $this->assertStringContainsString('`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('`queue` varchar(100) NOT NULL DEFAULT \'default\'', $sql);
        $this->assertStringContainsString('`payload` longtext NOT NULL', $sql);
        $this->assertStringContainsString('`attempts` tinyint(4) UNSIGNED NOT NULL DEFAULT 0', $sql);
        $this->assertStringContainsString('`available_at` int(11) UNSIGNED NOT NULL', $sql);
        $this->assertStringContainsString('`reserved_at` int(11) UNSIGNED NULL DEFAULT NULL', $sql);
    }

    /**
     * Проверяет генерацию KEY для обычного индекса в MySQL.
     */
    public function testMysqlCreateWithIndexes(): void
    {
        $bp = new Blueprint();
        $bp->bigId();
        $bp->string('queue', 100)->default('default');
        $bp->index('queue', 'jobs_queue_idx');

        [$sql] = $this->mysql->compileCreate('jobs', $bp);

        $this->assertStringContainsString('KEY `jobs_queue_idx` (`queue`)', $sql);
    }

    /**
     * Проверяет составной уникальный индекс в MySQL.
     */
    public function testMysqlCreateWithCompositeUnique(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->string('language', 2);
        $bp->string('alias', 50);
        $bp->unique(['language', 'alias'], 'unique_lang_alias');

        [$sql] = $this->mysql->compileCreate('pages', $bp);

        $this->assertStringContainsString('UNIQUE KEY `unique_lang_alias` (`language`, `alias`)', $sql);
    }

    /**
     * Проверяет nullable-столбец с NULL DEFAULT NULL в MySQL.
     */
    public function testMysqlNullableColumn(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->datetime('deleted_at')->nullable();

        [$sql] = $this->mysql->compileCreate('users', $bp);

        $this->assertStringContainsString('`deleted_at` datetime NULL DEFAULT NULL', $sql);
    }

    /**
     * Проверяет RawExpression как DEFAULT в MySQL.
     */
    public function testMysqlRawDefault(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->datetime('created_at')->default(new RawExpression('CURRENT_TIMESTAMP'));

        [$sql] = $this->mysql->compileCreate('test', $bp);

        $this->assertStringContainsString('DEFAULT CURRENT_TIMESTAMP', $sql);
    }

    /**
     * Проверяет ALTER TABLE ADD COLUMN с AFTER в MySQL.
     */
    public function testMysqlAlterAddColumnWithAfter(): void
    {
        $bp = new Blueprint();
        $bp->string('language', 2)->nullable()->after('email');

        $sqls = $this->mysql->compileAlter('users', $bp);

        $this->assertCount(1, $sqls);
        $this->assertStringContainsString('ALTER TABLE `users` ADD COLUMN `language` varchar(2) NULL DEFAULT NULL AFTER `email`', $sqls[0]);
    }

    /**
     * Проверяет ALTER TABLE DROP COLUMN в MySQL.
     */
    public function testMysqlAlterDropColumn(): void
    {
        $bp = new Blueprint();
        $bp->dropColumn('old_field');

        $sqls = $this->mysql->compileAlter('users', $bp);

        $this->assertCount(1, $sqls);
        $this->assertSame('ALTER TABLE `users` DROP COLUMN `old_field`', $sqls[0]);
    }

    /**
     * Проверяет DROP TABLE IF EXISTS для MySQL.
     */
    public function testMysqlDropIfExists(): void
    {
        $this->assertSame(
            'DROP TABLE IF EXISTS `users`',
            $this->mysql->compileDropIfExists('users')
        );
    }

    /**
     * Проверяет compileHasTable для MySQL.
     */
    public function testMysqlHasTableSql(): void
    {
        $sql = $this->mysql->compileHasTable();

        $this->assertStringContainsString('information_schema.tables', $sql);
        $this->assertStringContainsString('DATABASE()', $sql);
        $this->assertStringContainsString('?', $sql);
    }

    /**
     * Проверяет boolean() — tinyint(1) без unsigned.
     */
    public function testMysqlBooleanType(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->boolean('is_active')->default(true);

        [$sql] = $this->mysql->compileCreate('users', $bp);

        $this->assertStringContainsString('`is_active` tinyint(1) NOT NULL DEFAULT 1', $sql);
    }

    /**
     * Проверяет decimal() с точностью и масштабом в MySQL.
     */
    public function testMysqlDecimalType(): void
    {
        $bp = new Blueprint();
        $bp->id();
        $bp->decimal('price', 10, 2);

        [$sql] = $this->mysql->compileCreate('products', $bp);

        $this->assertStringContainsString('`price` decimal(10,2) NOT NULL', $sql);
    }
}
