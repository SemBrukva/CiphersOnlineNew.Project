<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Создаёт таблицу для кеша статусов индексации страниц инструментов.
 */
final class CreateToolIndexationSnapshots extends Migration
{
    /**
     * Создаёт таблицу tool_indexation_snapshots.
     */
    public function up(): void
    {
        Schema::create(Tables::TOOL_INDEXATION_SNAPSHOTS, function (Blueprint $table): void {
            $table->bigId();
            $table->string('tool_slug', 100);
            $table->string('locale', 10);
            $table->string('url', 500);
            $table->string('provider', 50)->default('yandex');
            $table->string('indexing_status', 50)->nullable();
            $table->integer('http_code')->nullable();
            $table->string('crawl_date', 50)->nullable();
            $table->datetime('checked_at');
            $table->timestamps();
            $table->unique(['tool_slug', 'locale', 'provider'], 'tool_indexation_snapshots_uq');
            $table->index('tool_slug', 'tool_indexation_snapshots_tool_idx');
        });
    }

    /**
     * Удаляет таблицу tool_indexation_snapshots.
     */
    public function down(): void
    {
        Schema::dropIfExists(Tables::TOOL_INDEXATION_SNAPSHOTS);
    }
}
