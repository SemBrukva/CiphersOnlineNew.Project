<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\UrlGenerator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Проверяет генерацию URL по именованным маршрутам.
 */
final class UrlGeneratorTest extends TestCase
{
    /**
     * Проверяет генерацию URL без параметров.
     */
    public function testGeneratesUrlWithoutParams(): void
    {
        $generator = new UrlGenerator(
            ['GET /contacts' => ['name' => 'contacts']],
            [],
            [],
            '/admin'
        );

        self::assertSame('/contacts', $generator->url('contacts'));
    }

    /**
     * Проверяет генерацию URL с параметром маршрута.
     */
    public function testGeneratesUrlWithParams(): void
    {
        $generator = new UrlGenerator(
            ['GET /page/{alias}' => ['name' => 'page.show']],
            [],
            [],
            '/admin'
        );

        self::assertSame('/page/privacy-policy', $generator->url('page.show', ['alias' => 'privacy-policy']));
    }

    /**
     * Проверяет префиксы admin и api маршрутов.
     */
    public function testGeneratesUrlsWithAdminAndApiPrefixes(): void
    {
        $generator = new UrlGenerator(
            [],
            ['GET /redirects/{id:\\d+}/edit' => ['name' => 'admin.redirects.edit']],
            ['GET /user/profile' => ['name' => 'api.user.profile']],
            '/secret-admin'
        );

        self::assertSame('/secret-admin/redirects/10/edit', $generator->url('admin.redirects.edit', ['id' => 10]));
        self::assertSame('/api/user/profile', $generator->url('api.user.profile'));
    }

    /**
     * Проверяет ошибку при неизвестном имени маршрута.
     */
    public function testThrowsForUnknownRouteName(): void
    {
        $generator = new UrlGenerator([], [], [], '/admin');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route name not found: missing');

        $generator->url('missing');
    }

    /**
     * Проверяет ошибку при отсутствии обязательного параметра маршрута.
     */
    public function testThrowsForMissingRouteParam(): void
    {
        $generator = new UrlGenerator(
            ['GET /page/{alias}' => ['name' => 'page.show']],
            [],
            [],
            '/admin'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing route parameter "alias" for route "page.show"');

        $generator->url('page.show');
    }

    /**
     * Проверяет ошибку при дублировании имён маршрутов.
     */
    public function testThrowsForDuplicateRouteName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Duplicate route name: duplicate');

        new UrlGenerator(
            ['GET /a' => ['name' => 'duplicate']],
            ['GET /b' => ['name' => 'duplicate']],
            [],
            '/admin'
        );
    }
}
