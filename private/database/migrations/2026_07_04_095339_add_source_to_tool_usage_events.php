<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Добавляет столбец source в события использования инструментов,
 * чтобы отличать основной сайт ('local') от встраиваемых виджетов ('embed').
 */
class AddSourceToToolUsageEvents extends Migration
{
    /**
     * Применяет миграцию.
     */
    public function up(): void
    {
        Schema::table(Tables::TOOL_USAGE_EVENTS, function (Blueprint $table): void {
            $table->string('source', 10)->default('local');
            $table->index(['source', 'created_at'], 'tool_usage_events_source_created_idx');
        });
    }

    /**
     * Откатывает миграцию.
     */
    public function down(): void
    {
        Schema::table(Tables::TOOL_USAGE_EVENTS, function (Blueprint $table): void {
            $table->dropColumn('source');
        });
    }
}
