<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Schema;

/**
 * Добавляет короткое имя категории в таблицу переводов категорий.
 */
class AddNameShortToCipherCategoryTranslations extends Migration
{
    /**
     * Добавляет поле name_short и заполняет его для категории encoding на всех языках.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('ciphers_categories_translations', 'name_short')) {
            Schema::table('ciphers_categories_translations', static function ($table): void {
                $table->string('name_short', 100)->default('')->after('name');
            });
        }

        $shortNames = [
            'en' => 'Encoding',
            'ru' => 'Кодирование',
            'de' => 'Kodierung',
            'es' => 'Codificación',
            'fr' => 'Encodage',
            'it' => 'Codifica',
            'pt' => 'Codificação',
            'tr' => 'Kodlama',
        ];

        foreach ($shortNames as $language => $nameShort) {
            $this->db->execute(
                'UPDATE ciphers_categories_translations SET name_short = ? WHERE category_id = ? AND language = ?',
                [$nameShort, 1, $language]
            );
        }
    }

    /**
     * Удаляет поле name_short из таблицы переводов категорий.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('ciphers_categories_translations', 'name_short')) {
            return;
        }

        Schema::table('ciphers_categories_translations', static function ($table): void {
            $table->dropColumn('name_short');
        });
    }
}
