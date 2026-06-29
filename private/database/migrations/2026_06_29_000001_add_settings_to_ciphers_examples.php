<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Добавляет колонку settings (JSON) в ciphers_examples и проставляет
 * дефолтные значения для примеров hashing-инструментов HMAC, PBKDF2, bcrypt, Argon2.
 *
 * Поле хранит произвольный объект «id поля формы → значение», который при клике
 * на пример применяется к настройкам инструмента, чтобы вывод воспроизводился точно.
 */
class AddSettingsToCiphersExamples extends Migration
{
    /**
     * Добавляет колонку и засеивает settings для существующих hashing-примеров.
     */
    public function up(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->text('settings')->nullable();
        });

        $defaults = [
            'hmac' => [
                'ciphers-hash-algorithm'  => 'hmac-sha-256',
                'ciphers-hmac-key-format' => 'text',
            ],
            'pbkdf2' => [
                'ciphers-kdf-hash'       => 'SHA-256',
                'ciphers-kdf-iterations' => '600000',
                'ciphers-kdf-key-length' => '32',
                'ciphers-kdf-salt'       => '',
            ],
            'bcrypt' => [
                'ciphers-kdf-cost' => '12',
            ],
            'argon2' => [
                'ciphers-kdf-variant'     => 'argon2id',
                'ciphers-kdf-memory'      => '19456',
                'ciphers-kdf-iterations'  => '2',
                'ciphers-kdf-parallelism' => '1',
                'ciphers-kdf-key-length'  => '32',
                'ciphers-kdf-salt'        => '',
            ],
        ];

        foreach ($defaults as $alias => $settings) {
            $cipher = $this->db->fetch(
                'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
                [$alias]
            );

            if ($cipher === false) {
                continue;
            }

            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET settings = ? WHERE app_id = ?',
                [json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (int) $cipher['id']]
            );
        }
    }

    /**
     * Удаляет колонку settings.
     */
    public function down(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES, function (Blueprint $table): void {
            $table->dropColumn('settings');
        });
    }
}
