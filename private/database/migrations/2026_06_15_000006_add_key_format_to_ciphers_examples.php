<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Добавляет колонку key_format в ciphers_examples и проставляет значение для hex-примера XOR-шифра.
 */
class AddKeyFormatToCiphersExamples extends Migration
{
    /**
     * Добавляет колонку и обновляет hex-пример.
     */
    public function up(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->string('key_format', 20)->nullable()->after('delimiter');
        });

        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['xor-cipher']
        );

        if ($cipher === false) {
            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET key_format = ? WHERE app_id = ? AND sort_order = ?',
            ['hex', (int) $cipher['id'], 40]
        );
    }

    /**
     * Удаляет колонку key_format.
     */
    public function down(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->dropColumn('key_format');
        });
    }
}
