<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Cache\CacheInterface;
use App\Debug\DebugInfo;
use App\Debug\TranslationTracker;
use App\Geo\GeoIpService;
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
        private GeoIpService        $geoIpService,
        private CacheInterface      $cache,
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
        $this->view->share('nav_pages', $this->loadPages($locale));
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
        $this->view->share('tracking_config', $this->trackingConfig($request->ip()));
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
    private function loadPages(string $language): array
    {
        try {
            /** @var array<int, array<string, mixed>> $result */
            $result = $this->cache->remember("nav_pages:{$language}", 3600, fn () => $this->pages->listPublishedForNavigation($language));
            return $result;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Возвращает безопасную конфигурацию публичных tracking-тегов для шаблонов.
     *
     * Реклама выбирается по стране IP: для RU/BY/KZ — РСЯ, для всех остальных — Adsense.
     * Если геолокация недоступна — по умолчанию показывается Adsense.
     *
     * @return array<string, mixed>
     */
    private function trackingConfig(string $ip): array
    {
        $rsyaCountries = (array) config('geoip.rsya_countries', ['RU', 'BY', 'KZ']);
        $countryCode   = $this->geoIpService->getCountryCode($ip);
        $isRsyaUser    = $countryCode !== null && in_array($countryCode, $rsyaCountries, true);

        $slots = $isRsyaUser
            ? (array) config('tracking.yandex.rsya_slots', [])
            : (array) config('tracking.google.adsense_slots', []);

        return [
            'ga_measurement_id'       => (string) config('tracking.google.analytics_id', ''),
            'adsense_client_id'       => $isRsyaUser ? '' : (string) config('tracking.google.adsense_client_id', ''),
            'yandex_metrica_id'       => (string) config('tracking.yandex.metrica_id', ''),
            'yandex_metrica_webvisor' => (bool) config('tracking.yandex.metrica_webvisor', false),
            'yandex_rsya_enabled'     => $isRsyaUser && (bool) config('tracking.yandex.rsya_enabled', false),
            'ad_network'              => $isRsyaUser ? 'rsya' : 'adsense',
            'ad_slots'                => [
                'after_hero'        => (string) ($slots['after_hero'] ?? ''),
                'after_first_block' => (string) ($slots['after_first_block'] ?? ''),
                'after_faq'         => (string) ($slots['after_faq'] ?? ''),
            ],
        ];
    }

    /**
     * Возвращает метаданные локалей: флаг, название на родном языке и og_locale (language_TERRITORY).
     *
     * @return array<string, array{flag: string, name: string, og_locale: string}>
     */
    private function buildLocaleMeta(): array
    {
        return [
            'en' => ['flag' => '🇬🇧', 'name' => 'English',    'og_locale' => 'en_US'],
            'ru' => ['flag' => '🇷🇺', 'name' => 'Русский',    'og_locale' => 'ru_RU'],
            'de' => ['flag' => '🇩🇪', 'name' => 'Deutsch',    'og_locale' => 'de_DE'],
            'es' => ['flag' => '🇪🇸', 'name' => 'Español',    'og_locale' => 'es_ES'],
            'fr' => ['flag' => '🇫🇷', 'name' => 'Français',   'og_locale' => 'fr_FR'],
            'it' => ['flag' => '🇮🇹', 'name' => 'Italiano',   'og_locale' => 'it_IT'],
            'pt' => ['flag' => '🇵🇹', 'name' => 'Português',  'og_locale' => 'pt_PT'],
            'tr' => ['flag' => '🇹🇷', 'name' => 'Türkçe',    'og_locale' => 'tr_TR'],
            'pl' => ['flag' => '🇵🇱', 'name' => 'Polski',     'og_locale' => 'pl_PL'],
            'nl' => ['flag' => '🇳🇱', 'name' => 'Nederlands', 'og_locale' => 'nl_NL'],
            'uk' => ['flag' => '🇺🇦', 'name' => 'Українська', 'og_locale' => 'uk_UA'],
            'zh' => ['flag' => '🇨🇳', 'name' => '中文',        'og_locale' => 'zh_CN'],
            'ja' => ['flag' => '🇯🇵', 'name' => '日本語',      'og_locale' => 'ja_JP'],
            'ko' => ['flag' => '🇰🇷', 'name' => '한국어',      'og_locale' => 'ko_KR'],
            'ar' => ['flag' => '🇸🇦', 'name' => 'العربية',    'og_locale' => 'ar_SA'],
            'sv' => ['flag' => '🇸🇪', 'name' => 'Svenska',    'og_locale' => 'sv_SE'],
        ];
    }
}
