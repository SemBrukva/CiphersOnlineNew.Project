<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Schema;

/**
 * Добавляет режим вычисления в таблицу шифров.
 */
class AddCalculationModeToCiphers extends Migration
{
    /**
     * Добавляет поле calculation_mode со значениями api/client.
     */
    public function up(): void
    {
        if (Schema::hasColumn('ciphers', 'calculation_mode')) {
            return;
        }

        $driver = (string) config('database.default', 'sqlite');

        if ($driver === 'mysql') {
            $this->db->execute(
                "ALTER TABLE ciphers ADD COLUMN calculation_mode ENUM('api', 'client') NOT NULL DEFAULT 'client' AFTER alias"
            );
        } else {
            $this->db->execute(
                "ALTER TABLE ciphers ADD COLUMN calculation_mode TEXT NOT NULL DEFAULT 'client' CHECK (calculation_mode IN ('api', 'client'))"
            );
        }
    }

    /**
     * Удаляет поле calculation_mode из таблицы шифров.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('ciphers', 'calculation_mode')) {
            return;
        }

        Schema::table('ciphers', static function ($table): void {
            $table->dropColumn('calculation_mode');
        });
    }
}
