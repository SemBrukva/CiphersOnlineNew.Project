<?php

declare(strict_types=1);

namespace App\Navigation;

use App\Auth\Auth;
use App\I18n\Translator;

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
    ) {
    }

    /**
     * Собирает финальный список пунктов меню для текущего запроса.
     *
     * @param  string $currentPath  Путь запроса (для подсветки активного пункта).
     * @param  string $localePrefix Префикс локали, например '/ru' или ''.
     * @return list<array{label: string, url: string, active: bool, icon: string|null}>
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
}
