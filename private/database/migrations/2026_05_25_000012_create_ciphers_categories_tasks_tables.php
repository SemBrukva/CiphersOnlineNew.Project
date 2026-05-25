<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Создаёт таблицы задач категорий шифров и их переводов.
 */
class CreateCiphersCategoriesTasksTables extends Migration
{
    /**
     * Создаёт таблицы задач категорий и их переводов.
     */
    public function up(): void
    {
        Schema::create(Tables::CIPHERS_CATEGORIES_TASKS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('relation_cipher_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('published')->default(1);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->index(['category_id', 'published', 'sort_order'], 'idx_cipher_cat_tasks_cat_pub_sort');
            $table->index('relation_cipher_id', 'idx_cipher_cat_tasks_relation_cipher');
            $table->foreign('category_id')
                ->references('id')
                ->on(Tables::CIPHER_CATEGORIES)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
            $table->foreign('relation_cipher_id')
                ->references('id')
                ->on(Tables::CIPHERS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('task_id');
            $table->string('language', 8);
            $table->string('title', 255);
            $table->mediumText('description');
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->unique(['task_id', 'language'], 'uniq_cipher_cat_tasks_trans_task_lang');
            $table->index('language', 'idx_cipher_cat_tasks_trans_language');
            $table->foreign('task_id')
                ->references('id')
                ->on(Tables::CIPHERS_CATEGORIES_TASKS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });
    }

    /**
     * Удаляет таблицы задач категорий и их переводов.
     */
    public function down(): void
    {
        Schema::dropIfExists(Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS);
        Schema::dropIfExists(Tables::CIPHERS_CATEGORIES_TASKS);
    }
}
