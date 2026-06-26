<?php

declare(strict_types=1);

namespace Tests\Unit\Semantic;

use App\Semantic\SemanticRawImporter;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет импорт сырой семантики из CSV в semantic-core JSON.
 */
final class SemanticRawImporterTest extends TestCase
{
    /**
     * Проверяет генерацию JSON с приоритетами, интентами и source-блоком.
     */
    public function testImportCreatesSemanticCoreJson(): void
    {
        $root = sys_get_temp_dir() . '/semantic-raw-importer-' . bin2hex(random_bytes(4));
        $raw = $root . '/raw';
        $core = $root . '/core';
        mkdir($raw . '/ru/codes-and-alphabets', 0777, true);

        file_put_contents($raw . '/ru/codes-and-alphabets/demo.csv', implode("\n", [
            'query;score;competitiveness',
            'азбука морзе переводчик;1000;AVERAGE',
            'переводчик с азбуки морзе на русский;300;AVERAGE',
            'переводчик с русского на азбуку морзе;200;LOW',
            'азбука морзе переводчик со звуком;50;LOW',
        ]));

        file_put_contents($raw . '/ru/codes-and-alphabets/demo.meta.json', json_encode([
            'schema' => 'semantic-raw.v1',
            'locale' => 'ru',
            'source' => 'test',
            'score_metric' => 'test_score',
            'cluster' => 'азбука морзе переводчик',
            'tool' => 'codes-and-alphabets/demo',
            'imported_at' => '2026-06-25T21:56:00+02:00',
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $importer = new SemanticRawImporter($raw, $core);
        $result = $importer->import($raw . '/ru/codes-and-alphabets/demo.csv');

        $json = json_decode((string) file_get_contents($core . '/ru/codes-and-alphabets/demo.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertStringEndsWith('/raw/ru/codes-and-alphabets/demo.csv', $result['input']);
        self::assertSame(4, $result['queries']);
        self::assertSame(1550, $result['total_score']);
        self::assertSame('semantic-core.v1', $json['schema']);
        self::assertSame('draft', $json['status']);
        self::assertSame('test_score', $json['analysis']['score_metric']);
        self::assertSame('primary', $json['queries'][0]['priority']);
        self::assertContains('decode', $json['queries'][1]['intent']);
        self::assertContains('encode', $json['queries'][2]['intent']);
        self::assertContains('audio', $json['queries'][3]['intent']);
    }
}
