<?php

declare(strict_types=1);

namespace App\Navigation;

use App\Auth\Auth;
use App\Cache\CacheInterface;
use App\I18n\Translator;
use App\Repository\CipherCategoryRepository;

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
        private readonly Auth       $auth,
        private readonly Translator $translator,
        private readonly CipherCategoryRepository $categories,
        private readonly CacheInterface $cache,
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
        $items = [];

        foreach (config('navigation.main', []) as $item) {
            $items[] = $this->makeItem(
                $item['title_key'],
                $item['url'],
                $item['icon'] ?? null,
                $currentPath,
                $localePrefix,
            );
        }

        if ($this->auth->check()) {
            // Приватный маршрут — языковой префикс не используется
            $items[] = $this->makeItem('MENU_CABINET', '/cabinet', null, $currentPath, '');
        }

        $toolsChildren = $this->buildToolsChildren($currentPath, $localePrefix);

        if ($toolsChildren !== []) {
            $items[] = [
                'label' => $this->translator->get('MENU_TOOLS'),
                'url' => '#',
                'active' => (bool) array_filter($toolsChildren, static fn (array $child): bool => $child['active']),
                'icon' => null,
                'children' => $toolsChildren,
            ];
        }

        return $items;
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

    /**
     * Собирает дочерние пункты меню «Инструменты».
     *
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    private function buildToolsChildren(string $currentPath, string $localePrefix): array
    {
        $language = $this->translator->getLocale();
        $defaultLanguage = $this->translator->getDefaultLocale();
        $cacheTtl = (int) config('cache.ttl', 3600);

        $categories = $this->cache->tag('cipher_categories')->remember(
            'nav.tools.categories.' . $language . '.' . $defaultLanguage,
            $cacheTtl,
            fn (): array => $this->categories->listPublishedForNavigation($language, $defaultLanguage)
        );

        $items = [];

        foreach ($categories as $category) {
            $alias = (string) ($category['alias'] ?? '');
            $name = trim((string) ($category['name'] ?? ''));

            if ($alias === '') {
                continue;
            }

            $path = '/' . $alias;
            $url = $localePrefix !== '' ? $localePrefix . $path : $path;

            $items[] = [
                'label' => $name !== '' ? $name : $alias,
                'url' => $url,
                'active' => $currentPath === $path,
            ];
        }

        return $items;
    }
}
