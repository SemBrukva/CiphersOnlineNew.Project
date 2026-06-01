<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;

/**
 * Добавляет колонку delimiter в таблицу ciphers_examples.
 */
class AddDelimiterToCiphersExamples extends Migration
{
    public function up(): void
    {
        Schema::table('ciphers_examples', function (Blueprint $table) {
            $table->string('delimiter', 20)->default('');
        });
    }

    public function down(): void
    {
        Schema::table('ciphers_examples', function (Blueprint $table) {
            $table->dropColumn('delimiter');
        });
    }
}
