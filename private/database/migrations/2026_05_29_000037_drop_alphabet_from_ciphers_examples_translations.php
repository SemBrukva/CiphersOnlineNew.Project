<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Удаляет поле alphabet из ciphers_examples_translations:
 * язык перевода уже однозначно определяет алфавит.
 */
final class DropAlphabetFromCiphersExamplesTranslations extends Migration
{
    /**
     * Применяет миграцию.
     */
    public function up(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, function (Blueprint $table): void {
            $table->dropColumn('alphabet');
        });
    }

    /**
     * Откатывает миграцию.
     */
    public function down(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, function (Blueprint $table): void {
            $table->string('alphabet', 50)->default('')->after('key');
        });
    }
}
