<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Schema\Blueprint;
use App\Database\Schema\Schema;
use App\Database\Tables;

/**
 * Создаёт таблицы семантического ядра и будущей аналитики позиций.
 */
final class CreateSemanticCoreTables extends Migration
{
    /**
     * Создаёт таблицы кластеров, запросов и снимков позиций.
     */
    public function up(): void
    {
        Schema::create(Tables::SEMANTIC_CLUSTERS, function (Blueprint $table): void {
            $table->bigId();
            $table->string('schema_version', 50);
            $table->string('locale', 10);
            $table->string('cluster', 255);
            $table->string('intent', 50);
            $table->string('status', 50);
            $table->string('tool_slug', 150);
            $table->string('url', 255);
            $table->string('content_file', 255);
            $table->string('json_path', 255);
            $table->string('source_provider', 100)->nullable();
            $table->string('score_metric', 100)->nullable();
            $table->integer('total_score')->default(0);
            $table->integer('queries_count')->default(0);
            $table->text('analysis_json')->nullable();
            $table->text('curation_json')->nullable();
            $table->text('notes')->nullable();
            $table->datetime('synced_at');
            $table->timestamps();
            $table->unique(['locale', 'tool_slug', 'cluster'], 'semantic_clusters_identity_uq');
            $table->index(['locale', 'status'], 'semantic_clusters_locale_status_idx');
            $table->index('tool_slug', 'semantic_clusters_tool_idx');
        });

        Schema::create(Tables::SEMANTIC_QUERIES, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('cluster_id');
            $table->string('query', 255);
            $table->string('normalized_query', 255);
            $table->integer('score')->default(0);
            $table->string('competitiveness', 50)->nullable();
            $table->string('priority', 50);
            $table->string('target', 50);
            $table->text('intent_json')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['cluster_id', 'normalized_query'], 'semantic_queries_cluster_query_uq');
            $table->index(['cluster_id', 'priority'], 'semantic_queries_cluster_priority_idx');
            $table->index('normalized_query', 'semantic_queries_normalized_idx');
        });

        Schema::create(Tables::SEMANTIC_RANK_SNAPSHOTS, function (Blueprint $table): void {
            $table->bigId();
            $table->unsignedBigInteger('query_id');
            $table->string('provider', 100);
            $table->date('checked_at');
            $table->integer('position')->nullable();
            $table->integer('impressions')->nullable();
            $table->integer('clicks')->nullable();
            $table->decimal('ctr', 8, 4)->nullable();
            $table->string('url', 255)->nullable();
            $table->text('raw_json')->nullable();
            $table->timestamps();
            $table->unique(['query_id', 'provider', 'checked_at'], 'semantic_rank_snapshot_uq');
            $table->index(['provider', 'checked_at'], 'semantic_rank_provider_date_idx');
        });
    }

    /**
     * Удаляет таблицы семантического ядра.
     */
    public function down(): void
    {
        Schema::dropIfExists(Tables::SEMANTIC_RANK_SNAPSHOTS);
        Schema::dropIfExists(Tables::SEMANTIC_QUERIES);
        Schema::dropIfExists(Tables::SEMANTIC_CLUSTERS);
    }
}
