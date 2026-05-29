<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Переносит key и alphabet из ciphers_examples в ciphers_examples_translations
 * (поля языкозависимые — ключ может отличаться для разных локалей).
 */
final class MoveKeyAlphabetToCiphersExamplesTranslations extends Migration
{
    /**
     * Применяет миграцию.
     */
    public function up(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->dropColumn('key');
            $table->dropColumn('alphabet');
        });

        Schema::table(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, function (Blueprint $table): void {
            $table->string('key', 255)->default('')->after('description');
            $table->string('alphabet', 50)->default('')->after('key');
        });
    }

    /**
     * Откатывает миграцию.
     */
    public function down(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, function (Blueprint $table): void {
            $table->dropColumn('key');
            $table->dropColumn('alphabet');
        });

        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->string('key', 255)->default('')->after('published');
            $table->string('alphabet', 50)->default('')->after('key');
        });
    }
}
