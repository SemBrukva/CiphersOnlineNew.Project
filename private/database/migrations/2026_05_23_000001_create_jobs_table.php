<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;

/**
 * Создаёт таблицу jobs для хранения задач очереди.
 */
class CreateJobsTable extends Migration
{
    /**
     * Создаёт таблицу.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigId();
            $table->string('queue', 100)->default('default');
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('created_at');
            $table->index('queue', 'jobs_queue_idx');
            $table->index('reserved_at', 'jobs_reserved_at_idx');
            $table->index('available_at', 'jobs_available_at_idx');
        });
    }

    /**
     * Удаляет таблицу.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
}
