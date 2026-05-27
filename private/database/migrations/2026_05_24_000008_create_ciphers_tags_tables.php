<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Создаёт таблицы тегов шифров и их переводов.
 */
class CreateCiphersTagsTables extends Migration
{
    /**
     * Создаёт таблицы ciphers_tags и ciphers_tags_translations.
     */
    public function up(): void
    {
        Schema::create(Tables::CIPHERS_TAGS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('app_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('published')->default(1);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->index(['app_id', 'published', 'sort_order'], 'idx_ciphers_tags_app_published_sort');
            $table->foreign('app_id')
                ->references('id')
                ->on(Tables::CIPHERS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create(Tables::CIPHERS_TAGS_TRANSLATIONS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('tag_id');
            $table->string('language', 8);
            $table->string('tag', 100);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));

            $table->unique(['tag_id', 'language'], 'uniq_ciphers_tags_translations_tag_lang');
            $table->index('language', 'idx_ciphers_tags_translations_language');
            $table->foreign('tag_id')
                ->references('id')
                ->on(Tables::CIPHERS_TAGS)
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });
    }

    /**
     * Удаляет таблицы ciphers_tags и ciphers_tags_translations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Tables::CIPHERS_TAGS_TRANSLATIONS);
        Schema::dropIfExists(Tables::CIPHERS_TAGS);
    }
}
