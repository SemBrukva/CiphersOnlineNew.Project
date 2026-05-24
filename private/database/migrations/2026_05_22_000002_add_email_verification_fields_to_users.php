<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;

/**
 * Добавляет поля верификации email в таблицу пользователей.
 */
class AddEmailVerificationFieldsToUsers extends Migration
{
    /**
     * Добавляет колонки верификации email.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->datetime('email_verified_at')->nullable();
            $table->string('email_verification_token')->nullable();
            $table->datetime('email_verification_sent_at')->nullable();
        });
    }

    /**
     * Удаляет колонки верификации email.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
            $table->dropColumn('email_verification_token');
            $table->dropColumn('email_verification_sent_at');
        });
    }
}
