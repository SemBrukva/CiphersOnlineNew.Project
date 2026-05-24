<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;

/**
 * Создаёт таблицу redirects для управления HTTP-редиректами.
 */
class CreateRedirectsTable extends Migration
{
    /**
     * Создаёт таблицу.
     */
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 500)->unique();
            $table->string('to_path', 500);
            $table->smallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->integer('hit_count')->default(0);
            $table->datetime('created_at');
            $table->datetime('updated_at');
        });
    }

    /**
     * Удаляет таблицу.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
}
