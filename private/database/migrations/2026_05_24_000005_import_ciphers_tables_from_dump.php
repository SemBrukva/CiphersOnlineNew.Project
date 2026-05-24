<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Создаёт таблицы ciphers_* и импортирует в них данные из предоставленного SQL-дампа.
 */
class ImportCiphersTablesFromDump extends Migration
{
    /** @var string Путь к SQL-дампу внутри репозитория. */
    private const string DUMP_PATH = DATABASE_PATH . '/dumps/user_ciphers.sql';

    /**
     * Создаёт таблицы ciphers_* и загружает данные из дампа.
     */
    public function up(): void
    {
        $this->createTables();
        $this->importData();
    }

    /**
     * Удаляет импортированные таблицы ciphers_* в корректном порядке зависимостей.
     */
    public function down(): void
    {
        Schema::dropIfExists(Tables::CIPHERS_BLOCKS_TRANSLATIONS);
        Schema::dropIfExists(Tables::CIPHERS_EXAMPLES_TRANSLATIONS);
        Schema::dropIfExists(Tables::CIPHERS_FAQ_TRANSLATIONS);
        Schema::dropIfExists(Tables::CIPHERS_TRANSLATIONS);
        Schema::dropIfExists(Tables::CIPHERS_BLOCKS);
        Schema::dropIfExists(Tables::CIPHERS_EXAMPLES);
        Schema::dropIfExists(Tables::CIPHERS_FAQ);
        Schema::dropIfExists(Tables::CIPHERS);
    }

