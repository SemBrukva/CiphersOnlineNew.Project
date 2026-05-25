<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Создаёт таблицы связок инструментов, часто используемых вместе, и их переводов.
 */
class CreateCiphersCategoriesUsedTogetherTables extends Migration
{
    /**
     * Создаёт таблицы used_together и used_together_translations.
     */
    public function up(): void
    {
        Schema::create(Tables::CIPHERS_CATEGORIES_USED_TOGETHER, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('relation_cipher_first_id');
            $table->unsignedBigInteger('relation_cipher_second_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('published')->default(1);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->index(['category_id', 'published', 'sort_order'], 'idx_cipher_cat_used_together_cat_pub_sort');
            $table->index('relation_cipher_first_id', 'idx_cipher_cat_used_together_first_cipher');
            $table->index('relation_cipher_second_id', 'idx_cipher_cat_used_together_second_cipher');
            $table->foreign('category_id')
                ->references('id')
                ->on(Tables::CIPHER_CATEGORIES)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
            $table->foreign('relation_cipher_first_id')
                ->references('id')
                ->on(Tables::CIPHERS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
            $table->foreign('relation_cipher_second_id')
                ->references('id')
                ->on(Tables::CIPHERS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('used_together_id');
            $table->string('language', 8);
            $table->string('title', 500);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->unique(['used_together_id', 'language'], 'uniq_cipher_cat_used_together_trans_lang');
            $table->index('language', 'idx_cipher_cat_used_together_trans_language');
            $table->foreign('used_together_id')
                ->references('id')
                ->on(Tables::CIPHERS_CATEGORIES_USED_TOGETHER)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });
    }

    /**
     * Удаляет таблицы связок и их переводов.
     */
    public function down(): void
    {
        Schema::dropIfExists(Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS);
        Schema::dropIfExists(Tables::CIPHERS_CATEGORIES_USED_TOGETHER);
    }
}
