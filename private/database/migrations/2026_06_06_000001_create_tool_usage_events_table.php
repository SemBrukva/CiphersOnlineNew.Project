<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Создаёт таблицу событий использования инструментов для аналитики.
 */
final class CreateToolUsageEventsTable extends Migration
{
    public function up(): void
    {
        Schema::create(Tables::TOOL_USAGE_EVENTS, function (Blueprint $table): void {
            $table->bigId();
            $table->string('tool_slug', 100);
            $table->string('mode', 10);
            $table->unsignedInteger('user_id')->nullable();
            $table->string('ip_hash', 64);
            $table->datetime('created_at');
            $table->index(['tool_slug', 'created_at'], 'tool_usage_events_tool_created_idx');
            $table->index('user_id', 'tool_usage_events_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::TOOL_USAGE_EVENTS);
    }
}