    /**
     * Создаёт структуру таблиц ciphers_*.
     */
    private function createTables(): void
    {
        // Если предыдущий запуск прервался на импорте данных, пересоздаём таблицы заново.
        $this->down();

        Schema::create(Tables::CIPHERS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('category_id');
            $table->string('alias', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('published')->default(1);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->unique(['category_id', 'alias'], 'uniq_ciphers_category_alias');
            $table->index(['category_id', 'published', 'sort_order'], 'idx_ciphers_category_published_sort');
            $table->foreign('category_id')
                ->references('id')
                ->on(Tables::CIPHER_CATEGORIES)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_TRANSLATIONS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('app_id');
            $table->string('language', 8);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('meta_title', 255)->default('');
            $table->text('meta_description')->nullable();
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->unique(['app_id', 'language'], 'uniq_ciphers_translations_app_lang');
            $table->index('language', 'idx_ciphers_translations_language');
            $table->foreign('app_id')
                ->references('id')
                ->on(Tables::CIPHERS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_BLOCKS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('app_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('published')->default(1);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->index(['app_id', 'published', 'sort_order'], 'idx_ciphers_blocks_app_published_sort');
            $table->foreign('app_id')
                ->references('id')
                ->on(Tables::CIPHERS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_BLOCKS_TRANSLATIONS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('block_id');
            $table->string('language', 8);
            $table->string('title', 255);
            $table->mediumText('text');
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->unique(['block_id', 'language'], 'uniq_ciphers_blocks_translations_block_lang');
            $table->index('language', 'idx_ciphers_blocks_translations_language');
            $table->foreign('block_id')
                ->references('id')
                ->on(Tables::CIPHERS_BLOCKS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('app_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('published')->default(1);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->index(['app_id', 'published', 'sort_order'], 'idx_ciphers_examples_app_published_sort');
            $table->foreign('app_id')
                ->references('id')
                ->on(Tables::CIPHERS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('example_id');
            $table->string('language', 8);
            $table->string('title', 255);
            $table->mediumText('input');
            $table->mediumText('output');
            $table->mediumText('description');
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->unique(['example_id', 'language'], 'uniq_ciphers_examples_translations_example_lang');
            $table->index('language', 'idx_ciphers_examples_translations_language');
            $table->foreign('example_id')
                ->references('id')
                ->on(Tables::CIPHERS_EXAMPLES)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_FAQ, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('app_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('show_in_category')->default(0);
            $table->unsignedTinyInteger('published')->default(1);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->index(['app_id', 'published', 'show_in_category', 'sort_order'], 'idx_ciphers_faq_app_pub_show_sort');
            $table->foreign('app_id')
                ->references('id')
                ->on(Tables::CIPHERS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_FAQ_TRANSLATIONS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('faq_id');
            $table->string('language', 8);
            $table->string('question', 500);
            $table->mediumText('answer');
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->unique(['faq_id', 'language'], 'uniq_ciphers_faq_translations_faq_lang');
            $table->index('language', 'idx_ciphers_faq_translations_language');
            $table->foreign('faq_id')
                ->references('id')
                ->on(Tables::CIPHERS_FAQ)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });
    }

    /**
     * Импортирует данные только для таблиц ciphers_* из SQL-дампа.
     */
    private function importData(): void
    {
        $driver = (string) config('database.default', 'sqlite');

        if (!is_file(self::DUMP_PATH)) {
            throw new RuntimeException('Не найден SQL-дамп: ' . self::DUMP_PATH);
        }

        $sql = (string) file_get_contents(self::DUMP_PATH);

        if ($sql === '') {
            throw new RuntimeException('SQL-дамп пустой: ' . self::DUMP_PATH);
        }

        $tableMap = [
            'apps' => Tables::CIPHERS,
            'app_translations' => Tables::CIPHERS_TRANSLATIONS,
            'app_blocks' => Tables::CIPHERS_BLOCKS,
            'app_block_translations' => Tables::CIPHERS_BLOCKS_TRANSLATIONS,
            'app_examples' => Tables::CIPHERS_EXAMPLES,
            'app_example_translations' => Tables::CIPHERS_EXAMPLES_TRANSLATIONS,
            'app_faq' => Tables::CIPHERS_FAQ,
            'app_faq_translations' => Tables::CIPHERS_FAQ_TRANSLATIONS,
            'ciphers' => Tables::CIPHERS,
            'ciphers_translations' => Tables::CIPHERS_TRANSLATIONS,
            'ciphers_blocks' => Tables::CIPHERS_BLOCKS,
            'ciphers_blocks_translations' => Tables::CIPHERS_BLOCKS_TRANSLATIONS,
            'ciphers_examples' => Tables::CIPHERS_EXAMPLES,
            'ciphers_examples_translations' => Tables::CIPHERS_EXAMPLES_TRANSLATIONS,
            'ciphers_faq' => Tables::CIPHERS_FAQ,
            'ciphers_faq_translations' => Tables::CIPHERS_FAQ_TRANSLATIONS,
        ];

        $order = [
            Tables::CIPHERS,
            Tables::CIPHERS_TRANSLATIONS,
            Tables::CIPHERS_BLOCKS,
            Tables::CIPHERS_BLOCKS_TRANSLATIONS,
            Tables::CIPHERS_EXAMPLES,
            Tables::CIPHERS_EXAMPLES_TRANSLATIONS,
            Tables::CIPHERS_FAQ,
            Tables::CIPHERS_FAQ_TRANSLATIONS,
        ];

        $buckets = [];

        foreach ($order as $table) {
            $buckets[$table] = [];
        }

        $lines = preg_split('/\R/u', $sql) ?: [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (!str_starts_with($trimmed, 'INSERT INTO `')) {
                continue;
            }

            if (!preg_match('/^INSERT INTO `([^`]+)`/u', $trimmed, $match)) {
                continue;
            }

            $sourceTable = $match[1];

            if (!isset($tableMap[$sourceTable])) {
                continue;
            }

            $targetTable = $tableMap[$sourceTable];
            $statement = str_replace('`' . $sourceTable . '`', '`' . $targetTable . '`', $trimmed);

            if ($driver === 'sqlite') {
                $statement = str_replace("\\'", "''", $statement);
            }

            $buckets[$targetTable][] = $statement;
        }

        $this->db->transaction(function () use ($buckets, $order): void {
            foreach ($order as $table) {
                foreach ($buckets[$table] as $statement) {
                    $this->db->pdo()->exec($statement);
                }
            }
        });
    }
}
