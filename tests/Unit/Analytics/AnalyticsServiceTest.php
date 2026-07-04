<?php

declare(strict_types=1);

namespace Tests\Unit\Analytics;

use App\Analytics\AnalyticsService;
use App\Cache\NullCache;
use App\Database\Database;
use App\Http\RequestContext;
use App\Repository\AnalyticsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса аналитики: нормализация источника и запись через репозиторий.
 *
 * NullCache всегда сообщает об отсутствии cooldown, поэтому каждое событие пишется.
 */
final class AnalyticsServiceTest extends TestCase
{
    private Database $db;

    private AnalyticsService $service;

    private AnalyticsRepository $repo;

    protected function setUp(): void
    {
        $this->db = new Database(
            ['driver' => 'sqlite', 'database' => ':memory:'],
            new RequestContext('test', microtime(true), false)
        );

        $this->db->execute(
            'CREATE TABLE tool_usage_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tool_slug TEXT NOT NULL,
                mode TEXT NOT NULL,
                user_id INTEGER NULL,
                ip_hash TEXT NOT NULL,
                source TEXT NOT NULL DEFAULT \'local\',
                created_at TEXT NOT NULL
            )'
        );

        $this->repo = new AnalyticsRepository($this->db);
        $this->service = new AnalyticsService(new NullCache(), $this->repo);
    }

    /**
     * Проверяет, что валидный источник embed сохраняется как есть.
     */
    public function testEmbedSourceIsRecorded(): void
    {
        $this->service->recordUse('encoding/base64', null, 'iphash', 'encode', 'embed');

        self::assertSame(['local' => 0, 'embed' => 1], $this->repo->totalCountBySource(30));
    }

    /**
     * Проверяет, что неизвестный источник нормализуется в 'local'.
     */
    public function testUnknownSourceFallsBackToLocal(): void
    {
        $this->service->recordUse('encoding/base64', null, 'iphash', 'encode', 'malicious');

        self::assertSame(['local' => 1, 'embed' => 0], $this->repo->totalCountBySource(30));
    }
}
