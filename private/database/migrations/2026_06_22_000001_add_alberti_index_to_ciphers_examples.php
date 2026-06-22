<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Добавляет колонку alberti_index в ciphers_examples и заполняет значение 'A' для примеров шифра Альберти.
 */
class AddAlbertiIndexToCiphersExamples extends Migration
{
    /**
     * Добавляет колонку и обновляет примеры шифра Альберти.
     */
    public function up(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->string('alberti_index', 1)->default('')->after('key_format');
        });

        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['alberti']
        );

        if ($cipher === false) {
            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET alberti_index = ? WHERE app_id = ?',
            ['A', (int) $cipher['id']]
        );
    }

    /**
     * Удаляет колонку alberti_index.
     */
    public function down(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->dropColumn('alberti_index');
        });
    }
}
