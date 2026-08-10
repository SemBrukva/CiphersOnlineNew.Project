<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Добавляет локализуемые JSON-настройки к переводам примеров шифров.
 */
final class AddSettingsToCiphersExamplesTranslations extends Migration
{
    /**
     * Добавляет поле JSON-настроек перевода.
     */
    public function up(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, function (Blueprint $table): void {
            $table->text('settings')->nullable()->after('shift');
        });
    }

    /**
     * Удаляет поле JSON-настроек перевода.
     */
    public function down(): void
    {
        Schema::table(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, function (Blueprint $table): void {
            $table->dropColumn('settings');
        });
    }
}
