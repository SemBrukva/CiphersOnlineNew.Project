<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет поле description_stort в таблицу переводов шифров.
 */
class AddDescriptionStortToCiphersTranslations extends Migration
{
    /**
     * Добавляет колонку description_stort в ciphers_translations.
     */
    public function up(): void
    {
        $driver = (string) config('database.default', 'sqlite');
        $table = Tables::CIPHERS_TRANSLATIONS;

        if ($driver === 'sqlite') {
            $this->db->execute(sprintf(
                "ALTER TABLE %s ADD COLUMN description_stort VARCHAR(255) NOT NULL DEFAULT ''",
                $table
            ));

            return;
        }

        $this->db->execute(sprintf(
            "ALTER TABLE `%s` ADD COLUMN `description_stort` VARCHAR(255) NOT NULL DEFAULT '' AFTER `description`",
            $table
        ));
    }

    /**
     * Удаляет колонку description_stort из ciphers_translations.
     */
    public function down(): void
    {
        $driver = (string) config('database.default', 'sqlite');
        $table = Tables::CIPHERS_TRANSLATIONS;

        if ($driver === 'sqlite') {
            $this->db->execute(sprintf(
                'CREATE TABLE %1$s_tmp (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    app_id INTEGER NOT NULL,
                    language VARCHAR(8) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    name_short VARCHAR(100) NOT NULL DEFAULT \'\',
                    description TEXT NULL,
                    meta_title VARCHAR(255) NOT NULL DEFAULT \'\',
                    meta_description TEXT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )',
                $table
            ));

            $this->db->execute(sprintf(
                'INSERT INTO %1$s_tmp (id, app_id, language, name, name_short, description, meta_title, meta_description, created_at, updated_at)
                 SELECT id, app_id, language, name, name_short, description, meta_title, meta_description, created_at, updated_at
                 FROM %1$s',
                $table
            ));

            $this->db->execute(sprintf('DROP TABLE %s', $table));
            $this->db->execute(sprintf('ALTER TABLE %s_tmp RENAME TO %s', $table, $table));
            $this->db->execute(sprintf(
                'CREATE UNIQUE INDEX uniq_ciphers_translations_app_lang ON %s (app_id, language)',
                $table
            ));
            $this->db->execute(sprintf(
                'CREATE INDEX idx_ciphers_translations_language ON %s (language)',
                $table
            ));

            return;
        }

        $this->db->execute(sprintf(
            'ALTER TABLE `%s` DROP COLUMN `description_stort`',
            $table
        ));
    }
}
