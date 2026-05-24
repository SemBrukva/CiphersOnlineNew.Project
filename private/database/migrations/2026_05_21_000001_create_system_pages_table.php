<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;

/**
 * Создаёт таблицу system_pages для хранения статических страниц сайта.
 */
class CreateSystemPagesTable extends Migration
{
    /**
     * Создаёт таблицу.
     */
    public function up(): void
    {
        Schema::create('system_pages', function (Blueprint $table) {
            $table->id();
            $table->string('language', 2);
            $table->string('alias', 50);
            $table->string('name', 100);
            $table->text('text');
            $table->boolean('published')->default(false);
            $table->unique(['language', 'alias']);
        });
    }

    /**
     * Удаляет таблицу.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_pages');
    }
}
