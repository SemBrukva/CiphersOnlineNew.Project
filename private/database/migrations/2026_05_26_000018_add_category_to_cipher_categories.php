<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Schema;

/**
 * Добавляет тип категории в таблицу категорий шифров.
 */
class AddCategoryToCipherCategories extends Migration
{
    /**
     * Добавляет поле category и заполняет его для существующих записей.
     */
    public function up(): void
    {
        if (Schema::hasColumn('cipher_categories', 'category')) {
            return;
        }

        $driver = (string) config('database.default', 'sqlite');

        if ($driver === 'mysql') {
            $this->db->execute(
                "ALTER TABLE cipher_categories ADD COLUMN category ENUM('cipher', 'encoding') NOT NULL DEFAULT 'cipher' AFTER alias"
            );
        } else {
            $this->db->execute(
                "ALTER TABLE cipher_categories ADD COLUMN category TEXT NOT NULL DEFAULT 'cipher' CHECK (category IN ('cipher', 'encoding'))"
            );
        }

        $this->db->execute(
            "UPDATE cipher_categories SET category = 'encoding' WHERE alias = ?",
            ['encoding']
        );
        $this->db->execute(
            "UPDATE cipher_categories SET category = 'cipher' WHERE alias <> ?",
            ['encoding']
        );
    }

    /**
     * Удаляет поле category из таблицы категорий шифров.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('cipher_categories', 'category')) {
            return;
        }

        Schema::table('cipher_categories', static function ($table): void {
            $table->dropColumn('category');
        });
    }
}

