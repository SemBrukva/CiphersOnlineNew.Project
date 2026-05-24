<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;

/**
 * Добавляет поле language в таблицу пользователей.
 *
 * Хранит предпочтительный язык интерфейса пользователя.
 * Значение по умолчанию совпадает с APP_LOCALE.
 */
class AddLanguageToUsers extends Migration
{
    /**
     * Добавляет колонку language.
     */
    public function up(): void
    {
        $default = (string) config('locale.locale');

        Schema::table('users', function (Blueprint $table) use ($default) {
            $table->string('language', 5)->default($default);
        });
    }

    /**
     * Удаляет колонку language.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
}
