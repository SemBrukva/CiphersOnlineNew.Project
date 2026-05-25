<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Создаёт таблицы блоков контента для категорий шифров и их переводов.
 */
class CreateCiphersCategoriesBlocksTables extends Migration
{
    /**
     * Создаёт таблицы блоков категорий и их переводов.
     */
    public function up(): void
    {
        Schema::create(Tables::CIPHERS_CATEGORIES_BLOCKS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('category_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('published')->default(1);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->index(['category_id', 'published', 'sort_order'], 'idx_cipher_cat_blocks_cat_pub_sort');
            $table->foreign('category_id')
                ->references('id')
                ->on(Tables::CIPHER_CATEGORIES)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('block_id');
            $table->string('language', 8);
            $table->string('title', 255);
            $table->mediumText('text');
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->unique(['block_id', 'language'], 'uniq_cipher_cat_blocks_trans_block_lang');
            $table->index('language', 'idx_cipher_cat_blocks_trans_language');
            $table->foreign('block_id')
                ->references('id')
                ->on(Tables::CIPHERS_CATEGORIES_BLOCKS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });
    }

    /**
     * Удаляет таблицы блоков категорий и их переводов.
     */
    public function down(): void
    {
        Schema::dropIfExists(Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS);
        Schema::dropIfExists(Tables::CIPHERS_CATEGORIES_BLOCKS);
    }
}
