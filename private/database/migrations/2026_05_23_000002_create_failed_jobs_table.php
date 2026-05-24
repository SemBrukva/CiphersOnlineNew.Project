<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;

/**
 * Создаёт таблицу failed_jobs для упавших задач очереди.
 */
class CreateFailedJobsTable extends Migration
{
    /**
     * Создаёт таблицу.
     */
    public function up(): void
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->bigId();
            $table->string('queue', 100)->default('default');
            $table->longText('payload');
            $table->longText('exception');
            $table->datetime('failed_at');
        });
    }

    /**
     * Удаляет таблицу.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
}
