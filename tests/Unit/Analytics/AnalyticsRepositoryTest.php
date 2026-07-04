<?php

declare(strict_types=1);

namespace Tests\Unit\Analytics;

use App\Database\Database;
use App\Http\RequestContext;
use App\Repository\AnalyticsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Тесты репозитория аналитики: запись событий и агрегация с разбивкой по источнику.
 */
final class AnalyticsRepositoryTest extends TestCase
{
    private Database $db;

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
    }

    /**
     * Проверяет разбивку топа инструментов по источнику (local/embed).
     */
    public function testTopToolsSplitsBySource(): void
    {
        $this->repo->record('encoding/base64', 'encode', null, 'ip1', 'local');
        $this->repo->record('encoding/base64', 'decode', null, 'ip2', 'embed');
        $this->repo->record('encoding/base64', 'encode', null, 'ip3', 'embed');
        $this->repo->record('classical-ciphers/caesar', 'encode', null, 'ip4', 'local');

        $top = $this->repo->topTools(10, 30);

        $bySlug = [];
        foreach ($top as $row) {
            $bySlug[$row['tool_slug']] = $row;
        }

        self::assertSame(3, (int) $bySlug['encoding/base64']['total']);
        self::assertSame(1, (int) $bySlug['encoding/base64']['locals']);
        self::assertSame(2, (int) $bySlug['encoding/base64']['embeds']);
        self::assertSame(1, (int) $bySlug['classical-ciphers/caesar']['locals']);
        self::assertSame(0, (int) $bySlug['classical-ciphers/caesar']['embeds']);
    }

    /**
     * Проверяет агрегированный подсчёт событий по источнику.
     */
    public function testTotalCountBySource(): void
    {
        $this->repo->record('encoding/hex', 'encode', null, 'ip1', 'local');
        $this->repo->record('encoding/hex', 'encode', null, 'ip2', 'local');
        $this->repo->record('encoding/hex', 'encode', null, 'ip3', 'embed');

        $counts = $this->repo->totalCountBySource(30);

        self::assertSame(['local' => 2, 'embed' => 1], $counts);
    }

    /**
     * Проверяет, что источник по умолчанию — 'local'.
     */
    public function testRecordDefaultsToLocalSource(): void
    {
        $this->repo->record('encoding/binary-converter', 'encode', null, 'ip1');

        self::assertSame(['local' => 1, 'embed' => 0], $this->repo->totalCountBySource(30));
    }
}
