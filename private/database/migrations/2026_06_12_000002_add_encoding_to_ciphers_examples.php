<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Добавляет колонку encoding в ciphers_examples для поддержки режима кодирования в примерах.
 */
class AddEncodingToCiphersExamples extends Migration
{
    /**
     * Добавляет колонку encoding.
     */
    public function up(): void
    {
        if (!Schema::hasColumn(Tables::CIPHERS_EXAMPLES, 'encoding')) {
            Schema::table(Tables::CIPHERS_EXAMPLES, function ($table) {
                $table->string('encoding', 32)->default('')->after('delimiter');
            });
        }
    }

    /**
     * Удаляет колонку encoding.
     */
    public function down(): void
    {
        if (Schema::hasColumn(Tables::CIPHERS_EXAMPLES, 'encoding')) {
            Schema::table(Tables::CIPHERS_EXAMPLES, function ($table) {
                $table->dropColumn('encoding');
            });
        }
    }
}
