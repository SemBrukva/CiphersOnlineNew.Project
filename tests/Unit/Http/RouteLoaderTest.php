<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\RouteLoader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Проверяет загрузку маршрутов из Route-атрибутов.
 */
final class RouteLoaderTest extends TestCase
{
    /**
     * Проверяет, что loader находит route-атрибут и определяет группу api.
     */
    public function testLoadFromAttributesReadsApiRoute(): void
    {
        $dir = sys_get_temp_dir() . '/route-loader-test-' . bin2hex(random_bytes(4));
        mkdir($dir, 0775, true);
        mkdir($dir . '/Api', 0775, true);

        file_put_contents(
            $dir . '/Api/PingController.php',
            <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Controller\Api;
use App\Http\Attribute\Route;
final class PingController
{
    #[Route(method: 'GET', path: '/ping', name: 'tmp.api.ping')]
    public function ping(): void {}
}
PHP
        );

        $loader = new RouteLoader($dir, 'Tmp\\Controller');
        $routes = $loader->loadFromAttributes();

        self::assertArrayHasKey('GET /ping', $routes['api']);
        self::assertSame('Tmp\\Controller\\Api\\PingController', $routes['api']['GET /ping']['controller']);
        self::assertSame('tmp.api.ping', $routes['api']['GET /ping']['name']);

        unlink($dir . '/Api/PingController.php');
        rmdir($dir . '/Api');
        rmdir($dir);
    }

    /**
     * Проверяет, что merge выбрасывает исключение при дубле route-key.
     */
    public function testLoadMergedThrowsOnDuplicateRouteKey(): void
    {
        $dir = sys_get_temp_dir() . '/route-loader-dup-key-' . bin2hex(random_bytes(4));
        mkdir($dir, 0775, true);

        file_put_contents(
            $dir . '/HomeController.php',
            <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Controller;
use App\Http\Attribute\Route;
final class HomeController
{
    #[Route(method: 'GET', path: '/', name: 'tmp.home')]
    public function index(): void {}
}
PHP
        );

        $loader = new RouteLoader($dir, 'Tmp\\Controller');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Duplicate route key');

            $loader->loadMerged(
                ['GET /' => ['controller' => 'Any', 'method' => 'index']],
                [],
                []
            );
        } finally {
            unlink($dir . '/HomeController.php');
            rmdir($dir);
        }
    }

    /**
     * Проверяет, что merge выбрасывает исключение при дубле route-name.
     */
    public function testLoadMergedThrowsOnDuplicateRouteName(): void
    {
        $dir = sys_get_temp_dir() . '/route-loader-dup-name-' . bin2hex(random_bytes(4));
        mkdir($dir, 0775, true);
        mkdir($dir . '/Api', 0775, true);

        file_put_contents(
            $dir . '/Api/PingController.php',
            <<<'PHP'
<?php
declare(strict_types=1);
namespace TmpDupName\Controller\Api;
use App\Http\Attribute\Route;
final class PingController
{
    #[Route(method: 'GET', path: '/ping', name: 'shared.name')]
    public function ping(): void {}
}
PHP
        );

        $loader = new RouteLoader($dir, 'TmpDupName\\Controller');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Duplicate route name');

            $loader->loadMerged(
                ['GET /' => ['controller' => 'WebCtrl', 'method' => 'index', 'name' => 'shared.name']],
                [],
                []
            );
        } finally {
            unlink($dir . '/Api/PingController.php');
            rmdir($dir . '/Api');
            rmdir($dir);
        }
    }
}
