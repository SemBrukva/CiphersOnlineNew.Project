<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Обновляет Cookie Policy под текущую CMP-матрицу и планируемые рекламные теги.
 */
class UpdateCookiePolicySystemPages extends Migration
{
    /**
     * Обновляет опубликованные страницы Cookie Policy для всех поддерживаемых локалей.
     */
    public function up(): void
    {
        $this->db->transaction(function (): void {
            foreach ($this->pages() as [$language, $name, $text]) {
                $this->db->execute(
                    'UPDATE ' . Tables::SYSTEM_PAGES . ' SET name = ?, text = ?, published = 1 WHERE language = ? AND alias = ?',
                    [$name, $text, $language, 'cookie-policy']
                );
            }
        });
    }

    /**
     * Откат не восстанавливает прежний большой SQL-дамп.
     */
    public function down(): void
    {
        // Старая версия страницы хранится в private/database/dumps/system_pages.sql.
    }

    /**
     * Возвращает локализованные страницы политики cookie.
     *
     * @return array<int, array{0:string, 1:string, 2:string}>
     */
    private function pages(): array
    {
        return [
            ['en', 'Cookie Policy', <<<'HTML'
<p><strong>Last updated:</strong> 05.06.2026</p>
<p>This Cookie Policy explains how ciphersonline.com (“CiphersOnline”, “the Website”, “we”) uses cookies, local storage and similar technologies. It should be read together with our <a href="https://ciphersonline.com/privacy-policy">Privacy Policy</a> and <a href="https://ciphersonline.com/terms-of-service">Terms of Service</a>.</p>
<h2>What are cookies and similar technologies?</h2>
<p>Cookies are small files stored by your browser. Similar technologies include local storage, session storage, pixels, tags and scripts that can store or read information on your device or help us understand how the Website is used.</p>
<h2>Consent model</h2>
<p>When you first visit the Website, we ask for your choice before optional categories are enabled. Continuing to browse the Website does not mean that you consent to optional cookies or similar technologies. You can accept all, reject all, or manage categories separately. You can change your choice at any time using the “Cookie settings” link in the footer.</p>
<h2>Categories we use</h2>
<table>
<thead><tr><th>Category</th><th>Purpose</th><th>Can be disabled?</th></tr></thead>
<tbody>
<tr><td><strong>Necessary</strong></td><td>Security, sessions, CSRF protection, request handling and basic site operation.</td><td>No</td></tr>
<tr><td><strong>Preferences</strong></td><td>Saving interface choices, favorite tools and tool settings in the browser.</td><td>Yes</td></tr>
<tr><td><strong>Analytics</strong></td><td>Understanding page usage, technical performance and tool popularity.</td><td>Yes</td></tr>
<tr><td><strong>Marketing</strong></td><td>Showing advertising, ad measurement and ad personalization where permitted.</td><td>Yes</td></tr>
</tbody>
</table>
<h2>Technologies currently used or planned</h2>
<table>
<thead><tr><th>Technology</th><th>Category</th><th>Purpose</th><th>Provider</th></tr></thead>
<tbody>
<tr><td>Session cookie</td><td>Necessary</td><td>Keeps the session, login state and security functions working.</td><td>CiphersOnline</td></tr>
<tr><td>CSRF/session security data</td><td>Necessary</td><td>Protects forms and API requests from abuse.</td><td>CiphersOnline</td></tr>
<tr><td><code>ciphersonline_cookie_consent</code></td><td>Necessary</td><td>Stores your cookie choices and the consent version.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher_favorites</code></td><td>Preferences</td><td>Stores favorite tools in your browser.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher-tool:state:*</code></td><td>Preferences</td><td>Stores local settings for individual cipher and encoding tools.</td><td>CiphersOnline</td></tr>
<tr><td>Google Analytics</td><td>Analytics</td><td>Measures page views and site usage after Analytics consent.</td><td>Google</td></tr>
<tr><td>Yandex Metrica</td><td>Analytics</td><td>Measures page usage and technical analytics after Analytics consent.</td><td>Yandex</td></tr>
<tr><td>Google AdSense</td><td>Marketing</td><td>Displays ads and performs ad measurement after Marketing consent.</td><td>Google</td></tr>
<tr><td>Yandex Advertising Network (RSYA)</td><td>Marketing</td><td>Displays ads and performs ad measurement after Marketing consent.</td><td>Yandex</td></tr>
</tbody>
</table>
<h2>Google Consent Mode</h2>
<p>Where Google tags are enabled, the Website maps your choices to Google Consent Mode. Analytics consent controls <code>analytics_storage</code>. Marketing consent controls <code>ad_storage</code>, <code>ad_user_data</code> and <code>ad_personalization</code>. Preferences consent controls <code>functionality_storage</code> and <code>personalization_storage</code>. Security storage remains enabled because it is necessary.</p>
<p>For users in the European Economic Area, the United Kingdom and Switzerland, Google may require a Google-certified consent management platform integrated with the IAB Transparency and Consent Framework for AdSense advertising. We may use such a platform where required.</p>
<h2>Yandex Metrica and Yandex advertising</h2>
<p>Yandex Metrica is not initialized unless Analytics consent is granted. If Analytics is denied, the Website sets Yandex Metrica’s documented disable flag before initialization. Yandex advertising scripts are loaded only after Marketing consent.</p>
<h2>Changing or withdrawing consent</h2>
<p>You can change your cookie choices at any time through the “Cookie settings” link in the footer. If you reject Preferences, known preference keys such as favorite tools and tool settings are removed from local storage. If you withdraw Analytics or Marketing after a third-party script has already loaded, we apply the new consent state immediately and stop optional new calls where possible, but your browser may keep already loaded third-party resources until the page is refreshed.</p>
<h2>Browser controls</h2>
<p>You can also block or delete cookies in your browser settings. Blocking necessary cookies may cause account, security or tool functions to work incorrectly.</p>
<h2>Policy updates</h2>
<p>We may update this Cookie Policy when technologies, providers or legal requirements change. The updated version becomes effective when published on this page.</p>
<h2>Contact</h2>
<p>If you have questions about this Cookie Policy, contact us at <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['ru', 'Политика использования Cookie', <<<'HTML'
<p><strong>Дата последнего обновления:</strong> 05.06.2026</p>
<p>Настоящая Политика использования Cookie объясняет, как ciphersonline.com (“CiphersOnline”, “Сайт”, “мы”) использует cookie, local storage и похожие технологии. Она применяется вместе с нашей <a href="https://ciphersonline.com/ru/privacy-policy">Политикой конфиденциальности</a> и <a href="https://ciphersonline.com/ru/terms-of-service">Условиями использования</a>.</p>
<h2>Что такое cookie и похожие технологии?</h2>
<p>Cookie — это небольшие файлы, которые сохраняются браузером. Похожие технологии включают local storage, session storage, пиксели, теги и скрипты, которые могут сохранять или считывать информацию на вашем устройстве либо помогать понимать, как используется Сайт.</p>
<h2>Модель согласия</h2>
<p>При первом посещении Сайта мы просим вас сделать выбор до включения необязательных категорий. Продолжение просмотра Сайта не означает согласие на необязательные cookie или похожие технологии. Вы можете принять всё, отклонить всё или настроить категории отдельно. Изменить выбор можно в любое время по ссылке “Настройки cookie” в нижней части сайта.</p>
<h2>Категории</h2>
<table>
<thead><tr><th>Категория</th><th>Назначение</th><th>Можно отключить?</th></tr></thead>
<tbody>
<tr><td><strong>Обязательные</strong></td><td>Безопасность, сессии, CSRF-защита, обработка запросов и базовая работа сайта.</td><td>Нет</td></tr>
<tr><td><strong>Предпочтения</strong></td><td>Сохранение настроек интерфейса, избранных инструментов и параметров инструментов в браузере.</td><td>Да</td></tr>
<tr><td><strong>Аналитика</strong></td><td>Понимание использования страниц, технической производительности и популярности инструментов.</td><td>Да</td></tr>
<tr><td><strong>Маркетинг</strong></td><td>Показ рекламы, рекламные измерения и персонализация рекламы там, где это разрешено.</td><td>Да</td></tr>
</tbody>
</table>
<h2>Используемые и планируемые технологии</h2>
<table>
<thead><tr><th>Технология</th><th>Категория</th><th>Цель</th><th>Поставщик</th></tr></thead>
<tbody>
<tr><td>Session cookie</td><td>Обязательные</td><td>Поддерживает сессию, вход в аккаунт и функции безопасности.</td><td>CiphersOnline</td></tr>
<tr><td>CSRF/session security data</td><td>Обязательные</td><td>Защищает формы и API-запросы от злоупотреблений.</td><td>CiphersOnline</td></tr>
<tr><td><code>ciphersonline_cookie_consent</code></td><td>Обязательные</td><td>Сохраняет ваш выбор cookie и версию согласия.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher_favorites</code></td><td>Предпочтения</td><td>Сохраняет избранные инструменты в браузере.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher-tool:state:*</code></td><td>Предпочтения</td><td>Сохраняет локальные настройки отдельных инструментов.</td><td>CiphersOnline</td></tr>
<tr><td>Google Analytics</td><td>Аналитика</td><td>Измеряет просмотры страниц и использование сайта после согласия на аналитику.</td><td>Google</td></tr>
<tr><td>Яндекс Метрика</td><td>Аналитика</td><td>Измеряет использование страниц и техническую аналитику после согласия на аналитику.</td><td>Яндекс</td></tr>
<tr><td>Google AdSense</td><td>Маркетинг</td><td>Показывает рекламу и выполняет рекламные измерения после согласия на маркетинг.</td><td>Google</td></tr>
<tr><td>Рекламная сеть Яндекса (РСЯ)</td><td>Маркетинг</td><td>Показывает рекламу и выполняет рекламные измерения после согласия на маркетинг.</td><td>Яндекс</td></tr>
</tbody>
</table>
<h2>Google Consent Mode</h2>
<p>Если на Сайте включены теги Google, мы передаём ваш выбор в Google Consent Mode. Согласие на аналитику управляет <code>analytics_storage</code>. Согласие на маркетинг управляет <code>ad_storage</code>, <code>ad_user_data</code> и <code>ad_personalization</code>. Согласие на предпочтения управляет <code>functionality_storage</code> и <code>personalization_storage</code>. Хранилище безопасности остаётся включённым, потому что оно необходимо.</p>
<p>Для пользователей из Европейской экономической зоны, Великобритании и Швейцарии Google может требовать Google-certified CMP с интеграцией IAB Transparency and Consent Framework для рекламы AdSense. Мы можем использовать такую платформу там, где это требуется.</p>
<h2>Яндекс Метрика и реклама Яндекса</h2>
<p>Яндекс Метрика не инициализируется без согласия на аналитику. Если аналитика отклонена, Сайт до инициализации устанавливает документированный флаг отключения Метрики. Рекламные скрипты Яндекса загружаются только после согласия на маркетинг.</p>
<h2>Изменение или отзыв согласия</h2>
<p>Вы можете изменить выбор в любое время по ссылке “Настройки cookie” в нижней части сайта. Если вы отклоняете Предпочтения, известные ключи local storage, включая избранные инструменты и настройки инструментов, удаляются. Если вы отзываетe согласие на Аналитику или Маркетинг после загрузки стороннего скрипта, мы сразу применяем новое состояние согласия и прекращаем необязательные новые вызовы там, где это возможно, но браузер может сохранять уже загруженные сторонние ресурсы до обновления страницы.</p>
<h2>Настройки браузера</h2>
<p>Вы также можете блокировать или удалять cookie в настройках браузера. Блокировка обязательных cookie может привести к некорректной работе аккаунта, безопасности или инструментов.</p>
<h2>Обновления политики</h2>
<p>Мы можем обновлять эту Политику при изменении технологий, поставщиков или юридических требований. Обновлённая версия вступает в силу после публикации на этой странице.</p>
<h2>Контакты</h2>
<p>Если у вас есть вопросы по этой Политике, свяжитесь с нами по адресу <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['de', 'Cookie-Richtlinie', <<<'HTML'
<p><strong>Letzte Aktualisierung:</strong> 05.06.2026</p>
<p>Diese Cookie-Richtlinie erklärt, wie ciphersonline.com (“CiphersOnline”, “Website”, “wir”) Cookies, lokalen Speicher und ähnliche Technologien nutzt. Sie gilt zusammen mit unserer <a href="https://ciphersonline.com/de/privacy-policy">Datenschutzerklärung</a> und unseren <a href="https://ciphersonline.com/de/terms-of-service">Nutzungsbedingungen</a>.</p>
<h2>Was sind Cookies und ähnliche Technologien?</h2>
<p>Cookies sind kleine Dateien, die Ihr Browser speichert. Ähnliche Technologien umfassen Local Storage, Session Storage, Pixel, Tags und Skripte, die Informationen auf Ihrem Gerät speichern oder lesen können oder uns helfen, die Nutzung der Website zu verstehen.</p>
<h2>Einwilligungsmodell</h2>
<p>Beim ersten Besuch fragen wir nach Ihrer Auswahl, bevor optionale Kategorien aktiviert werden. Die weitere Nutzung der Website gilt nicht als Einwilligung in optionale Cookies oder ähnliche Technologien. Sie können alles akzeptieren, alles ablehnen oder Kategorien einzeln verwalten. Ihre Auswahl können Sie jederzeit über “Cookie-Einstellungen” im Footer ändern.</p>
<h2>Kategorien</h2>
<table><thead><tr><th>Kategorie</th><th>Zweck</th><th>Deaktivierbar?</th></tr></thead><tbody>
<tr><td><strong>Notwendig</strong></td><td>Sicherheit, Sitzungen, CSRF-Schutz, Anfrageverarbeitung und Kernfunktionen.</td><td>Nein</td></tr>
<tr><td><strong>Präferenzen</strong></td><td>Speicherung von Interface-Auswahl, Favoriten und Tool-Einstellungen im Browser.</td><td>Ja</td></tr>
<tr><td><strong>Analyse</strong></td><td>Messung der Seitennutzung, technischen Leistung und Tool-Beliebtheit.</td><td>Ja</td></tr>
<tr><td><strong>Marketing</strong></td><td>Anzeige von Werbung, Anzeigenmessung und Anzeigenpersonalisierung, soweit erlaubt.</td><td>Ja</td></tr>
</tbody></table>
<h2>Verwendete oder geplante Technologien</h2>
<table><thead><tr><th>Technologie</th><th>Kategorie</th><th>Zweck</th><th>Anbieter</th></tr></thead><tbody>
<tr><td>Session cookie</td><td>Notwendig</td><td>Erhält Sitzung, Login-Status und Sicherheitsfunktionen.</td><td>CiphersOnline</td></tr>
<tr><td>CSRF/session security data</td><td>Notwendig</td><td>Schützt Formulare und API-Anfragen vor Missbrauch.</td><td>CiphersOnline</td></tr>
<tr><td><code>ciphersonline_cookie_consent</code></td><td>Notwendig</td><td>Speichert Ihre Cookie-Auswahl und die Consent-Version.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher_favorites</code></td><td>Präferenzen</td><td>Speichert favorisierte Tools im Browser.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher-tool:state:*</code></td><td>Präferenzen</td><td>Speichert lokale Einstellungen einzelner Tools.</td><td>CiphersOnline</td></tr>
<tr><td>Google Analytics</td><td>Analyse</td><td>Misst Seitenaufrufe und Website-Nutzung nach Analyse-Einwilligung.</td><td>Google</td></tr>
<tr><td>Yandex Metrica</td><td>Analyse</td><td>Misst Seitennutzung und technische Analyse nach Analyse-Einwilligung.</td><td>Yandex</td></tr>
<tr><td>Google AdSense</td><td>Marketing</td><td>Zeigt Werbung und misst Anzeigen nach Marketing-Einwilligung.</td><td>Google</td></tr>
<tr><td>Yandex Advertising Network (RSYA)</td><td>Marketing</td><td>Zeigt Werbung und misst Anzeigen nach Marketing-Einwilligung.</td><td>Yandex</td></tr>
</tbody></table>
<h2>Google Consent Mode</h2>
<p>Wenn Google-Tags aktiviert sind, ordnen wir Ihre Auswahl Google Consent Mode zu. Analyse steuert <code>analytics_storage</code>. Marketing steuert <code>ad_storage</code>, <code>ad_user_data</code> und <code>ad_personalization</code>. Präferenzen steuern <code>functionality_storage</code> und <code>personalization_storage</code>. Sicherheits-Speicher bleibt aktiviert, weil er notwendig ist.</p>
<p>Für Nutzer im Europäischen Wirtschaftsraum, im Vereinigten Königreich und in der Schweiz kann Google für AdSense eine Google-zertifizierte CMP mit IAB-TCF-Integration verlangen. Wo erforderlich, können wir eine solche Plattform einsetzen.</p>
<h2>Yandex Metrica und Yandex-Werbung</h2>
<p>Yandex Metrica wird nur nach Analyse-Einwilligung initialisiert. Bei Ablehnung setzen wir vor der Initialisierung das dokumentierte Deaktivierungsflag. Yandex-Werbeskripte werden nur nach Marketing-Einwilligung geladen.</p>
<h2>Einwilligung ändern oder widerrufen</h2>
<p>Sie können Ihre Auswahl jederzeit über “Cookie-Einstellungen” im Footer ändern. Wenn Präferenzen abgelehnt werden, löschen wir bekannte lokale Präferenzschlüssel wie Favoriten und Tool-Einstellungen. Wenn Analyse oder Marketing widerrufen werden, nachdem ein Drittanbieter-Skript geladen wurde, wenden wir den neuen Status sofort an und vermeiden weitere optionale Aufrufe, soweit möglich; bereits geladene Ressourcen können bis zum Neuladen der Seite im Browser verbleiben.</p>
<h2>Browser-Einstellungen</h2>
<p>Sie können Cookies auch im Browser blockieren oder löschen. Das Blockieren notwendiger Cookies kann Konto-, Sicherheits- oder Tool-Funktionen beeinträchtigen.</p>
<h2>Aktualisierungen</h2>
<p>Wir können diese Richtlinie aktualisieren, wenn sich Technologien, Anbieter oder rechtliche Anforderungen ändern. Die neue Version gilt ab Veröffentlichung auf dieser Seite.</p>
<h2>Kontakt</h2>
<p>Fragen richten Sie bitte an <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['es', 'Política de Cookies', <<<'HTML'
<p><strong>Última actualización:</strong> 05.06.2026</p>
<p>Esta Política de Cookies explica cómo ciphersonline.com (“CiphersOnline”, “el Sitio”, “nosotros”) usa cookies, almacenamiento local y tecnologías similares. Debe leerse junto con nuestra <a href="https://ciphersonline.com/es/privacy-policy">Política de Privacidad</a> y los <a href="https://ciphersonline.com/es/terms-of-service">Términos de Servicio</a>.</p>
<h2>¿Qué son las cookies y tecnologías similares?</h2>
<p>Las cookies son pequeños archivos almacenados por el navegador. Tecnologías similares incluyen local storage, session storage, píxeles, etiquetas y scripts que pueden guardar o leer información en su dispositivo o ayudarnos a entender cómo se usa el Sitio.</p>
<h2>Modelo de consentimiento</h2>
<p>En la primera visita pedimos su elección antes de activar categorías opcionales. Seguir navegando no significa consentimiento para cookies opcionales o tecnologías similares. Puede aceptar todo, rechazar todo o gestionar categorías por separado. Puede cambiar su elección en cualquier momento desde “Configuración de cookies” en el pie de página.</p>
<h2>Categorías</h2>
<table><thead><tr><th>Categoría</th><th>Finalidad</th><th>¿Se puede desactivar?</th></tr></thead><tbody>
<tr><td><strong>Necesarias</strong></td><td>Seguridad, sesiones, protección CSRF, procesamiento de solicitudes y funcionamiento básico.</td><td>No</td></tr>
<tr><td><strong>Preferencias</strong></td><td>Guardar opciones de interfaz, favoritos y ajustes de herramientas en el navegador.</td><td>Sí</td></tr>
<tr><td><strong>Analítica</strong></td><td>Entender el uso de páginas, rendimiento técnico y popularidad de herramientas.</td><td>Sí</td></tr>
<tr><td><strong>Marketing</strong></td><td>Mostrar publicidad, medir anuncios y personalizar publicidad cuando esté permitido.</td><td>Sí</td></tr>
</tbody></table>
<h2>Tecnologías usadas o previstas</h2>
<table><thead><tr><th>Tecnología</th><th>Categoría</th><th>Finalidad</th><th>Proveedor</th></tr></thead><tbody>
<tr><td>Session cookie</td><td>Necesarias</td><td>Mantiene la sesión, el acceso y las funciones de seguridad.</td><td>CiphersOnline</td></tr>
<tr><td>CSRF/session security data</td><td>Necesarias</td><td>Protege formularios y solicitudes API contra abusos.</td><td>CiphersOnline</td></tr>
<tr><td><code>ciphersonline_cookie_consent</code></td><td>Necesarias</td><td>Guarda sus elecciones de cookies y la versión del consentimiento.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher_favorites</code></td><td>Preferencias</td><td>Guarda herramientas favoritas en el navegador.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher-tool:state:*</code></td><td>Preferencias</td><td>Guarda ajustes locales de herramientas individuales.</td><td>CiphersOnline</td></tr>
<tr><td>Google Analytics</td><td>Analítica</td><td>Mide vistas de página y uso del sitio tras el consentimiento de Analítica.</td><td>Google</td></tr>
<tr><td>Yandex Metrica</td><td>Analítica</td><td>Mide uso de páginas y analítica técnica tras el consentimiento de Analítica.</td><td>Yandex</td></tr>
<tr><td>Google AdSense</td><td>Marketing</td><td>Muestra anuncios y mide publicidad tras el consentimiento de Marketing.</td><td>Google</td></tr>
<tr><td>Yandex Advertising Network (RSYA)</td><td>Marketing</td><td>Muestra anuncios y mide publicidad tras el consentimiento de Marketing.</td><td>Yandex</td></tr>
</tbody></table>
<h2>Google Consent Mode</h2>
<p>Cuando las etiquetas de Google están habilitadas, asignamos sus elecciones a Google Consent Mode. Analítica controla <code>analytics_storage</code>. Marketing controla <code>ad_storage</code>, <code>ad_user_data</code> y <code>ad_personalization</code>. Preferencias controla <code>functionality_storage</code> y <code>personalization_storage</code>. El almacenamiento de seguridad permanece habilitado porque es necesario.</p>
<p>Para usuarios del Espacio Económico Europeo, Reino Unido y Suiza, Google puede exigir una CMP certificada por Google e integrada con IAB TCF para publicidad AdSense. Podemos usar dicha plataforma cuando sea necesario.</p>
<h2>Yandex Metrica y publicidad de Yandex</h2>
<p>Yandex Metrica no se inicializa salvo que se conceda consentimiento de Analítica. Si se rechaza, el Sitio establece el indicador documentado de desactivación antes de inicializarla. Los scripts publicitarios de Yandex se cargan solo tras el consentimiento de Marketing.</p>
<h2>Cambiar o retirar el consentimiento</h2>
<p>Puede cambiar sus elecciones en cualquier momento desde “Configuración de cookies” en el pie de página. Si rechaza Preferencias, eliminamos claves conocidas como favoritos y ajustes de herramientas. Si retira Analítica o Marketing después de que un script externo ya se haya cargado, aplicamos el nuevo estado inmediatamente y evitamos nuevas llamadas opcionales cuando sea posible; los recursos ya cargados pueden permanecer en el navegador hasta recargar la página.</p>
<h2>Controles del navegador</h2>
<p>También puede bloquear o borrar cookies en su navegador. Bloquear cookies necesarias puede afectar a cuentas, seguridad o herramientas.</p>
<h2>Actualizaciones</h2>
<p>Podemos actualizar esta Política cuando cambien tecnologías, proveedores o requisitos legales. La nueva versión entra en vigor al publicarse en esta página.</p>
<h2>Contacto</h2>
<p>Para preguntas, escríbanos a <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['fr', 'Politique de Cookies', <<<'HTML'
<p><strong>Dernière mise à jour :</strong> 05.06.2026</p>
<p>Cette Politique de Cookies explique comment ciphersonline.com (“CiphersOnline”, “le Site”, “nous”) utilise les cookies, le stockage local et les technologies similaires. Elle doit être lue avec notre <a href="https://ciphersonline.com/fr/privacy-policy">Politique de Confidentialité</a> et nos <a href="https://ciphersonline.com/fr/terms-of-service">Conditions d’utilisation</a>.</p>
<h2>Que sont les cookies et technologies similaires ?</h2>
<p>Les cookies sont de petits fichiers stockés par votre navigateur. Les technologies similaires incluent le local storage, le session storage, les pixels, tags et scripts pouvant stocker ou lire des informations sur votre appareil ou nous aider à comprendre l’utilisation du Site.</p>
<h2>Modèle de consentement</h2>
<p>Lors de votre première visite, nous demandons votre choix avant d’activer les catégories optionnelles. Continuer à naviguer ne vaut pas consentement aux cookies optionnels. Vous pouvez tout accepter, tout refuser ou gérer les catégories séparément. Vous pouvez changer votre choix à tout moment via “Paramètres des cookies” dans le pied de page.</p>
<h2>Catégories</h2>
<table><thead><tr><th>Catégorie</th><th>Finalité</th><th>Désactivable ?</th></tr></thead><tbody>
<tr><td><strong>Nécessaires</strong></td><td>Sécurité, sessions, protection CSRF, traitement des requêtes et fonctionnement de base.</td><td>Non</td></tr>
<tr><td><strong>Préférences</strong></td><td>Enregistrement des choix d’interface, outils favoris et réglages dans le navigateur.</td><td>Oui</td></tr>
<tr><td><strong>Analyse</strong></td><td>Comprendre l’usage des pages, la performance technique et la popularité des outils.</td><td>Oui</td></tr>
<tr><td><strong>Marketing</strong></td><td>Afficher des publicités, mesurer les annonces et personnaliser la publicité lorsque permis.</td><td>Oui</td></tr>
</tbody></table>
<h2>Technologies utilisées ou prévues</h2>
<table><thead><tr><th>Technologie</th><th>Catégorie</th><th>Finalité</th><th>Fournisseur</th></tr></thead><tbody>
<tr><td>Session cookie</td><td>Nécessaires</td><td>Maintient la session, la connexion et les fonctions de sécurité.</td><td>CiphersOnline</td></tr>
<tr><td>CSRF/session security data</td><td>Nécessaires</td><td>Protège les formulaires et requêtes API contre les abus.</td><td>CiphersOnline</td></tr>
<tr><td><code>ciphersonline_cookie_consent</code></td><td>Nécessaires</td><td>Enregistre vos choix de cookies et la version du consentement.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher_favorites</code></td><td>Préférences</td><td>Enregistre les outils favoris dans le navigateur.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher-tool:state:*</code></td><td>Préférences</td><td>Enregistre les réglages locaux de chaque outil.</td><td>CiphersOnline</td></tr>
<tr><td>Google Analytics</td><td>Analyse</td><td>Mesure les pages vues et l’utilisation du site après consentement Analyse.</td><td>Google</td></tr>
<tr><td>Yandex Metrica</td><td>Analyse</td><td>Mesure l’utilisation des pages et l’analyse technique après consentement Analyse.</td><td>Yandex</td></tr>
<tr><td>Google AdSense</td><td>Marketing</td><td>Affiche des annonces et mesure la publicité après consentement Marketing.</td><td>Google</td></tr>
<tr><td>Yandex Advertising Network (RSYA)</td><td>Marketing</td><td>Affiche des annonces et mesure la publicité après consentement Marketing.</td><td>Yandex</td></tr>
</tbody></table>
<h2>Google Consent Mode</h2>
<p>Lorsque les tags Google sont activés, nous associons vos choix à Google Consent Mode. Analyse contrôle <code>analytics_storage</code>. Marketing contrôle <code>ad_storage</code>, <code>ad_user_data</code> et <code>ad_personalization</code>. Préférences contrôle <code>functionality_storage</code> et <code>personalization_storage</code>. Le stockage de sécurité reste activé car il est nécessaire.</p>
<p>Pour les utilisateurs de l’Espace économique européen, du Royaume-Uni et de la Suisse, Google peut exiger une CMP certifiée par Google intégrée à l’IAB TCF pour AdSense. Nous pouvons utiliser une telle plateforme lorsque requis.</p>
<h2>Yandex Metrica et publicité Yandex</h2>
<p>Yandex Metrica n’est initialisé qu’après consentement Analyse. En cas de refus, le Site définit l’indicateur documenté de désactivation avant l’initialisation. Les scripts publicitaires Yandex ne sont chargés qu’après consentement Marketing.</p>
<h2>Modifier ou retirer le consentement</h2>
<p>Vous pouvez modifier vos choix à tout moment via “Paramètres des cookies” dans le pied de page. Si vous refusez Préférences, nous supprimons les clés connues telles que favoris et réglages d’outils. Si vous retirez Analyse ou Marketing après le chargement d’un script tiers, nous appliquons immédiatement le nouvel état et évitons les nouveaux appels optionnels lorsque possible ; les ressources déjà chargées peuvent rester dans le navigateur jusqu’au rechargement.</p>
<h2>Contrôles du navigateur</h2>
<p>Vous pouvez aussi bloquer ou supprimer les cookies dans votre navigateur. Bloquer les cookies nécessaires peut perturber les comptes, la sécurité ou les outils.</p>
<h2>Mises à jour</h2>
<p>Nous pouvons mettre à jour cette Politique lorsque les technologies, fournisseurs ou obligations changent. La nouvelle version s’applique dès sa publication.</p>
<h2>Contact</h2>
<p>Pour toute question, contactez-nous à <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['it', 'Politica sui Cookie', <<<'HTML'
<p><strong>Ultimo aggiornamento:</strong> 05.06.2026</p>
<p>Questa Politica sui Cookie spiega come ciphersonline.com (“CiphersOnline”, “il Sito”, “noi”) usa cookie, archiviazione locale e tecnologie simili. Va letta insieme alla nostra <a href="https://ciphersonline.com/it/privacy-policy">Informativa sulla Privacy</a> e ai <a href="https://ciphersonline.com/it/terms-of-service">Termini di Servizio</a>.</p>
<h2>Cosa sono cookie e tecnologie simili?</h2>
<p>I cookie sono piccoli file memorizzati dal browser. Tecnologie simili includono local storage, session storage, pixel, tag e script che possono salvare o leggere informazioni sul dispositivo o aiutarci a capire come viene usato il Sito.</p>
<h2>Modello di consenso</h2>
<p>Alla prima visita chiediamo la tua scelta prima di attivare categorie opzionali. Continuare a navigare non costituisce consenso ai cookie opzionali. Puoi accettare tutto, rifiutare tutto o gestire le categorie separatamente. Puoi modificare la scelta in qualsiasi momento tramite “Impostazioni cookie” nel footer.</p>
<h2>Categorie</h2>
<table><thead><tr><th>Categoria</th><th>Scopo</th><th>Disattivabile?</th></tr></thead><tbody>
<tr><td><strong>Necessari</strong></td><td>Sicurezza, sessioni, protezione CSRF, gestione richieste e funzioni essenziali.</td><td>No</td></tr>
<tr><td><strong>Preferenze</strong></td><td>Salvataggio di scelte interfaccia, preferiti e impostazioni strumenti nel browser.</td><td>Sì</td></tr>
<tr><td><strong>Analisi</strong></td><td>Comprendere uso delle pagine, prestazioni tecniche e popolarità degli strumenti.</td><td>Sì</td></tr>
<tr><td><strong>Marketing</strong></td><td>Mostrare pubblicità, misurare annunci e personalizzare annunci ove consentito.</td><td>Sì</td></tr>
</tbody></table>
<h2>Tecnologie usate o previste</h2>
<table><thead><tr><th>Tecnologia</th><th>Categoria</th><th>Scopo</th><th>Fornitore</th></tr></thead><tbody>
<tr><td>Session cookie</td><td>Necessari</td><td>Mantiene sessione, accesso e funzioni di sicurezza.</td><td>CiphersOnline</td></tr>
<tr><td>CSRF/session security data</td><td>Necessari</td><td>Protegge form e richieste API da abusi.</td><td>CiphersOnline</td></tr>
<tr><td><code>ciphersonline_cookie_consent</code></td><td>Necessari</td><td>Memorizza le tue scelte sui cookie e la versione del consenso.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher_favorites</code></td><td>Preferenze</td><td>Memorizza strumenti preferiti nel browser.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher-tool:state:*</code></td><td>Preferenze</td><td>Memorizza impostazioni locali dei singoli strumenti.</td><td>CiphersOnline</td></tr>
<tr><td>Google Analytics</td><td>Analisi</td><td>Misura visualizzazioni e uso del sito dopo consenso Analisi.</td><td>Google</td></tr>
<tr><td>Yandex Metrica</td><td>Analisi</td><td>Misura uso pagine e analisi tecnica dopo consenso Analisi.</td><td>Yandex</td></tr>
<tr><td>Google AdSense</td><td>Marketing</td><td>Mostra annunci e misura pubblicità dopo consenso Marketing.</td><td>Google</td></tr>
<tr><td>Yandex Advertising Network (RSYA)</td><td>Marketing</td><td>Mostra annunci e misura pubblicità dopo consenso Marketing.</td><td>Yandex</td></tr>
</tbody></table>
<h2>Google Consent Mode</h2>
<p>Quando i tag Google sono abilitati, mappiamo le tue scelte su Google Consent Mode. Analisi controlla <code>analytics_storage</code>. Marketing controlla <code>ad_storage</code>, <code>ad_user_data</code> e <code>ad_personalization</code>. Preferenze controlla <code>functionality_storage</code> e <code>personalization_storage</code>. Lo storage di sicurezza resta abilitato perché necessario.</p>
<p>Per utenti nello Spazio Economico Europeo, Regno Unito e Svizzera, Google può richiedere una CMP certificata Google integrata con IAB TCF per AdSense. Potremmo usare tale piattaforma dove richiesto.</p>
<h2>Yandex Metrica e pubblicità Yandex</h2>
<p>Yandex Metrica non viene inizializzata senza consenso Analisi. In caso di rifiuto, il Sito imposta il flag documentato di disattivazione prima dell’inizializzazione. Gli script pubblicitari Yandex sono caricati solo dopo consenso Marketing.</p>
<h2>Modifica o revoca del consenso</h2>
<p>Puoi modificare le scelte in qualsiasi momento tramite “Impostazioni cookie” nel footer. Se rifiuti Preferenze, eliminiamo chiavi note come preferiti e impostazioni strumenti. Se revochi Analisi o Marketing dopo il caricamento di uno script terzo, applichiamo subito il nuovo stato ed evitiamo nuove chiamate opzionali ove possibile; risorse già caricate possono restare nel browser fino al refresh.</p>
<h2>Controlli del browser</h2>
<p>Puoi anche bloccare o eliminare cookie dal browser. Bloccare cookie necessari può compromettere account, sicurezza o strumenti.</p>
<h2>Aggiornamenti</h2>
<p>Possiamo aggiornare questa Politica quando cambiano tecnologie, fornitori o requisiti legali. La nuova versione vale dalla pubblicazione.</p>
<h2>Contatto</h2>
<p>Per domande scrivici a <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['pt', 'Política de Cookies', <<<'HTML'
<p><strong>Última atualização:</strong> 05.06.2026</p>
<p>Esta Política de Cookies explica como ciphersonline.com (“CiphersOnline”, “o Site”, “nós”) usa cookies, armazenamento local e tecnologias semelhantes. Ela deve ser lida junto com nossa <a href="https://ciphersonline.com/pt/privacy-policy">Política de Privacidade</a> e <a href="https://ciphersonline.com/pt/terms-of-service">Termos de Serviço</a>.</p>
<h2>O que são cookies e tecnologias semelhantes?</h2>
<p>Cookies são pequenos arquivos armazenados pelo navegador. Tecnologias semelhantes incluem local storage, session storage, pixels, tags e scripts que podem salvar ou ler informações no dispositivo ou nos ajudar a entender como o Site é usado.</p>
<h2>Modelo de consentimento</h2>
<p>Na primeira visita, pedimos sua escolha antes de ativar categorias opcionais. Continuar navegando não significa consentimento para cookies opcionais. Você pode aceitar tudo, recusar tudo ou gerenciar categorias separadamente. Você pode alterar sua escolha a qualquer momento em “Configurações de cookies” no rodapé.</p>
<h2>Categorias</h2>
<table><thead><tr><th>Categoria</th><th>Finalidade</th><th>Pode desativar?</th></tr></thead><tbody>
<tr><td><strong>Necessários</strong></td><td>Segurança, sessões, proteção CSRF, tratamento de requisições e funcionamento básico.</td><td>Não</td></tr>
<tr><td><strong>Preferências</strong></td><td>Salvar escolhas da interface, favoritos e configurações de ferramentas no navegador.</td><td>Sim</td></tr>
<tr><td><strong>Analítica</strong></td><td>Entender uso das páginas, desempenho técnico e popularidade das ferramentas.</td><td>Sim</td></tr>
<tr><td><strong>Marketing</strong></td><td>Exibir publicidade, medir anúncios e personalizar anúncios quando permitido.</td><td>Sim</td></tr>
</tbody></table>
<h2>Tecnologias usadas ou planejadas</h2>
<table><thead><tr><th>Tecnologia</th><th>Categoria</th><th>Finalidade</th><th>Fornecedor</th></tr></thead><tbody>
<tr><td>Session cookie</td><td>Necessários</td><td>Mantém sessão, login e funções de segurança.</td><td>CiphersOnline</td></tr>
<tr><td>CSRF/session security data</td><td>Necessários</td><td>Protege formulários e requisições API contra abuso.</td><td>CiphersOnline</td></tr>
<tr><td><code>ciphersonline_cookie_consent</code></td><td>Necessários</td><td>Armazena suas escolhas de cookies e a versão do consentimento.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher_favorites</code></td><td>Preferências</td><td>Armazena ferramentas favoritas no navegador.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher-tool:state:*</code></td><td>Preferências</td><td>Armazena configurações locais de ferramentas individuais.</td><td>CiphersOnline</td></tr>
<tr><td>Google Analytics</td><td>Analítica</td><td>Mede visualizações e uso do site após consentimento de Analítica.</td><td>Google</td></tr>
<tr><td>Yandex Metrica</td><td>Analítica</td><td>Mede uso das páginas e analítica técnica após consentimento de Analítica.</td><td>Yandex</td></tr>
<tr><td>Google AdSense</td><td>Marketing</td><td>Exibe anúncios e mede publicidade após consentimento de Marketing.</td><td>Google</td></tr>
<tr><td>Yandex Advertising Network (RSYA)</td><td>Marketing</td><td>Exibe anúncios e mede publicidade após consentimento de Marketing.</td><td>Yandex</td></tr>
</tbody></table>
<h2>Google Consent Mode</h2>
<p>Quando tags do Google estão habilitadas, mapeamos suas escolhas para o Google Consent Mode. Analítica controla <code>analytics_storage</code>. Marketing controla <code>ad_storage</code>, <code>ad_user_data</code> e <code>ad_personalization</code>. Preferências controla <code>functionality_storage</code> e <code>personalization_storage</code>. O armazenamento de segurança permanece habilitado porque é necessário.</p>
<p>Para usuários no Espaço Econômico Europeu, Reino Unido e Suíça, o Google pode exigir uma CMP certificada pelo Google integrada ao IAB TCF para AdSense. Podemos usar essa plataforma quando necessário.</p>
<h2>Yandex Metrica e publicidade Yandex</h2>
<p>Yandex Metrica não é inicializado sem consentimento de Analítica. Se recusado, o Site define o sinalizador documentado de desativação antes da inicialização. Scripts publicitários Yandex são carregados apenas após consentimento de Marketing.</p>
<h2>Alterar ou retirar consentimento</h2>
<p>Você pode alterar suas escolhas a qualquer momento em “Configurações de cookies” no rodapé. Se recusar Preferências, removemos chaves conhecidas como favoritos e configurações de ferramentas. Se retirar Analítica ou Marketing após um script terceiro já ter carregado, aplicamos o novo estado imediatamente e evitamos novas chamadas opcionais quando possível; recursos já carregados podem permanecer no navegador até recarregar a página.</p>
<h2>Controles do navegador</h2>
<p>Você também pode bloquear ou excluir cookies no navegador. Bloquear cookies necessários pode afetar conta, segurança ou ferramentas.</p>
<h2>Atualizações</h2>
<p>Podemos atualizar esta Política quando tecnologias, fornecedores ou requisitos legais mudarem. A nova versão entra em vigor quando publicada.</p>
<h2>Contato</h2>
<p>Em caso de dúvidas, escreva para <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['tr', 'Çerez Politikası', <<<'HTML'
<p><strong>Son güncelleme:</strong> 05.06.2026</p>
<p>Bu Çerez Politikası, ciphersonline.com’un (“CiphersOnline”, “Web Sitesi”, “biz”) çerezleri, yerel depolamayı ve benzer teknolojileri nasıl kullandığını açıklar. <a href="https://ciphersonline.com/tr/privacy-policy">Gizlilik Politikası</a> ve <a href="https://ciphersonline.com/tr/terms-of-service">Hizmet Şartları</a> ile birlikte okunmalıdır.</p>
<h2>Çerezler ve benzer teknolojiler nedir?</h2>
<p>Çerezler tarayıcınız tarafından saklanan küçük dosyalardır. Benzer teknolojiler local storage, session storage, pikseller, etiketler ve cihazınızda bilgi saklayabilen veya okuyabilen ya da Web Sitesi’nin nasıl kullanıldığını anlamamıza yardımcı olan scriptleri içerir.</p>
<h2>Onay modeli</h2>
<p>İlk ziyaretinizde isteğe bağlı kategoriler etkinleştirilmeden önce seçiminizi isteriz. Web Sitesi’nde gezinmeye devam etmek isteğe bağlı çerezlere onay verdiğiniz anlamına gelmez. Tümünü kabul edebilir, tümünü reddedebilir veya kategorileri ayrı ayrı yönetebilirsiniz. Seçiminizi footer’daki “Çerez ayarları” bağlantısından istediğiniz zaman değiştirebilirsiniz.</p>
<h2>Kategoriler</h2>
<table><thead><tr><th>Kategori</th><th>Amaç</th><th>Kapatılabilir mi?</th></tr></thead><tbody>
<tr><td><strong>Gerekli</strong></td><td>Güvenlik, oturumlar, CSRF koruması, istek işleme ve temel site çalışması.</td><td>Hayır</td></tr>
<tr><td><strong>Tercihler</strong></td><td>Arayüz seçimleri, favori araçlar ve araç ayarlarını tarayıcıda saklama.</td><td>Evet</td></tr>
<tr><td><strong>Analitik</strong></td><td>Sayfa kullanımı, teknik performans ve araç popülerliğini anlama.</td><td>Evet</td></tr>
<tr><td><strong>Pazarlama</strong></td><td>Reklam gösterimi, reklam ölçümü ve izin verilen yerde reklam kişiselleştirme.</td><td>Evet</td></tr>
</tbody></table>
<h2>Kullanılan veya planlanan teknolojiler</h2>
<table><thead><tr><th>Teknoloji</th><th>Kategori</th><th>Amaç</th><th>Sağlayıcı</th></tr></thead><tbody>
<tr><td>Session cookie</td><td>Gerekli</td><td>Oturum, giriş durumu ve güvenlik işlevlerini sürdürür.</td><td>CiphersOnline</td></tr>
<tr><td>CSRF/session security data</td><td>Gerekli</td><td>Formları ve API isteklerini kötüye kullanıma karşı korur.</td><td>CiphersOnline</td></tr>
<tr><td><code>ciphersonline_cookie_consent</code></td><td>Gerekli</td><td>Çerez seçimlerinizi ve onay sürümünü saklar.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher_favorites</code></td><td>Tercihler</td><td>Favori araçları tarayıcıda saklar.</td><td>CiphersOnline</td></tr>
<tr><td><code>cipher-tool:state:*</code></td><td>Tercihler</td><td>Tek tek araçların yerel ayarlarını saklar.</td><td>CiphersOnline</td></tr>
<tr><td>Google Analytics</td><td>Analitik</td><td>Analitik onayından sonra sayfa görüntülemelerini ve site kullanımını ölçer.</td><td>Google</td></tr>
<tr><td>Yandex Metrica</td><td>Analitik</td><td>Analitik onayından sonra sayfa kullanımını ve teknik analitiği ölçer.</td><td>Yandex</td></tr>
<tr><td>Google AdSense</td><td>Pazarlama</td><td>Pazarlama onayından sonra reklam gösterir ve reklam ölçümü yapar.</td><td>Google</td></tr>
<tr><td>Yandex Advertising Network (RSYA)</td><td>Pazarlama</td><td>Pazarlama onayından sonra reklam gösterir ve reklam ölçümü yapar.</td><td>Yandex</td></tr>
</tbody></table>
<h2>Google Consent Mode</h2>
<p>Google etiketleri etkinse seçimlerinizi Google Consent Mode’a eşleriz. Analitik <code>analytics_storage</code> değerini kontrol eder. Pazarlama <code>ad_storage</code>, <code>ad_user_data</code> ve <code>ad_personalization</code> değerlerini kontrol eder. Tercihler <code>functionality_storage</code> ve <code>personalization_storage</code> değerlerini kontrol eder. Güvenlik depolaması gerekli olduğu için açık kalır.</p>
<p>Avrupa Ekonomik Alanı, Birleşik Krallık ve İsviçre’deki kullanıcılar için Google, AdSense reklamları için IAB TCF ile entegre Google sertifikalı CMP gerektirebilir. Gerekli olduğunda böyle bir platform kullanabiliriz.</p>
<h2>Yandex Metrica ve Yandex reklamları</h2>
<p>Yandex Metrica, Analitik onayı verilmedikçe başlatılmaz. Analitik reddedilirse Web Sitesi, başlatmadan önce belgelenmiş devre dışı bırakma bayrağını ayarlar. Yandex reklam scriptleri yalnızca Pazarlama onayından sonra yüklenir.</p>
<h2>Onayı değiştirme veya geri çekme</h2>
<p>Seçimlerinizi footer’daki “Çerez ayarları” bağlantısından istediğiniz zaman değiştirebilirsiniz. Tercihleri reddederseniz favoriler ve araç ayarları gibi bilinen yerel depolama anahtarları silinir. Analitik veya Pazarlama onayını üçüncü taraf script yüklendikten sonra geri çekerseniz yeni durumu hemen uygular ve mümkün olduğunca yeni isteğe bağlı çağrıları durdururuz; önceden yüklenen kaynaklar sayfa yenilenene kadar tarayıcıda kalabilir.</p>
<h2>Tarayıcı kontrolleri</h2>
<p>Tarayıcı ayarlarından çerezleri engelleyebilir veya silebilirsiniz. Gerekli çerezleri engellemek hesap, güvenlik veya araç işlevlerini bozabilir.</p>
<h2>Güncellemeler</h2>
<p>Teknolojiler, sağlayıcılar veya yasal gereklilikler değiştiğinde bu Politikayı güncelleyebiliriz. Yeni sürüm bu sayfada yayımlandığında yürürlüğe girer.</p>
<h2>İletişim</h2>
<p>Sorularınız için <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a> adresinden bize ulaşın.</p>
HTML],
        ];
    }
}
