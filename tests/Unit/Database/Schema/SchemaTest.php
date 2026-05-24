<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Schema;

use App\Config\Config;
use App\Container\Container;
use App\Database\Database;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Http\RequestContext;
use PHPUnit\Framework\TestCase;

/**
 * Интеграционные тесты Schema Builder на реальной SQLite in-memory базе.
 */
final class SchemaTest extends TestCase
{
    private Database $db;

    /**
     * Настраивает in-memory SQLite базу и регистрирует зависимости для хелперов.
     */
    protected function setUp(): void
    {
        global $config, $container;

        $config = new Config([
            'database' => ['default' => 'sqlite'],
        ]);

        $context  = new RequestContext('test', microtime(true), false);
        $this->db = new Database(
            ['driver' => 'sqlite', 'database' => ':memory:'],
            $context
        );

        $container = new Container();
        $container->set(Database::class, fn () => $this->db);
    }

    /**
     * Проверяет, что Schema::create() создаёт таблицу в базе.
     */
    public function testCreateTableExists(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email')->unique();
            $table->timestamps();
        });

        $this->assertTrue(Schema::hasTable('users'));
    }

    /**
     * Проверяет, что hasTable() возвращает false для несуществующей таблицы.
     */
    public function testHasTableReturnsFalseForMissing(): void
    {
        $this->assertFalse(Schema::hasTable('nonexistent_table'));
    }

    /**
     * Проверяет, что созданные столбцы существуют через hasColumn().
     */
    public function testHasColumnAfterCreate(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
        });

        $this->assertTrue(Schema::hasColumn('users', 'id'));
        $this->assertTrue(Schema::hasColumn('users', 'email'));
        $this->assertFalse(Schema::hasColumn('users', 'nonexistent'));
    }

    /**
     * Проверяет вставку и выборку данных из созданной таблицы.
     */
    public function testCreatedTableAcceptsData(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('qty')->default(0);
        });

        $this->db->insert('INSERT INTO items (title, qty) VALUES (?, ?)', ['Widget', 5]);
        $row = $this->db->fetch('SELECT * FROM items WHERE title = ?', ['Widget']);

        $this->assertNotFalse($row);
        $this->assertSame('Widget', $row['title']);
        $this->assertSame('5', (string) $row['qty']);
    }

    /**
     * Проверяет, что dropIfExists() удаляет таблицу.
     */
    public function testDropIfExistsRemovesTable(): void
    {
        Schema::create('temp_table', function (Blueprint $table) {
            $table->id();
        });

        $this->assertTrue(Schema::hasTable('temp_table'));

        Schema::dropIfExists('temp_table');

        $this->assertFalse(Schema::hasTable('temp_table'));
    }

    /**
     * Проверяет, что dropIfExists() не падает для несуществующей таблицы.
     */
    public function testDropIfExistsIsSilentForMissing(): void
    {
        $this->expectNotToPerformAssertions();
        Schema::dropIfExists('table_that_does_not_exist');
    }

    /**
     * Проверяет добавление столбца через Schema::table().
     */
    public function testTableAlterAddColumn(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar', 100)->nullable();
        });

        $this->assertTrue(Schema::hasColumn('users', 'avatar'));
    }

    /**
     * Проверяет удаление столбца через dropColumn() (SQLite 3.35+).
     */
    public function testTableAlterDropColumn(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('old_field')->nullable();
        });

        $this->assertTrue(Schema::hasColumn('users', 'old_field'));

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('old_field');
        });

        $this->assertFalse(Schema::hasColumn('users', 'old_field'));
    }

    /**
     * Проверяет, что unique-ограничение реально применяется в базе.
     */
    public function testUniqueConstraintIsEnforced(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
        });

        $this->db->insert('INSERT INTO users (email) VALUES (?)', ['a@example.com']);

        $this->expectException(\PDOException::class);
        $this->db->insert('INSERT INTO users (email) VALUES (?)', ['a@example.com']);
    }

    /**
     * Проверяет создание таблицы с составным уникальным индексом.
     */
    public function testCompositeUniqueConstraint(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('language', 2);
            $table->string('alias', 50);
            $table->unique(['language', 'alias']);
        });

        $this->db->insert('INSERT INTO pages (language, alias) VALUES (?, ?)', ['en', 'home']);

        $this->expectException(\PDOException::class);
        $this->db->insert('INSERT INTO pages (language, alias) VALUES (?, ?)', ['en', 'home']);
    }

    /**
     * Проверяет DEFAULT-значение столбца.
     */
    public function testColumnDefaultValue(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue', 100)->default('default');
            $table->tinyInteger('attempts')->default(0);
        });

        $this->db->insert('INSERT INTO jobs (queue, attempts) VALUES (?, ?)', ['default', 0]);
        $row = $this->db->fetch('SELECT * FROM jobs WHERE id = 1');

        $this->assertNotFalse($row);
        $this->assertSame('default', $row['queue']);
        $this->assertSame('0', (string) $row['attempts']);
    }

    /**
     * Проверяет Schema::raw() — значение не экранируется.
     */
    public function testRawExpressionAsDefault(): void
    {
        $raw = Schema::raw("datetime('now')");

        Schema::create('events', function (Blueprint $table) use ($raw) {
            $table->id();
            $table->string('name');
            $table->datetime('occurred_at')->default($raw);
        });

        $this->assertTrue(Schema::hasTable('events'));
        $this->assertTrue(Schema::hasColumn('events', 'occurred_at'));
    }

    /**
     * Проверяет drop() (без IF EXISTS) для существующей таблицы.
     */
    public function testDrop(): void
    {
        Schema::create('to_drop', function (Blueprint $table) {
            $table->id();
        });

        Schema::drop('to_drop');

        $this->assertFalse(Schema::hasTable('to_drop'));
    }

    /**
     * Проверяет softDeletes() — добавляет nullable deleted_at.
     */
    public function testSoftDeletes(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->softDeletes();
        });

        $this->assertTrue(Schema::hasColumn('posts', 'deleted_at'));

        $this->db->insert('INSERT INTO posts (title) VALUES (?)', ['Hello']);
        $row = $this->db->fetch('SELECT deleted_at FROM posts WHERE id = 1');

        $this->assertNotFalse($row);
        $this->assertNull($row['deleted_at']);
    }
}
