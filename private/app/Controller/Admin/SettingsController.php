<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Cache\CacheInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\View\View;

/**
 * Контроллер системных настроек панели администратора.
 */
final class SettingsController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private readonly View           $view,
        private readonly Session        $session,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Отображает страницу системных настроек.
     */
    public function index(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');

        $this->view
            ->setTitle('Системные настройки')
            ->setBreadcrumbs([['label' => 'Системные настройки']])
            ->setContent($this->view->fetch('admin/settings/index.tpl', [
                'admin_path'   => $adminPath,
                'success'      => $this->session->getFlash('success'),
                'error'        => $this->session->getFlash('error'),
                'cache_driver' => config('cache.driver', 'null'),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }

    /**
     * Сбрасывает кеш приложения: теги redirects, cipher_categories, ciphers и все известные ключи.
     *
     * Использует точечный сброс по тегам и именованным ключам, чтобы не затронуть сессии,
     * которые могут находиться в том же Memcached-сервере.
     */
    public function flushCache(Request $request): Response
    {
        $adminPath = config('admin.path', '/admin');

        try {
            // Теги
            $this->cache->tag('redirects')->flush();
            $this->cache->tag('cipher_categories')->flush();
            $this->cache->tag('ciphers')->flush();

            // Статические ключи
            $this->cache->delete('llms.txt.content');
            $this->cache->delete('sitemap.xml.paths');

            // Динамические ключи по всем поддерживаемым локалям
            $locales = config('locale.locales', [config('locale.locale', 'en')]);
            foreach ($locales as $locale) {
                $this->cache->delete("home:{$locale}");
                $this->cache->delete("nav_pages:{$locale}");
            }

            $this->session->flash('success', 'Кеш успешно сброшен.');
        } catch (\Throwable $e) {
            $this->session->flash('error', 'Ошибка при сбросе кеша: ' . $e->getMessage());
        }

        return new Response('', 302, ['Location' => $adminPath . '/settings']);
    }
}
