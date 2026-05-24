<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Удаляет устаревший столбец allow_fallback из таблицы шифров.
 */
class DropAllowFallbackFromCiphers extends Migration
{
    /**
     * Удаляет столбец allow_fallback, если он присутствует.
     */
    public function up(): void
    {
        if (!Schema::hasTable(Tables::CIPHERS) || !Schema::hasColumn(Tables::CIPHERS, 'allow_fallback')) {
            return;
        }

        Schema::table(Tables::CIPHERS, function (Blueprint $table): void {
            $table->dropColumn('allow_fallback');
        });
    }

    /**
     * Возвращает столбец allow_fallback при откате.
     */
    public function down(): void
    {
        if (!Schema::hasTable(Tables::CIPHERS) || Schema::hasColumn(Tables::CIPHERS, 'allow_fallback')) {
            return;
        }

        Schema::table(Tables::CIPHERS, function (Blueprint $table): void {
            $table->unsignedTinyInteger('allow_fallback')->default(1);
        });
    }
}
