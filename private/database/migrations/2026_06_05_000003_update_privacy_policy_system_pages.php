<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Обновляет Privacy Policy под текущую архитектуру сайта и tracking/CMP.
 */
class UpdatePrivacyPolicySystemPages extends Migration
{
    /**
     * Обновляет опубликованные страницы Privacy Policy для всех поддерживаемых локалей.
     */
    public function up(): void
    {
        $this->db->transaction(function (): void {
            foreach ($this->pages() as [$language, $name, $text]) {
                $this->db->execute(
                    'UPDATE ' . Tables::SYSTEM_PAGES . ' SET name = ?, text = ?, published = 1 WHERE language = ? AND alias = ?',
                    [$name, $text, $language, 'privacy-policy']
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
     * Возвращает локализованные страницы политики конфиденциальности.
     *
     * @return array<int, array{0:string, 1:string, 2:string}>
     */
    private function pages(): array
    {
        return [
            ['en', 'Privacy Policy', <<<'HTML'
<p><strong>Last updated:</strong> 05.06.2026</p>
<p>This Privacy Policy explains how ciphersonline.com (“CiphersOnline”, “the Website”, “we”) collects, uses and protects personal data. It should be read together with our <a href="https://ciphersonline.com/terms-of-service">Terms of Service</a> and <a href="https://ciphersonline.com/cookie-policy">Cookie Policy</a>.</p>
<h2>Who we are</h2>
<p>Data controller: BRUKVA PR<br>Email: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a><br>Address: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>What data we process</h2>
<ul>
<li><strong>Account data:</strong> name, email, password hash, language preference, account identifiers and session information.</li>
<li><strong>Contact data:</strong> name, email address and message content when you contact us.</li>
<li><strong>Technical data:</strong> IP address, user agent, request URL, timestamps, security events, rate-limit data and server logs.</li>
<li><strong>Tool data:</strong> text or settings submitted to tools. Some tools run in your browser; API-based tools send input to the server for processing. Do not enter passwords, private keys, production tokens, secrets or confidential information.</li>
<li><strong>Preference data:</strong> favorites and tool settings stored in browser local storage if you allow Preferences.</li>
<li><strong>Analytics and advertising data:</strong> only if you allow the relevant cookie categories, Google Analytics, Google AdSense, Yandex Metrica and Yandex Advertising Network may process usage or advertising data.</li>
</ul>
<h2>Why we process data and legal bases</h2>
<table><thead><tr><th>Purpose</th><th>Legal basis</th></tr></thead><tbody>
<tr><td>Provide the Website, accounts, tools and support.</td><td>Contract performance or steps requested before using the service.</td></tr>
<tr><td>Protect security, prevent abuse, debug errors and enforce limits.</td><td>Legitimate interests and legal obligations where applicable.</td></tr>
<tr><td>Respond to contact requests.</td><td>Legitimate interests or your request before a contract.</td></tr>
<tr><td>Save preferences in the browser.</td><td>Your consent.</td></tr>
<tr><td>Analytics, advertising and ad personalization.</td><td>Your consent where required.</td></tr>
<tr><td>Comply with legal requests and accounting or administrative duties.</td><td>Legal obligations.</td></tr>
</tbody></table>
<h2>How tool input is handled</h2>
<p>Where possible, tools process data in the browser. Some tools use the Website API and send input to the server to calculate a result. We do not intentionally use tool input for advertising profiling. Tool input may still appear in transient request processing, security logs or error diagnostics where technically necessary.</p>
<h2>Cookies and consent</h2>
<p>We use a cookie consent banner with Necessary, Preferences, Analytics and Marketing categories. Optional analytics and advertising technologies are not enabled until the corresponding consent is granted. More details are available in the <a href="https://ciphersonline.com/cookie-policy">Cookie Policy</a>.</p>
<h2>Recipients and processors</h2>
<p>We may use hosting, email, logging, analytics, advertising and infrastructure providers. Planned or possible providers include Google services (Google Analytics and Google AdSense) and Yandex services (Yandex Metrica and Yandex Advertising Network), only according to your consent and configuration. We may disclose data if required by law or to protect our rights, users or the Website.</p>
<h2>International transfers</h2>
<p>We are based in Serbia and may use service providers in other countries. Where data protection laws require safeguards for international transfers, we rely on appropriate measures such as contractual safeguards, adequacy mechanisms or provider compliance frameworks where available.</p>
<h2>Retention</h2>
<ul>
<li>Account data is kept while the account exists and for a reasonable period after deletion if needed for security, legal or dispute purposes.</li>
<li>Contact messages are kept as long as needed to handle the request and maintain business records.</li>
<li>Server logs and security records are kept for a limited period needed for security, debugging and abuse prevention.</li>
<li>Cookie consent and preference data remain in your browser until you change settings, delete browser storage or the consent version changes.</li>
</ul>
<h2>Your rights</h2>
<p>Depending on your location, you may have rights to access, correct, delete, restrict or object to processing, portability, withdraw consent and complain to a data protection authority. Withdrawing consent does not affect processing that happened before withdrawal.</p>
<h2>Children</h2>
<p>The Website is not intended for children under the age required by applicable law to use online services without parental consent. If you believe a child provided us personal data, contact us.</p>
<h2>Security</h2>
<p>We use reasonable technical and organizational measures to protect data. No internet service can guarantee absolute security.</p>
<h2>Changes</h2>
<p>We may update this Privacy Policy when the Website, providers or legal requirements change. The updated version becomes effective when published on this page.</p>
<h2>Contact</h2>
<p>To exercise privacy rights or ask questions, contact <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['ru', 'Политика конфиденциальности', <<<'HTML'
<p><strong>Дата последнего обновления:</strong> 05.06.2026</p>
<p>Настоящая Политика конфиденциальности объясняет, как ciphersonline.com (“CiphersOnline”, “Сайт”, “мы”) собирает, использует и защищает персональные данные. Она применяется вместе с <a href="https://ciphersonline.com/ru/terms-of-service">Условиями использования</a> и <a href="https://ciphersonline.com/ru/cookie-policy">Политикой использования Cookie</a>.</p>
<h2>Кто мы</h2>
<p>Оператор данных: BRUKVA PR<br>Email: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a><br>Адрес: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Какие данные мы обрабатываем</h2>
<ul>
<li><strong>Данные аккаунта:</strong> имя, email, хеш пароля, языковые настройки, идентификаторы аккаунта и данные сессии.</li>
<li><strong>Контактные данные:</strong> имя, email и текст сообщения, если вы связываетесь с нами.</li>
<li><strong>Технические данные:</strong> IP-адрес, user agent, URL запроса, время запросов, события безопасности, rate-limit данные и серверные логи.</li>
<li><strong>Данные инструментов:</strong> текст и настройки, отправленные в инструменты. Часть инструментов работает в браузере, а API-инструменты отправляют ввод на сервер для расчёта результата. Не вводите пароли, приватные ключи, production-токены, секреты или конфиденциальную информацию.</li>
<li><strong>Данные предпочтений:</strong> избранные инструменты и настройки инструментов в local storage браузера, если вы разрешили категорию “Предпочтения”.</li>
<li><strong>Аналитика и реклама:</strong> только при согласии на соответствующие категории Google Analytics, Google AdSense, Яндекс Метрика и РСЯ могут обрабатывать данные использования или рекламы.</li>
</ul>
<h2>Цели и правовые основания</h2>
<table><thead><tr><th>Цель</th><th>Основание</th></tr></thead><tbody>
<tr><td>Предоставление Сайта, аккаунтов, инструментов и поддержки.</td><td>Исполнение договора или действия по вашему запросу до использования сервиса.</td></tr>
<tr><td>Безопасность, предотвращение злоупотреблений, отладка ошибок и лимиты.</td><td>Законные интересы и юридические обязанности, где применимо.</td></tr>
<tr><td>Ответы на обращения.</td><td>Законные интересы или ваш запрос до заключения договора.</td></tr>
<tr><td>Сохранение предпочтений в браузере.</td><td>Ваше согласие.</td></tr>
<tr><td>Аналитика, реклама и персонализация рекламы.</td><td>Ваше согласие там, где оно требуется.</td></tr>
<tr><td>Исполнение юридических, административных или учётных обязанностей.</td><td>Юридические обязанности.</td></tr>
</tbody></table>
<h2>Как обрабатывается ввод в инструменты</h2>
<p>Где возможно, инструменты обрабатывают данные в браузере. Некоторые инструменты используют API Сайта и отправляют ввод на сервер для расчёта результата. Мы не используем ввод в инструменты для рекламного профилирования. При этом ввод может временно участвовать в обработке запроса, событиях безопасности или диагностике ошибок, если это технически необходимо.</p>
<h2>Cookie и согласие</h2>
<p>Мы используем баннер согласия с категориями “Обязательные”, “Предпочтения”, “Аналитика” и “Маркетинг”. Необязательные аналитические и рекламные технологии не включаются до согласия на соответствующую категорию. Подробнее см. <a href="https://ciphersonline.com/ru/cookie-policy">Политику использования Cookie</a>.</p>
<h2>Получатели и обработчики</h2>
<p>Мы можем использовать провайдеров хостинга, email, логирования, аналитики, рекламы и инфраструктуры. Планируемые или возможные провайдеры включают сервисы Google (Google Analytics и Google AdSense) и Яндекса (Яндекс Метрика и РСЯ), только согласно вашему согласию и настройкам. Мы можем раскрывать данные, если этого требует закон или защита наших прав, пользователей или Сайта.</p>
<h2>Международные передачи</h2>
<p>Мы находимся в Сербии и можем использовать провайдеров в других странах. Если применимое законодательство требует гарантий для международной передачи данных, мы используем подходящие меры: договорные гарантии, механизмы адекватности или compliance-механизмы провайдеров, где они доступны.</p>
<h2>Сроки хранения</h2>
<ul>
<li>Данные аккаунта хранятся, пока аккаунт существует, и разумный период после удаления, если это нужно для безопасности, закона или споров.</li>
<li>Контактные сообщения хранятся столько, сколько нужно для обработки обращения и деловых записей.</li>
<li>Серверные логи и записи безопасности хранятся ограниченный период, необходимый для безопасности, отладки и предотвращения злоупотреблений.</li>
<li>Согласие на cookie и данные предпочтений хранятся в браузере, пока вы не измените настройки, не удалите storage или пока версия согласия не изменится.</li>
</ul>
<h2>Ваши права</h2>
<p>В зависимости от вашего местонахождения вы можете иметь право на доступ, исправление, удаление, ограничение или возражение против обработки, переносимость данных, отзыв согласия и жалобу в орган по защите данных. Отзыв согласия не влияет на обработку, выполненную до отзыва.</p>
<h2>Дети</h2>
<p>Сайт не предназначен для детей младше возраста, с которого применимое право разрешает пользоваться онлайн-сервисами без согласия родителей. Если вы считаете, что ребёнок передал нам персональные данные, свяжитесь с нами.</p>
<h2>Безопасность</h2>
<p>Мы используем разумные технические и организационные меры защиты. Ни один интернет-сервис не может гарантировать абсолютную безопасность.</p>
<h2>Изменения</h2>
<p>Мы можем обновлять эту Политику при изменении Сайта, провайдеров или юридических требований. Обновлённая версия вступает в силу после публикации на этой странице.</p>
<h2>Контакты</h2>
<p>Чтобы реализовать права или задать вопрос, напишите на <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['de', 'Datenschutzerklärung', <<<'HTML'
<p><strong>Letzte Aktualisierung:</strong> 05.06.2026</p>
<p>Diese Datenschutzerklärung erklärt, wie ciphersonline.com (“CiphersOnline”, “Website”, “wir”) personenbezogene Daten erhebt, nutzt und schützt. Sie gilt zusammen mit unseren <a href="https://ciphersonline.com/de/terms-of-service">Nutzungsbedingungen</a> und der <a href="https://ciphersonline.com/de/cookie-policy">Cookie-Richtlinie</a>.</p>
<h2>Wer wir sind</h2><p>Verantwortlicher: BRUKVA PR<br>E-Mail: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a><br>Adresse: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Welche Daten wir verarbeiten</h2>
<ul><li><strong>Kontodaten:</strong> Name, E-Mail, Passwort-Hash, Sprache, Konto-IDs und Sitzungsdaten.</li><li><strong>Kontaktdaten:</strong> Name, E-Mail und Nachricht.</li><li><strong>Technische Daten:</strong> IP-Adresse, User-Agent, URL, Zeitstempel, Sicherheitsereignisse, Rate-Limit-Daten und Serverlogs.</li><li><strong>Tool-Daten:</strong> Texte und Einstellungen, die an Tools übergeben werden. Einige Tools laufen im Browser, API-Tools senden Eingaben an den Server. Geben Sie keine Passwörter, privaten Schlüssel, Tokens, Geheimnisse oder vertraulichen Informationen ein.</li><li><strong>Präferenzen:</strong> Favoriten und Tool-Einstellungen im Browser, wenn Sie Präferenzen erlauben.</li><li><strong>Analyse und Werbung:</strong> Google Analytics, Google AdSense, Yandex Metrica und Yandex Advertising Network können nur nach passender Einwilligung Nutzungs- oder Werbedaten verarbeiten.</li></ul>
<h2>Zwecke und Rechtsgrundlagen</h2>
<table><thead><tr><th>Zweck</th><th>Rechtsgrundlage</th></tr></thead><tbody><tr><td>Website, Konten, Tools und Support bereitstellen.</td><td>Vertragserfüllung oder vorvertragliche Anfrage.</td></tr><tr><td>Sicherheit, Missbrauchsverhinderung, Fehleranalyse und Limits.</td><td>Berechtigte Interessen und ggf. rechtliche Pflichten.</td></tr><tr><td>Kontaktanfragen beantworten.</td><td>Berechtigte Interessen oder Ihre Anfrage.</td></tr><tr><td>Präferenzen im Browser speichern.</td><td>Einwilligung.</td></tr><tr><td>Analyse, Werbung und Personalisierung.</td><td>Einwilligung, soweit erforderlich.</td></tr><tr><td>Rechtliche und administrative Pflichten erfüllen.</td><td>Rechtliche Pflichten.</td></tr></tbody></table>
<h2>Tool-Eingaben</h2><p>Wo möglich, werden Eingaben im Browser verarbeitet. Einige Tools nutzen die Website-API und senden Eingaben zur Ergebnisberechnung an den Server. Wir verwenden Tool-Eingaben nicht absichtlich für Werbeprofile; sie können jedoch technisch notwendig in Anfrageverarbeitung, Sicherheitslogs oder Fehlerdiagnose erscheinen.</p>
<h2>Cookies und Einwilligung</h2><p>Unser Banner verwendet die Kategorien Notwendig, Präferenzen, Analyse und Marketing. Optionale Analyse- und Werbetechnologien werden erst nach entsprechender Einwilligung aktiviert. Details stehen in der <a href="https://ciphersonline.com/de/cookie-policy">Cookie-Richtlinie</a>.</p>
<h2>Empfänger, Übermittlungen und Speicherung</h2><p>Wir können Hosting-, E-Mail-, Logging-, Analyse-, Werbe- und Infrastruktur-Anbieter einsetzen, darunter Google und Yandex entsprechend Ihrer Einwilligung. Wir sind in Serbien ansässig und können Anbieter in anderen Ländern nutzen; erforderliche Schutzmaßnahmen werden eingesetzt, soweit anwendbar.</p><p>Kontodaten bleiben während des Bestehens des Kontos gespeichert; Kontaktanfragen, Logs und Sicherheitsdaten werden nur so lange gespeichert, wie es für Bearbeitung, Sicherheit, Nachweise oder gesetzliche Pflichten erforderlich ist. Cookie- und Präferenzdaten bleiben in Ihrem Browser, bis Sie sie ändern oder löschen.</p>
<h2>Ihre Rechte</h2><p>Je nach Standort können Sie Zugriff, Berichtigung, Löschung, Einschränkung, Widerspruch, Datenübertragbarkeit, Widerruf der Einwilligung und Beschwerde bei einer Datenschutzbehörde verlangen.</p>
<h2>Kinder, Sicherheit und Änderungen</h2><p>Die Website richtet sich nicht an Kinder, die nach geltendem Recht elterliche Zustimmung benötigen. Wir nutzen angemessene Sicherheitsmaßnahmen, können aber keine absolute Sicherheit garantieren. Diese Erklärung kann aktualisiert werden; die neue Version gilt ab Veröffentlichung.</p>
<h2>Kontakt</h2><p>Für Fragen oder Rechteausübung: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['es', 'Política de Privacidad', <<<'HTML'
<p><strong>Última actualización:</strong> 05.06.2026</p>
<p>Esta Política de Privacidad explica cómo ciphersonline.com (“CiphersOnline”, “el Sitio”, “nosotros”) recopila, usa y protege datos personales. Debe leerse junto con los <a href="https://ciphersonline.com/es/terms-of-service">Términos de Servicio</a> y la <a href="https://ciphersonline.com/es/cookie-policy">Política de Cookies</a>.</p>
<h2>Quiénes somos</h2><p>Responsable del tratamiento: BRUKVA PR<br>Email: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a><br>Dirección: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Datos que tratamos</h2><ul><li><strong>Cuenta:</strong> nombre, email, hash de contraseña, idioma, identificadores y sesión.</li><li><strong>Contacto:</strong> nombre, email y mensaje.</li><li><strong>Técnicos:</strong> IP, user agent, URL, marcas de tiempo, eventos de seguridad, rate limits y logs.</li><li><strong>Herramientas:</strong> textos y ajustes enviados a herramientas. Algunas funcionan en el navegador; las basadas en API envían la entrada al servidor. No introduzca contraseñas, claves privadas, tokens, secretos ni información confidencial.</li><li><strong>Preferencias:</strong> favoritos y ajustes en local storage si permite Preferencias.</li><li><strong>Analítica y publicidad:</strong> Google Analytics, Google AdSense, Yandex Metrica y Yandex Advertising Network solo según su consentimiento.</li></ul>
<h2>Finalidades y bases legales</h2><table><thead><tr><th>Finalidad</th><th>Base legal</th></tr></thead><tbody><tr><td>Proporcionar Sitio, cuentas, herramientas y soporte.</td><td>Ejecución contractual o solicitud previa.</td></tr><tr><td>Seguridad, prevención de abuso, depuración y límites.</td><td>Intereses legítimos y obligaciones legales.</td></tr><tr><td>Responder contactos.</td><td>Intereses legítimos o su solicitud.</td></tr><tr><td>Guardar preferencias.</td><td>Consentimiento.</td></tr><tr><td>Analítica, publicidad y personalización.</td><td>Consentimiento cuando sea requerido.</td></tr><tr><td>Cumplir obligaciones legales o administrativas.</td><td>Obligación legal.</td></tr></tbody></table>
<h2>Entrada en herramientas</h2><p>Cuando es posible, los datos se procesan en el navegador. Algunas herramientas usan la API del Sitio y envían la entrada al servidor para calcular resultados. No usamos intencionadamente esta entrada para perfiles publicitarios; puede aparecer temporalmente en procesamiento técnico, seguridad o diagnóstico.</p>
<h2>Cookies y consentimiento</h2><p>El banner usa categorías Necesarias, Preferencias, Analítica y Marketing. Las tecnologías opcionales no se activan hasta el consentimiento correspondiente. Más detalles en la <a href="https://ciphersonline.com/es/cookie-policy">Política de Cookies</a>.</p>
<h2>Destinatarios, transferencias y conservación</h2><p>Podemos usar proveedores de hosting, email, logs, analítica, publicidad e infraestructura, incluidos Google y Yandex según su consentimiento. Estamos en Serbia y podemos usar proveedores en otros países con salvaguardas apropiadas cuando sean necesarias.</p><p>Los datos de cuenta se conservan mientras exista la cuenta; mensajes, logs y seguridad se conservan solo el tiempo necesario para gestión, seguridad, pruebas o ley. El consentimiento y preferencias permanecen en el navegador hasta cambiarlos o borrarlos.</p>
<h2>Sus derechos</h2><p>Según su ubicación, puede tener derechos de acceso, rectificación, supresión, limitación, oposición, portabilidad, retirada del consentimiento y reclamación ante una autoridad.</p>
<h2>Menores, seguridad y cambios</h2><p>El Sitio no está dirigido a menores que requieran consentimiento parental por ley. Usamos medidas razonables de seguridad, sin garantía absoluta. Esta Política puede actualizarse desde su publicación.</p>
<h2>Contacto</h2><p>Para preguntas o derechos: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['fr', 'Politique de Confidentialité', <<<'HTML'
<p><strong>Dernière mise à jour :</strong> 05.06.2026</p>
<p>Cette Politique de Confidentialité explique comment ciphersonline.com (“CiphersOnline”, “le Site”, “nous”) collecte, utilise et protège les données personnelles. Elle doit être lue avec les <a href="https://ciphersonline.com/fr/terms-of-service">Conditions d’utilisation</a> et la <a href="https://ciphersonline.com/fr/cookie-policy">Politique de Cookies</a>.</p>
<h2>Qui nous sommes</h2><p>Responsable du traitement : BRUKVA PR<br>Email : <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a><br>Adresse : MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Données traitées</h2><ul><li><strong>Compte :</strong> nom, email, hash du mot de passe, langue, identifiants et session.</li><li><strong>Contact :</strong> nom, email et message.</li><li><strong>Techniques :</strong> IP, user agent, URL, horodatage, événements de sécurité, limites et journaux.</li><li><strong>Outils :</strong> textes et réglages soumis. Certains outils fonctionnent dans le navigateur ; les outils API envoient l’entrée au serveur. N’entrez pas mots de passe, clés privées, tokens, secrets ou informations confidentielles.</li><li><strong>Préférences :</strong> favoris et réglages dans le navigateur si vous autorisez Préférences.</li><li><strong>Analyse et publicité :</strong> Google Analytics, Google AdSense, Yandex Metrica et Yandex Advertising Network uniquement selon votre consentement.</li></ul>
<h2>Finalités et bases légales</h2><table><thead><tr><th>Finalité</th><th>Base légale</th></tr></thead><tbody><tr><td>Fournir le Site, comptes, outils et support.</td><td>Exécution contractuelle ou demande préalable.</td></tr><tr><td>Sécurité, prévention des abus, diagnostic et limites.</td><td>Intérêts légitimes et obligations légales.</td></tr><tr><td>Répondre aux demandes.</td><td>Intérêts légitimes ou votre demande.</td></tr><tr><td>Enregistrer les préférences.</td><td>Consentement.</td></tr><tr><td>Analyse, publicité et personnalisation.</td><td>Consentement si requis.</td></tr><tr><td>Respect des obligations légales ou administratives.</td><td>Obligation légale.</td></tr></tbody></table>
<h2>Saisie dans les outils</h2><p>Lorsque possible, les données sont traitées dans le navigateur. Certains outils utilisent l’API du Site et envoient l’entrée au serveur. Nous n’utilisons pas volontairement ces entrées pour le profilage publicitaire ; elles peuvent apparaître temporairement dans le traitement technique, la sécurité ou le diagnostic.</p>
<h2>Cookies et consentement</h2><p>Le bandeau utilise les catégories Nécessaires, Préférences, Analyse et Marketing. Les technologies optionnelles ne sont activées qu’après consentement. Voir la <a href="https://ciphersonline.com/fr/cookie-policy">Politique de Cookies</a>.</p>
<h2>Destinataires, transferts et conservation</h2><p>Nous pouvons utiliser des prestataires d’hébergement, email, logs, analyse, publicité et infrastructure, dont Google et Yandex selon votre consentement. Basés en Serbie, nous pouvons utiliser des prestataires dans d’autres pays avec garanties appropriées si nécessaire.</p><p>Les données de compte sont conservées tant que le compte existe ; messages, logs et données de sécurité seulement le temps nécessaire. Consentement et préférences restent dans votre navigateur jusqu’à modification ou suppression.</p>
<h2>Vos droits</h2><p>Selon votre localisation, vous pouvez demander accès, rectification, suppression, limitation, opposition, portabilité, retrait du consentement et réclamation auprès d’une autorité.</p>
<h2>Enfants, sécurité et changements</h2><p>Le Site ne vise pas les enfants nécessitant un consentement parental. Nous appliquons des mesures raisonnables, sans garantie absolue. Cette Politique peut être mise à jour dès publication.</p>
<h2>Contact</h2><p>Questions ou droits : <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['it', 'Informativa sulla Privacy', <<<'HTML'
<p><strong>Ultimo aggiornamento:</strong> 05.06.2026</p>
<p>Questa Informativa spiega come ciphersonline.com (“CiphersOnline”, “il Sito”, “noi”) raccoglie, usa e protegge dati personali. Va letta con i <a href="https://ciphersonline.com/it/terms-of-service">Termini di Servizio</a> e la <a href="https://ciphersonline.com/it/cookie-policy">Politica sui Cookie</a>.</p>
<h2>Chi siamo</h2><p>Titolare: BRUKVA PR<br>Email: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a><br>Indirizzo: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Dati trattati</h2><ul><li><strong>Account:</strong> nome, email, hash password, lingua, ID e sessione.</li><li><strong>Contatto:</strong> nome, email e messaggio.</li><li><strong>Tecnici:</strong> IP, user agent, URL, timestamp, eventi sicurezza, rate limit e log.</li><li><strong>Strumenti:</strong> testi e impostazioni inviati. Alcuni strumenti funzionano nel browser; quelli API inviano input al server. Non inserire password, chiavi private, token, segreti o informazioni riservate.</li><li><strong>Preferenze:</strong> preferiti e impostazioni nel browser se consenti Preferenze.</li><li><strong>Analisi e pubblicità:</strong> Google Analytics, Google AdSense, Yandex Metrica e Yandex Advertising Network solo secondo il consenso.</li></ul>
<h2>Finalità e basi giuridiche</h2><table><thead><tr><th>Finalità</th><th>Base</th></tr></thead><tbody><tr><td>Fornire Sito, account, strumenti e supporto.</td><td>Contratto o richiesta precontrattuale.</td></tr><tr><td>Sicurezza, prevenzione abusi, debug e limiti.</td><td>Interessi legittimi e obblighi legali.</td></tr><tr><td>Rispondere ai contatti.</td><td>Interessi legittimi o tua richiesta.</td></tr><tr><td>Salvare preferenze.</td><td>Consenso.</td></tr><tr><td>Analisi, pubblicità e personalizzazione.</td><td>Consenso se richiesto.</td></tr><tr><td>Obblighi legali o amministrativi.</td><td>Obbligo legale.</td></tr></tbody></table>
<h2>Input degli strumenti</h2><p>Quando possibile, i dati sono elaborati nel browser. Alcuni strumenti usano l’API e inviano input al server. Non usiamo intenzionalmente tali input per profilazione pubblicitaria; possono comparire temporaneamente in elaborazione tecnica, sicurezza o diagnostica.</p>
<h2>Cookie e consenso</h2><p>Il banner usa Necessari, Preferenze, Analisi e Marketing. Le tecnologie opzionali si attivano solo dopo consenso. Vedi la <a href="https://ciphersonline.com/it/cookie-policy">Politica sui Cookie</a>.</p>
<h2>Destinatari, trasferimenti e conservazione</h2><p>Possiamo usare provider di hosting, email, log, analisi, pubblicità e infrastruttura, inclusi Google e Yandex secondo consenso. Siamo in Serbia e possiamo usare provider in altri paesi con garanzie adeguate se necessarie.</p><p>I dati account restano finché l’account esiste; messaggi, log e sicurezza solo per il tempo necessario. Consenso e preferenze restano nel browser finché modificati o cancellati.</p>
<h2>Diritti</h2><p>Secondo la tua posizione puoi richiedere accesso, rettifica, cancellazione, limitazione, opposizione, portabilità, revoca del consenso e reclamo a un’autorità.</p>
<h2>Minori, sicurezza e modifiche</h2><p>Il Sito non è destinato a minori che richiedono consenso parentale. Usiamo misure ragionevoli, senza garanzia assoluta. Questa Informativa può essere aggiornata dalla pubblicazione.</p>
<h2>Contatto</h2><p>Domande o diritti: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['pt', 'Política de Privacidade', <<<'HTML'
<p><strong>Última atualização:</strong> 05.06.2026</p>
<p>Esta Política explica como ciphersonline.com (“CiphersOnline”, “o Site”, “nós”) coleta, usa e protege dados pessoais. Deve ser lida com os <a href="https://ciphersonline.com/pt/terms-of-service">Termos de Serviço</a> e a <a href="https://ciphersonline.com/pt/cookie-policy">Política de Cookies</a>.</p>
<h2>Quem somos</h2><p>Controlador: BRUKVA PR<br>Email: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a><br>Endereço: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Dados tratados</h2><ul><li><strong>Conta:</strong> nome, email, hash de senha, idioma, IDs e sessão.</li><li><strong>Contato:</strong> nome, email e mensagem.</li><li><strong>Técnicos:</strong> IP, user agent, URL, horários, eventos de segurança, rate limit e logs.</li><li><strong>Ferramentas:</strong> textos e configurações enviados. Algumas funcionam no navegador; ferramentas API enviam entrada ao servidor. Não insira senhas, chaves privadas, tokens, segredos ou informações confidenciais.</li><li><strong>Preferências:</strong> favoritos e configurações no navegador se você permitir Preferências.</li><li><strong>Analítica e publicidade:</strong> Google Analytics, Google AdSense, Yandex Metrica e Yandex Advertising Network apenas conforme consentimento.</li></ul>
<h2>Finalidades e bases legais</h2><table><thead><tr><th>Finalidade</th><th>Base</th></tr></thead><tbody><tr><td>Fornecer Site, contas, ferramentas e suporte.</td><td>Contrato ou solicitação pré-contratual.</td></tr><tr><td>Segurança, prevenção de abuso, depuração e limites.</td><td>Interesses legítimos e obrigações legais.</td></tr><tr><td>Responder contatos.</td><td>Interesses legítimos ou sua solicitação.</td></tr><tr><td>Salvar preferências.</td><td>Consentimento.</td></tr><tr><td>Analítica, publicidade e personalização.</td><td>Consentimento quando exigido.</td></tr><tr><td>Cumprir obrigações legais ou administrativas.</td><td>Obrigação legal.</td></tr></tbody></table>
<h2>Entrada nas ferramentas</h2><p>Quando possível, os dados são processados no navegador. Algumas ferramentas usam a API e enviam entrada ao servidor. Não usamos intencionalmente essa entrada para perfil publicitário; ela pode aparecer temporariamente em processamento técnico, segurança ou diagnóstico.</p>
<h2>Cookies e consentimento</h2><p>O banner usa Necessários, Preferências, Analítica e Marketing. Tecnologias opcionais só ativam após consentimento. Veja a <a href="https://ciphersonline.com/pt/cookie-policy">Política de Cookies</a>.</p>
<h2>Destinatários, transferências e retenção</h2><p>Podemos usar provedores de hospedagem, email, logs, analítica, publicidade e infraestrutura, incluindo Google e Yandex conforme consentimento. Estamos na Sérvia e podemos usar provedores em outros países com salvaguardas apropriadas quando necessário.</p><p>Dados de conta ficam enquanto a conta existir; mensagens, logs e segurança apenas pelo tempo necessário. Consentimento e preferências ficam no navegador até alteração ou exclusão.</p>
<h2>Seus direitos</h2><p>Dependendo da localização, você pode solicitar acesso, correção, exclusão, limitação, oposição, portabilidade, retirada de consentimento e reclamação a uma autoridade.</p>
<h2>Crianças, segurança e mudanças</h2><p>O Site não se destina a crianças que precisem de consentimento parental. Usamos medidas razoáveis, sem garantia absoluta. Esta Política pode ser atualizada após publicação.</p>
<h2>Contato</h2><p>Dúvidas ou direitos: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
            ['tr', 'Gizlilik Politikası', <<<'HTML'
<p><strong>Son güncelleme:</strong> 05.06.2026</p>
<p>Bu Politika, ciphersonline.com’un (“CiphersOnline”, “Web Sitesi”, “biz”) kişisel verileri nasıl topladığını, kullandığını ve koruduğunu açıklar. <a href="https://ciphersonline.com/tr/terms-of-service">Hizmet Şartları</a> ve <a href="https://ciphersonline.com/tr/cookie-policy">Çerez Politikası</a> ile birlikte okunmalıdır.</p>
<h2>Biz kimiz</h2><p>Veri sorumlusu: BRUKVA PR<br>Email: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a><br>Adres: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>İşlediğimiz veriler</h2><ul><li><strong>Hesap:</strong> ad, email, parola hash’i, dil, hesap kimlikleri ve oturum.</li><li><strong>İletişim:</strong> ad, email ve mesaj.</li><li><strong>Teknik:</strong> IP, user agent, URL, zaman damgaları, güvenlik olayları, rate-limit ve loglar.</li><li><strong>Araçlar:</strong> araçlara gönderilen metin ve ayarlar. Bazıları tarayıcıda çalışır; API araçları girdiyi sunucuya gönderir. Parola, özel anahtar, token, sır veya gizli bilgi girmeyin.</li><li><strong>Tercihler:</strong> izin verirseniz favoriler ve ayarlar tarayıcıda saklanır.</li><li><strong>Analitik ve reklam:</strong> Google Analytics, Google AdSense, Yandex Metrica ve Yandex Advertising Network yalnızca onaya göre veri işleyebilir.</li></ul>
<h2>Amaçlar ve hukuki dayanaklar</h2><table><thead><tr><th>Amaç</th><th>Dayanak</th></tr></thead><tbody><tr><td>Site, hesaplar, araçlar ve destek sağlamak.</td><td>Sözleşme veya talebiniz.</td></tr><tr><td>Güvenlik, kötüye kullanım önleme, hata ayıklama ve limitler.</td><td>Meşru menfaatler ve yasal yükümlülükler.</td></tr><tr><td>İletişim taleplerini yanıtlamak.</td><td>Meşru menfaatler veya talebiniz.</td></tr><tr><td>Tercihleri kaydetmek.</td><td>Onay.</td></tr><tr><td>Analitik, reklam ve kişiselleştirme.</td><td>Gerektiğinde onay.</td></tr><tr><td>Yasal veya idari yükümlülükler.</td><td>Yasal yükümlülük.</td></tr></tbody></table>
<h2>Araç girdileri</h2><p>Mümkün olduğunda veriler tarayıcıda işlenir. Bazı araçlar API kullanır ve girdiyi sunucuya gönderir. Bu girdileri reklam profillemesi için bilerek kullanmayız; teknik işlem, güvenlik veya tanılama sırasında geçici olarak görünebilir.</p>
<h2>Çerezler ve onay</h2><p>Banner Gerekli, Tercihler, Analitik ve Pazarlama kategorilerini kullanır. İsteğe bağlı teknolojiler ilgili onaydan sonra etkinleşir. Ayrıntılar <a href="https://ciphersonline.com/tr/cookie-policy">Çerez Politikası</a> içindedir.</p>
<h2>Alıcılar, aktarımlar ve saklama</h2><p>Barındırma, email, log, analitik, reklam ve altyapı sağlayıcıları kullanabiliriz; Google ve Yandex buna onayınıza göre dahildir. Sırbistan’dayız ve gerekirse uygun güvencelerle başka ülkelerde sağlayıcılar kullanabiliriz.</p><p>Hesap verileri hesap varken saklanır; mesajlar, loglar ve güvenlik kayıtları sadece gerekli süre tutulur. Onay ve tercihler tarayıcıda siz değiştirene veya silene kadar kalır.</p>
<h2>Haklarınız</h2><p>Konumunuza göre erişim, düzeltme, silme, kısıtlama, itiraz, taşınabilirlik, onayı geri çekme ve denetim makamına şikayet haklarınız olabilir.</p>
<h2>Çocuklar, güvenlik ve değişiklikler</h2><p>Web Sitesi ebeveyn izni gerektiren çocuklara yönelik değildir. Makul güvenlik önlemleri kullanırız, ancak mutlak güvenlik garanti edilemez. Bu Politika yayımlandığında güncellenebilir.</p>
<h2>İletişim</h2><p>Sorular veya haklar için: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML],
        ];
    }
}
