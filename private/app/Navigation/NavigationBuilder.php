<?php

declare(strict_types=1);

namespace App\Navigation;

use App\Auth\Auth;
use App\Cache\CacheInterface;
use App\I18n\Translator;
use App\Repository\CipherRepository;

/**
 * Сборщик пунктов главного навигационного меню.
 *
 * Объединяет статические пункты из конфига и динамические (зависят от
 * контекста запроса: авторизация, локаль). Разрешает лейблы через транслятор
 * и вычисляет флаг active — шаблон остаётся «тупым».
 */
final class NavigationBuilder
{
    public function __construct(
        private readonly Auth             $auth,
        private readonly Translator       $translator,
        private readonly CipherRepository $ciphers,
        private readonly CacheInterface   $cache,
    ) {
    }

    /**
     * Собирает финальный список пунктов меню для текущего запроса.
     *
     * @param  string $currentPath  Путь запроса (для подсветки активного пункта).
     * @param  string $localePrefix Префикс локали, например '/ru' или ''.
     * @return list<array{label: string, url: string, active: bool, icon: string|null, children?: array<int, array{label: string, url: string, active: bool}>}>
     */
    public function build(string $currentPath, string $localePrefix): array
    {
        $language        = $this->translator->getLocale();
        $defaultLanguage = $this->translator->getDefaultLocale();
        $cacheTtl        = (int) config('cache.ttl', 3600);

        $ciphersByCategory = $this->cache->tag('ciphers')->remember(
            'nav.ciphers_by_category.' . $language . '.' . $defaultLanguage,
            $cacheTtl,
            fn (): array => $this->ciphers->listPublishedForNavigation($language, $defaultLanguage)
        );

        $items = [];

        foreach (config('navigation.main', []) as $item) {
            if (($item['type'] ?? null) === 'group') {
                $items[] = $this->makeGroupItem($item, $ciphersByCategory, $currentPath, $localePrefix);
                continue;
            }

            $items[] = $this->makeNavEntry($item, $ciphersByCategory, $currentPath, $localePrefix);
        }

        if ($this->auth->check()) {
            // Приватный маршрут — языковой префикс не используется
            // Пока не выводим
            //$items[] = $this->makeItem('MENU_CABINET', '/cabinet', null, $currentPath, '');
        }

        return $items;
    }

    /**
     * Формирует один пункт меню: либо категорию с сервисами, либо обычную ссылку.
     *
     * @param  array<string, mixed>                        $item              Конфиг пункта.
     * @param  array<string, list<array{alias: string, name: string}>> $ciphersByCategory Сервисы по категориям.
     * @return array<string, mixed>
     */
    private function makeNavEntry(
        array  $item,
        array  $ciphersByCategory,
        string $currentPath,
        string $localePrefix,
    ): array {
        $categoryAlias = $item['category_alias'] ?? null;

        if ($categoryAlias !== null && isset($ciphersByCategory[$categoryAlias])) {
            return $this->makeDropdownItem(
                $item['title_key'],
                $item['url'],
                $item['icon'] ?? null,
                $categoryAlias,
                $ciphersByCategory[$categoryAlias],
                $currentPath,
                $localePrefix,
            );
        }

        return $this->makeItem(
            $item['title_key'],
            $item['url'],
            $item['icon'] ?? null,
            $currentPath,
            $localePrefix,
        );
    }

    /**
     * Формирует пункт-группу: дропдаун без своей страницы, дети — пункты-категории
     * (каждый со своим списком сервисов, раскрывается flyout-подменю).
     *
     * @param  array<string, mixed>                        $item              Конфиг группы.
     * @param  array<string, list<array{alias: string, name: string}>> $ciphersByCategory Сервисы по категориям.
     * @return array{label: string, url: null, icon: string|null, active: bool, is_group: true, children: list<array<string, mixed>>}
     */
    private function makeGroupItem(
        array  $item,
        array  $ciphersByCategory,
        string $currentPath,
        string $localePrefix,
    ): array {
        $children = [];

        foreach ($item['children'] ?? [] as $child) {
            $children[] = $this->makeNavEntry($child, $ciphersByCategory, $currentPath, $localePrefix);
        }

        $active = (bool) array_filter($children, static fn (array $c): bool => $c['active']);

        return [
            'label'    => $this->translator->get($item['title_key']),
            'url'      => null,
            'icon'     => $item['icon'] ?? null,
            'active'   => $active,
            'is_group' => true,
            'children' => $children,
        ];
    }

    /**
     * Формирует пункт меню с выпадающим списком сервисов категории.
     *
     * @param  list<array{alias: string, name: string}> $ciphers Список шифров категории.
     * @return array{label: string, url: string, active: bool, icon: string|null, children: list<array{label: string, url: string, active: bool}>}
     */
    private function makeDropdownItem(
        string  $titleKey,
        string  $path,
        ?string $icon,
        string  $categoryAlias,
        array   $ciphers,
        string  $currentPath,
        string  $localePrefix,
    ): array {
        $categoryUrl = ($localePrefix !== '' && $path === '/') ? $localePrefix : $localePrefix . $path;
        $children    = [];

        foreach ($ciphers as $cipher) {
            $alias      = $cipher['alias'];
            $cipherPath = '/' . $categoryAlias . '/' . $alias;
            $cipherUrl  = $localePrefix !== '' ? $localePrefix . $cipherPath : $cipherPath;

            $children[] = [
                'label'  => $cipher['name'],
                'url'    => $cipherUrl,
                'active' => $currentPath === $cipherPath || $currentPath === $cipherUrl,
            ];
        }

        $pageActive   = $currentPath === $path || $currentPath === $categoryUrl;
        $childActive  = (bool) array_filter($children, static fn (array $c): bool => $c['active']);

        return [
            'label'       => $this->translator->get($titleKey),
            'url'         => $categoryUrl,
            'active'      => $pageActive || $childActive,
            'page_active' => $pageActive,
            'icon'        => $icon,
            'children'    => $children,
        ];
    }

    /**
     * Формирует массив данных одного пункта меню.
     *
     * @param  string      $titleKey     Ключ перевода.
     * @param  string      $path         Путь без префикса локали.
     * @param  string|null $icon         CSS-класс Bootstrap Icons или null.
     * @param  string      $currentPath  Текущий путь запроса.
     * @param  string      $localePrefix Префикс локали.
     * @return array{label: string, url: string, active: bool, icon: string|null}
     */
    private function makeItem(
        string  $titleKey,
        string  $path,
        ?string $icon,
        string  $currentPath,
        string  $localePrefix,
    ): array {
        $url = ($localePrefix !== '' && $path === '/') ? $localePrefix : $localePrefix . $path;

        return [
            'label'  => $this->translator->get($titleKey),
            'url'    => $url,
            'active' => $currentPath === $path || $currentPath === $url,
            'icon'   => $icon,
        ];
    }


}
