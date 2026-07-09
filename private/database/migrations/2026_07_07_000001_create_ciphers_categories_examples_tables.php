<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Создаёт таблицы примеров для категорий шифров и их переводов.
 *
 * Используется для секции «Examples» на странице категории (например, флагман
 * cipher-solver). В отличие от примеров шифров, структура минимальна: только
 * подпись, входная строка и описание — без key/shift/alphabet и прочих настроек,
 * так как солвер определяет тип автоматически.
 */
class CreateCiphersCategoriesExamplesTables extends Migration
{
    /**
     * Создаёт таблицы примеров категорий и их переводов.
     */
    public function up(): void
    {
        Schema::create(Tables::CIPHERS_CATEGORIES_EXAMPLES, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('category_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('published')->default(1);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->index(['category_id', 'published', 'sort_order'], 'idx_cipher_cat_ex_cat_pub_sort');
            $table->foreign('category_id')
                ->references('id')
                ->on(Tables::CIPHER_CATEGORIES)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_CATEGORIES_EXAMPLES_TRANSLATIONS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('example_id');
            $table->string('language', 8);
            $table->string('title', 255);
            $table->mediumText('input');
            $table->mediumText('description');
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->unique(['example_id', 'language'], 'uniq_cipher_cat_ex_trans_ex_lang');
            $table->index('language', 'idx_cipher_cat_ex_trans_language');
            $table->foreign('example_id')
                ->references('id')
                ->on(Tables::CIPHERS_CATEGORIES_EXAMPLES)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });
    }

    /**
     * Удаляет таблицы примеров категорий и их переводов.
     */
    public function down(): void
    {
        Schema::dropIfExists(Tables::CIPHERS_CATEGORIES_EXAMPLES_TRANSLATIONS);
        Schema::dropIfExists(Tables::CIPHERS_CATEGORIES_EXAMPLES);
    }
}
