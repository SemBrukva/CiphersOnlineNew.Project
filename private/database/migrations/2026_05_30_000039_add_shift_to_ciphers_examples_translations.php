<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Добавляет языкозависимое поле shift в переводы примеров шифров.
 */
final class AddShiftToCiphersExamplesTranslations extends Migration
{
    /**
     * Применяет миграцию.
     */
    public function up(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, function (Blueprint $table): void {
            $table->unsignedInteger('shift')->default(0)->after('key');
        });

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' '
            . 'SET shift = 3 '
            . 'WHERE example_id IN ('
            . 'SELECT e.id FROM ' . Tables::CIPHERS_EXAMPLES . ' e '
            . 'INNER JOIN ' . Tables::CIPHERS . ' c ON c.id = e.app_id '
            . 'WHERE c.alias = ? AND e.sort_order IN (10, 20)'
            . ')',
            ['caesar']
        );
    }

    /**
     * Откатывает миграцию.
     */
    public function down(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, function (Blueprint $table): void {
            $table->dropColumn('shift');
        });
    }
}
