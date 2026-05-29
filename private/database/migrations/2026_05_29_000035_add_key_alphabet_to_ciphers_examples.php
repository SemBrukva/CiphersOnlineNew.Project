<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Добавляет поля key и alphabet в таблицу примеров для поддержки шифров с ключом.
 */
final class AddKeyAlphabetToCiphersExamples extends Migration
{
    /**
     * Применяет миграцию.
     */
    public function up(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->string('key', 255)->default('')->after('published');
            $table->string('alphabet', 50)->default('')->after('key');
        });
    }

    /**
     * Откатывает миграцию.
     */
    public function down(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->dropColumn('key');
            $table->dropColumn('alphabet');
        });
    }
}
