<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;

/**
 * Создаёт таблицу contacts для хранения сообщений обратной связи.
 */
class CreateContactsTable extends Migration
{
    /**
     * Создаёт таблицу.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('name', 100);
            $table->string('email', 100);
            $table->text('message');
            $table->tinyInteger('is_read')->unsigned()->default(0);
            $table->string('ip', 45)->default('');
            $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
            $table->index('user_id', 'idx_contacts_user_id');
            $table->index('created_at', 'idx_contacts_created_at');
            $table->index('is_read', 'idx_contacts_is_read');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('SET NULL')
                ->onUpdate('CASCADE');
        });
    }

    /**
     * Удаляет таблицу.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
}
