<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use App\Cache\CacheInterface;
use App\Cache\NullCache;
use App\Cache\TaggedCache;
use App\Cache\TaggedCacheInterface;
use PHPUnit\Framework\TestCase;

/**
 * Тесты тегированного кеша TaggedCache.
 */
final class TaggedCacheTest extends TestCase
{
    // -----------------------------------------------------------------------
    // set / get / has
    // -----------------------------------------------------------------------

    public function testSetAndGetDelegate(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'articles');

        $tagged->set('article:1', ['title' => 'Hello'], 60);

        self::assertSame(['title' => 'Hello'], $tagged->get('article:1'));
    }

    public function testGetReturnsDefaultWhenMissing(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'articles');

        self::assertSame('fallback', $tagged->get('missing', 'fallback'));
    }

    public function testHasDelegates(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'articles');

        self::assertFalse($tagged->has('key'));

        $tagged->set('key', 'value');

        self::assertTrue($tagged->has('key'));
    }

    // -----------------------------------------------------------------------
    // Индекс тега
    // -----------------------------------------------------------------------

    public function testSetRegistersKeyInTagIndex(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'posts');

        $tagged->set('post:1', 'a');
        $tagged->set('post:2', 'b');

        $index = $store->get('_tag:posts', []);
        self::assertContains('post:1', $index);
        self::assertContains('post:2', $index);
    }

    public function testSetDoesNotDuplicateKeysInIndex(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'posts');

        $tagged->set('post:1', 'a');
        $tagged->set('post:1', 'b');

        $index = $store->get('_tag:posts', []);
        self::assertCount(1, $index);
    }

    // -----------------------------------------------------------------------
    // remember
    // -----------------------------------------------------------------------

    public function testRememberComputesOnMiss(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'pages');

        $calls = 0;
        $result = $tagged->remember('page:home', 60, function () use (&$calls): string {
            $calls++;
            return 'home-content';
        });

        self::assertSame('home-content', $result);
        self::assertSame(1, $calls);
    }

    public function testRememberReturnsCachedOnHit(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'pages');

        $calls = 0;
        $tagged->remember('page:home', 60, function () use (&$calls): string {
            $calls++;
            return 'home-content';
        });
        $result = $tagged->remember('page:home', 60, function () use (&$calls): string {
            $calls++;
            return 'should-not-be-called';
        });

        self::assertSame('home-content', $result);
        self::assertSame(1, $calls);
    }

    public function testRememberAddsKeyToTagIndex(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'pages');

        $tagged->remember('page:home', 60, fn () => 'content');

        $index = $store->get('_tag:pages', []);
        self::assertContains('page:home', $index);
    }

    public function testRememberDoesNotAddToIndexOnHit(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'pages');

        $tagged->remember('page:home', 60, fn () => 'content');
        $tagged->remember('page:home', 60, fn () => 'content');

        $index = $store->get('_tag:pages', []);
        self::assertCount(1, $index);
    }

    // -----------------------------------------------------------------------
    // delete
    // -----------------------------------------------------------------------

    public function testDeleteRemovesKeyFromCacheAndIndex(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'users');

        $tagged->set('user:5', 'data');
        $tagged->delete('user:5');

        self::assertFalse($tagged->has('user:5'));
        $index = $store->get('_tag:users', []);
        self::assertNotContains('user:5', $index);
    }

    // -----------------------------------------------------------------------
    // flush
    // -----------------------------------------------------------------------

    public function testFlushDeletesAllTaggedKeys(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'redirects');

        $tagged->set('redirects:active_map', ['foo' => 'bar']);
        $tagged->set('redirects:stats', [1, 2, 3]);

        $tagged->flush();

        self::assertFalse($store->has('redirects:active_map'));
        self::assertFalse($store->has('redirects:stats'));
    }

    public function testFlushDeletesTagIndex(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'redirects');

        $tagged->set('redirects:active_map', 'x');
        $tagged->flush();

        self::assertFalse($store->has('_tag:redirects'));
    }

    public function testFlushEmptyTagIsNoError(): void
    {
        $store = new ArrayCacheStub();
        $tagged = new TaggedCache($store, 'empty');

        $tagged->flush(); // не должен бросать исключений

        self::assertFalse($store->has('_tag:empty'));
    }

    // -----------------------------------------------------------------------
    // Независимость тегов
    // -----------------------------------------------------------------------

    public function testFlushDoesNotAffectOtherTags(): void
    {
        $store = new ArrayCacheStub();
        $tagA = new TaggedCache($store, 'tagA');
        $tagB = new TaggedCache($store, 'tagB');

        $tagA->set('a:1', 'alpha');
        $tagB->set('b:1', 'beta');

        $tagA->flush();

        self::assertFalse($store->has('a:1'));
        self::assertTrue($store->has('b:1'));
    }

    // -----------------------------------------------------------------------
    // NullCache интеграция
    // -----------------------------------------------------------------------

    public function testNullCacheTagReturnsTaggedCacheInstance(): void
    {
        $null = new NullCache();

        self::assertInstanceOf(TaggedCacheInterface::class, $null->tag('any'));
    }

    public function testNullCacheTaggedRememberAlwaysCallsCallback(): void
    {
        $null = new NullCache();
        $tagged = $null->tag('test');

        $calls = 0;
        $tagged->remember('key', 60, function () use (&$calls): string {
            $calls++;
            return 'val';
        });
        $tagged->remember('key', 60, function () use (&$calls): string {
            $calls++;
            return 'val';
        });

        self::assertSame(2, $calls);
    }

    public function testNullCacheTaggedFlushIsNoOp(): void
    {
        $null = new NullCache();
        $tagged = $null->tag('test');
        $tagged->set('k', 'v');
        $tagged->flush(); // no exception expected

        $this->addToAssertionCount(1);
    }

    // -----------------------------------------------------------------------
    // CacheInterface::tag() возвращает правильный тип
    // -----------------------------------------------------------------------

    public function testArrayCacheTagReturnsTaggedCache(): void
    {
        $store = new ArrayCacheStub();

        self::assertInstanceOf(TaggedCacheInterface::class, $store->tag('test'));
    }
}

/**
 * Простая in-memory реализация CacheInterface для тестов.
 */
final class ArrayCacheStub implements CacheInterface
{
    /** @var array<string, mixed> */
    public array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->store) ? $this->store[$key] : $default;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->store[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->store);
    }

    public function delete(string $key): void
    {
        unset($this->store[$key]);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function flush(): void
    {
        $this->store = [];
    }

    public function getStats(): array
    {
        return ['hits' => 0, 'misses' => 0];
    }

    public function tag(string $tag): TaggedCacheInterface
    {
        return new TaggedCache($this, $tag);
    }
}
