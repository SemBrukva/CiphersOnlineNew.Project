<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;

/**
 * Создаёт таблицы категорий шифров и их переводов, а также импортирует стартовые данные.
 */
class CreateCipherCategoriesTables extends Migration
{
    /**
     * Создаёт таблицы и наполняет их данными из предоставленного дампа.
     */
    public function up(): void
    {
        Schema::create('cipher_categories', function (Blueprint $table) {
            $table->bigId();
            $table->string('alias', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('published')->default(1);
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->unique('alias', 'uniq_cipher_categories_alias');
            $table->index(['published', 'sort_order'], 'idx_cipher_categories_published_sort');
        });

        Schema::create('cipher_category_translations', function (Blueprint $table) {
            $table->bigId();
            $table->unsignedBigInteger('category_id');
            $table->string('language', 8);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('meta_title', 255)->default('');
            $table->text('meta_description')->nullable();
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->datetime('updated_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->unique(['category_id', 'language'], 'uniq_cipher_category_translations_category_lang');
            $table->index('language', 'idx_cipher_category_translations_language');
            $table->foreign('category_id')
                ->references('id')
                ->on('cipher_categories')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        $this->db->transaction(function (): void {
            $this->db->execute(
                'INSERT INTO cipher_categories (id, alias, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [1, 'encoding', 10, 1, '2026-05-17 08:35:59', '2026-05-17 08:35:59']
            );
            $this->db->execute(
                'INSERT INTO cipher_categories (id, alias, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [2, 'classical-ciphers', 0, 1, '2026-05-20 16:57:23', '2026-05-20 16:57:23']
            );

            $translations = [
                [1, 1, 'ru', 'Кодирование и преобразование данных', 'Инструменты для кодирования, декодирования и преобразования текста и бинарных данных. Base64, Hex, URL Encode, JWT и другие форматы для разработчиков.', 'Кодирование и преобразование данных | Ciphers Online', 'Hub инструментов кодирования: Base64, Hex, URL Encode и JWT Decoder для разработчиков.', '2026-05-17 08:36:00', '2026-05-17 08:36:00'],
                [2, 1, 'en', 'Encoding and Data Conversion', 'Tools for encoding, decoding, and transforming text and binary data. Base64, Hex, URL Encode, JWT and more for developers.', 'Encoding and Data Conversion | Ciphers Online', 'Encoding tools hub: Base64, Hex, URL Encode and JWT Decoder for developers.', '2026-05-17 08:36:00', '2026-05-17 08:36:00'],
                [4, 1, 'de', 'Kodierung und Datenkonvertierung', 'Tools zum Kodieren, Dekodieren und Transformieren von Text- und Binärdaten. Base64, Hex, URL Encode, JWT und mehr für Entwickler.', 'Kodierung und Datenkonvertierung | Ciphers Online', 'Hub für Kodierungs-Tools: Base64, Hex, URL Encode und JWT Decoder für Entwickler.', '2026-05-17 08:41:11', '2026-05-17 08:41:11'],
                [5, 1, 'es', 'Codificación y conversión de datos', 'Herramientas para codificar, decodificar y transformar texto y datos binarios. Base64, Hex, URL Encode, JWT y más para desarrolladores.', 'Codificación y conversión de datos | Ciphers Online', 'Hub de herramientas de codificación: Base64, Hex, URL Encode y JWT Decoder para desarrolladores.', '2026-05-17 08:41:11', '2026-05-17 08:41:11'],
                [6, 1, 'fr', 'Encodage et conversion des données', 'Outils pour encoder, décoder et transformer du texte et des données binaires. Base64, Hex, URL Encode, JWT et plus pour les développeurs.', 'Encodage et conversion des données | Ciphers Online', 'Hub des outils d’encodage : Base64, Hex, URL Encode et JWT Decoder pour développeurs.', '2026-05-17 08:41:11', '2026-05-17 08:41:11'],
                [7, 1, 'it', 'Codifica e conversione dei dati', 'Strumenti per codificare, decodificare e trasformare testo e dati binari. Base64, Hex, URL Encode, JWT e altro per sviluppatori.', 'Codifica e conversione dei dati | Ciphers Online', 'Hub strumenti di codifica: Base64, Hex, URL Encode e JWT Decoder per sviluppatori.', '2026-05-17 08:41:11', '2026-05-17 08:41:11'],
                [8, 1, 'pt', 'Codificação e conversão de dados', 'Ferramentas para codificar, decodificar e transformar texto e dados binários. Base64, Hex, URL Encode, JWT e mais para desenvolvedores.', 'Codificação e conversão de dados | Ciphers Online', 'Hub de ferramentas de codificação: Base64, Hex, URL Encode e JWT Decoder para desenvolvedores.', '2026-05-17 08:41:11', '2026-05-17 08:41:11'],
                [9, 1, 'tr', 'Kodlama ve veri dönüştürme', 'Metin ve ikili verileri kodlamak, çözmek ve dönüştürmek için araçlar. Geliştiriciler için Base64, Hex, URL Encode, JWT ve daha fazlası.', 'Kodlama ve veri dönüştürme | Ciphers Online', 'Geliştiriciler için kodlama araçları merkezi: Base64, Hex, URL Encode ve JWT Decoder.', '2026-05-17 08:41:11', '2026-05-17 08:41:11'],
                [11, 2, 'en', 'Classical Ciphers', '', '', '', '2026-05-20 16:57:23', '2026-05-20 16:57:23'],
                [12, 2, 'ru', 'Classical Ciphers', '', '', '', '2026-05-20 16:57:23', '2026-05-20 16:57:23'],
            ];

            foreach ($translations as $translation) {
                $this->db->execute(
                    'INSERT INTO cipher_category_translations (id, category_id, language, name, description, meta_title, meta_description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    $translation
                );
            }
        });
    }

    /**
     * Удаляет таблицы переводов и категорий шифров.
     */
    public function down(): void
    {
        Schema::dropIfExists('cipher_category_translations');
        Schema::dropIfExists('cipher_categories');
    }
}
