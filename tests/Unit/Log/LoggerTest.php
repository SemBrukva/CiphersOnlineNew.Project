<?php

declare(strict_types=1);

namespace Tests\Unit\Log;

use App\Http\Client\HttpClient;
use App\Http\RequestContext;
use App\Log\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет форматы записи логов.
 */
final class LoggerTest extends TestCase
{
    /**
     * Проверяет JSON-line формат с обязательными top-level полями.
     */
    public function testJsonFormatWritesStructuredLine(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $dir = sys_get_temp_dir() . '/logger-test-' . bin2hex(random_bytes(4));
        mkdir($dir, 0775, true);

        $logger = new Logger(
            [
                'path' => $dir,
                'min_level' => 'debug',
                'format' => 'json',
                'webhooks' => [],
            ],
            new RequestContext('req-json-1', microtime(true), false),
            new HttpClient(),
        );

        $logger->info('Hello {name}', ['name' => 'World']);

        $file = $dir . '/production-' . date('Y-m-d') . '.log';
        $line = trim((string) file_get_contents($file));
        $payload = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('info', $payload['level']);
        self::assertSame('Hello World', $payload['msg']);
        self::assertSame('req-json-1', $payload['request_id']);
        self::assertSame('127.0.0.1', $payload['ip']);
        self::assertSame('production', $payload['env']);
        self::assertSame('World', $payload['ctx']['name']);

        unlink($file);
        rmdir($dir);
    }
}
