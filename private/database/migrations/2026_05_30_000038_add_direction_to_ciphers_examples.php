<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Добавляет поле direction в ciphers_examples для явного указания направления операции.
 *
 * Допустимые значения: '' (авто), 'encrypt', 'decrypt'.
 */
final class AddDirectionToCiphersExamples extends Migration
{
    /**
     * Применяет миграцию.
     */
    public function up(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->string('direction', 20)->default('')->after('published');
        });
    }

    /**
     * Откатывает миграцию.
     */
    public function down(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->dropColumn('direction');
        });
    }
}
