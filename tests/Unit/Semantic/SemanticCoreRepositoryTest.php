<?php

declare(strict_types=1);

namespace Tests\Unit\Semantic;

use App\Semantic\SemanticCoreRepository;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет чтение и валидацию файлов семантического ядра.
 */
final class SemanticCoreRepositoryTest extends TestCase
{
    /**
     * Проверяет построение сводки по реальному прототипу семантики.
     */
    public function testSummaryReadsPrototypeCluster(): void
    {
        $repository = new SemanticCoreRepository(PRIVATE_PATH . '/storage/semantic-core');

        $summary = $repository->summary();

        self::assertGreaterThanOrEqual(1, $summary['clusters']);
        self::assertGreaterThanOrEqual(3, $summary['queries']);
        self::assertGreaterThanOrEqual(4363, $summary['total_volume']);
        self::assertSame(0, $summary['issues']);
    }

    /**
     * Проверяет, что валидатор находит отсутствующий файл связанного контента.
     */
    public function testValidateAllReportsMissingContentFile(): void
    {
        $root = sys_get_temp_dir() . '/semantic-core-test-' . bin2hex(random_bytes(4));
        mkdir($root . '/ru/demo', 0777, true);

        file_put_contents($root . '/ru/demo/missing.json', json_encode([
            'schema' => 'semantic-core.v1',
            'locale' => 'ru',
            'cluster' => 'demo',
            'intent' => 'tool',
            'status' => 'planned',
            'tool' => [
                'slug' => 'demo/missing',
                'url' => '/ru/demo/missing',
                'content_file' => 'private/storage/content/demo/missing/ru.json',
            ],
            'queries' => [
                ['query' => 'demo query', 'volume' => 10, 'priority' => 'primary'],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $repository = new SemanticCoreRepository($root);

        $issues = $repository->validateAll();

        self::assertNotEmpty($issues);
        self::assertStringContainsString('content_file не найден', $issues[0]['message']);
    }
}
