<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Debug\DebugInfo;
use App\Debug\TranslationTracker;
use App\Http\CspNonce;
use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\I18n\Translator;
use App\Navigation\NavigationBuilder;
use App\Repository\SystemPageRepository;
use App\View\View;

/**
 * Middleware для передачи глобальных данных во все шаблоны.
 *
 * Должен запускаться после SessionMiddleware и LocaleMiddleware.
 *
 * Порядок работы:
 *  1. Шарит общие переменные в View (csrf, auth, nav, t и др.).
 *  2. Запускает следующий обработчик (контроллер рендерит шаблон).
 *  3. Если debug разрешён — инжектирует debug-панель перед </body>.
 *     Это гарантирует что панель видит актуальные SQL, кеш и Response.
 */
final readonly class ShareViewDataMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт экземпляр middleware.
     */
    public function __construct(
        private View                $view,
        private Auth                $auth,
        private SystemPageRepository $pages,
        private Translator          $translator,
        private Session             $session,
        private DebugInfo           $debugInfo,
        private TranslationTracker  $translationTracker,
        private NavigationBuilder   $navigationBuilder,
        private CspNonce            $cspNonce,
    ) {
    }

    /**
     * Передаёт глобальные переменные в View, запускает цепочку и инжектирует debug-панель.
     *
     * Доступные в каждом шаблоне переменные: app_name, csrf_token, auth_user,
     * nav_main, nav_pages, current_path, current_year, t, multilang,
     * current_locale, locale_prefix, available_locales, locale_urls.
     */
    public function process(Request $request, callable $next): Response
    {
        $authUser      = $this->auth->user();
        $isAuth        = $authUser !== null;
        $adminIds      = config('admin.ids', []);
        $isAdmin       = $isAuth && in_array($this->auth->id(), $adminIds, true);
        $locale        = $this->translator->getLocale();
        $defaultLocale = $this->translator->getDefaultLocale();
        $locales       = $this->translator->getLocales();
        $multilang     = $this->translator->isMultilang();

        $prefix = ($multilang && $locale !== $defaultLocale) ? '/' . $locale : '';

        // Загружаем переводы в трекер и шарим его вместо голого массива.
        // Smarty обращается к {$t.KEY} через ArrayAccess — трекер фиксирует каждое обращение.
        $this->translationTracker->load($this->translator->all());

        $this->view->share('csp_nonce', $this->cspNonce->get());
        $this->view->share('app_name', config('app.name', 'Application'));
        $this->view->share('app_url', rtrim((string) config('app.url', ''), '/'));
        $this->view->share('csrf_token', $this->session->csrfToken());
        $this->view->share('auth_user', $authUser);
        $this->view->share('nav_main', $this->navigationBuilder->build($request->path(), $prefix));
        $this->view->share('nav_pages', $this->loadPages());
        $this->view->share('current_path', $request->path());
        $this->view->share('current_year', (int) date('Y'));
        $this->view->share('t', $this->translationTracker);
        $this->view->share('multilang', $multilang);
        $this->view->share('current_locale', $locale);
        $this->view->share('default_locale', $defaultLocale);
        $this->view->share('locale_prefix', $prefix);
        $this->view->share('registration_enabled', (bool) config('app.user_registration', false));
        $this->view->share('is_admin', $isAdmin);
        $this->view->share('admin_path', config('admin.path', '/admin'));
        $this->view->share('available_locales', $locales);
        $this->view->share('locale_meta', $this->buildLocaleMeta());
        $this->view->share('locale_urls', $isAuth ? [] : $this->buildLocaleUrls($request->path(), $defaultLocale, $locales));

        // Выполняем контроллер — шаблон рендерится здесь, все SQL и кеш-запросы происходят внутри.
        $response = $next($request);

        return $this->injectDebugPanel($request, $response);
    }

    /**
     * Инжектирует debug-панель в HTML-ответ перед </body>.
     * Для не-HTML ответов (JSON, редиректы без тела) — возвращает оригинальный ответ.
     */
    private function injectDebugPanel(Request $request, Response $response): Response
    {
        $content = $response->getContent();

        if (!str_contains($content, '</body>')) {
            return $response;
        }

        $debugData = $this->debugInfo->build($request, $response);

        if ($debugData === null) {
            return $response;
        }

        $panel   = $this->view->fetch('partials/debug_info.tpl', ['debug_info' => $debugData]);
        $content = str_replace('</body>', $panel . '</body>', $content);

        return new Response($content, $response->getStatusCode(), $response->getHeaders());
    }

    /**
     * Строит карту locale → URL для переключателя языков (только для гостей).
     *
     * @param string[] $locales
     * @return array<string, string>
     */
    private function buildLocaleUrls(string $path, string $defaultLocale, array $locales): array
    {
        $urls = [];
        foreach ($locales as $lang) {
            $urls[$lang] = ($lang !== $defaultLocale ? '/' . $lang : '') . $path;
        }

        return $urls;
    }

    /**
     * Загружает опубликованные системные страницы для нижнего меню.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadPages(): array
    {
        try {
            return $this->pages->listPublishedForNavigation();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Возвращает метаданные локалей: флаг + название на родном языке.
     *
     * @return array<string, array{flag: string, name: string}>
     */
    private function buildLocaleMeta(): array
    {
        return [
            'en' => ['flag' => '🇬🇧', 'name' => 'English'],
            'ru' => ['flag' => '🇷🇺', 'name' => 'Русский'],
            'de' => ['flag' => '🇩🇪', 'name' => 'Deutsch'],
            'es' => ['flag' => '🇪🇸', 'name' => 'Español'],
            'fr' => ['flag' => '🇫🇷', 'name' => 'Français'],
            'it' => ['flag' => '🇮🇹', 'name' => 'Italiano'],
            'pt' => ['flag' => '🇵🇹', 'name' => 'Português'],
            'tr' => ['flag' => '🇹🇷', 'name' => 'Türkçe'],
            'pl' => ['flag' => '🇵🇱', 'name' => 'Polski'],
            'nl' => ['flag' => '🇳🇱', 'name' => 'Nederlands'],
            'uk' => ['flag' => '🇺🇦', 'name' => 'Українська'],
            'zh' => ['flag' => '🇨🇳', 'name' => '中文'],
            'ja' => ['flag' => '🇯🇵', 'name' => '日本語'],
            'ko' => ['flag' => '🇰🇷', 'name' => '한국어'],
            'ar' => ['flag' => '🇸🇦', 'name' => 'العربية'],
            'sv' => ['flag' => '🇸🇪', 'name' => 'Svenska'],
        ];
    }
}
