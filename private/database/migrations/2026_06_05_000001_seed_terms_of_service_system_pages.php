<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет локализованные условия использования сайта.
 */
class SeedTermsOfServiceSystemPages extends Migration
{
    /**
     * Создаёт или обновляет страницу условий использования для всех поддерживаемых локалей.
     */
    public function up(): void
    {
        $driver = (string) config('database.default', 'sqlite');

        $sql = $driver === 'sqlite'
            ? 'INSERT INTO ' . Tables::SYSTEM_PAGES . ' (language, alias, name, text, published)
                VALUES (?, ?, ?, ?, ?)
                ON CONFLICT(language, alias) DO UPDATE SET
                    name = excluded.name,
                    text = excluded.text,
                    published = excluded.published'
            : 'INSERT INTO ' . Tables::SYSTEM_PAGES . ' (language, alias, name, text, published)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    text = VALUES(text),
                    published = VALUES(published)';

        $this->db->transaction(function () use ($sql): void {
            foreach ($this->pages() as $page) {
                $this->db->execute($sql, $page);
            }
        });
    }

    /**
     * Удаляет добавленные страницы условий использования.
     */
    public function down(): void
    {
        $this->db->transaction(function (): void {
            foreach (array_keys($this->pages()) as $language) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::SYSTEM_PAGES . ' WHERE language = ? AND alias = ?',
                    [$language, 'terms-of-service']
                );
            }
        });
    }

    /**
     * Возвращает данные системных страниц.
     *
     * @return array<string, array{0:string, 1:string, 2:string, 3:string, 4:int}>
     */
    private function pages(): array
    {
        return [
            'en' => [
                'en',
                'terms-of-service',
                'Terms of Service',
                <<<'HTML'
<p><strong>Last updated:</strong> 05.06.2026</p>
<p>These Terms of Service govern your access to and use of ciphersonline.com (the “Website”, “CiphersOnline”, “we”, “us”). By accessing or using the Website, you agree to these Terms. If you do not agree, please do not use the Website.</p>
<h2>Who we are</h2>
<p>The Website is operated by BRUKVA PR. Contact: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>. Address: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>What the Website provides</h2>
<p>CiphersOnline provides educational and utility tools for classical ciphers, encodings, decoding, and related learning materials. The Website is provided for informational, educational, debugging, and convenience purposes only.</p>
<h2>No professional or security advice</h2>
<p>The Website does not provide legal, cybersecurity, cryptographic, compliance, or other professional advice. Classical ciphers and encoding tools shown on the Website may be insecure for real-world protection of information. You are responsible for evaluating whether any output is suitable for your own use.</p>
<h2>User input and sensitive data</h2>
<p>Do not enter passwords, private keys, secrets, personal data, confidential business information, production tokens, or any information that you are not allowed to process. Some tools may process data in your browser, while others may send input to the Website server or API for processing.</p>
<h2>Accounts</h2>
<p>If you create an account, you must provide accurate information, keep your credentials secure, and notify us if you believe your account has been compromised. We may suspend or terminate accounts that violate these Terms or create risk for the Website or other users.</p>
<h2>Acceptable use</h2>
<p>You agree not to misuse the Website, including by attempting unauthorized access, disrupting the service, scraping or overloading the Website, bypassing rate limits, uploading or submitting unlawful content, or using the Website to harm others or violate applicable law.</p>
<h2>Intellectual property</h2>
<p>The Website, its design, texts, software, and other materials are owned by us or our licensors and are protected by applicable laws. You may use the Website for personal or internal business purposes, but you may not copy, resell, or reproduce substantial parts of the Website without permission.</p>
<h2>Third-party services and links</h2>
<p>The Website may include links to third-party resources or use third-party services. We are not responsible for third-party websites, services, content, policies, or practices.</p>
<h2>Availability and changes</h2>
<p>We may update, suspend, limit, or discontinue any part of the Website at any time. We do not guarantee that the Website will always be available, error-free, or compatible with every device or browser.</p>
<h2>Disclaimer and limitation of liability</h2>
<p>The Website is provided “as is” and “as available”. To the fullest extent permitted by law, we disclaim warranties and are not liable for indirect, incidental, special, consequential, or punitive damages, loss of data, loss of profits, or damages caused by misuse of the Website. Nothing in these Terms limits rights that cannot be limited under mandatory consumer protection laws.</p>
<h2>Privacy</h2>
<p>Our processing of personal data is described in the <a href="https://ciphersonline.com/privacy-policy">Privacy Policy</a>. Our use of cookies and similar technologies is described in the <a href="https://ciphersonline.com/cookie-policy">Cookie Policy</a>.</p>
<h2>Governing law</h2>
<p>These Terms are governed by the laws of Serbia, unless mandatory laws of your country of residence provide otherwise. Consumer protection rights that apply to you by law remain unaffected.</p>
<h2>Changes to these Terms</h2>
<p>We may update these Terms from time to time. The updated version becomes effective when published on the Website. If changes are material, we may provide additional notice where appropriate.</p>
<h2>Contact</h2>
<p>If you have questions about these Terms, contact us at <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML,
                1,
            ],
            'ru' => [
                'ru',
                'terms-of-service',
                'Условия использования',
                <<<'HTML'
<p><strong>Дата последнего обновления:</strong> 05.06.2026</p>
<p>Настоящие Условия использования регулируют доступ к сайту ciphersonline.com (“Сайт”, “CiphersOnline”, “мы”) и его использование. Используя Сайт, вы соглашаетесь с настоящими Условиями. Если вы не согласны с ними, пожалуйста, не используйте Сайт.</p>
<h2>Кто мы</h2>
<p>Сайт управляется BRUKVA PR. Контактный адрес: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>. Адрес: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Что предоставляет Сайт</h2>
<p>CiphersOnline предоставляет образовательные и прикладные инструменты для классических шифров, кодировок, декодирования и связанных учебных материалов. Сайт предназначен для информационных, образовательных, отладочных и вспомогательных целей.</p>
<h2>Не является профессиональной консультацией</h2>
<p>Сайт не предоставляет юридические, кибербезопасностные, криптографические, комплаенс- или иные профессиональные консультации. Классические шифры и методы кодирования, представленные на Сайте, могут быть небезопасны для реальной защиты информации. Вы самостоятельно оцениваете пригодность результатов для своих задач.</p>
<h2>Пользовательский ввод и чувствительные данные</h2>
<p>Не вводите пароли, приватные ключи, секреты, персональные данные, конфиденциальную деловую информацию, production-токены или любые сведения, которые вы не вправе обрабатывать. Некоторые инструменты могут обрабатывать данные в вашем браузере, а другие могут отправлять введённые данные на сервер или API Сайта для обработки.</p>
<h2>Аккаунты</h2>
<p>Если вы создаёте аккаунт, вы обязуетесь указывать достоверные данные, хранить учётные данные в безопасности и уведомить нас, если считаете, что аккаунт был скомпрометирован. Мы можем приостановить или прекратить доступ к аккаунтам, нарушающим настоящие Условия или создающим риск для Сайта или других пользователей.</p>
<h2>Допустимое использование</h2>
<p>Вы обязуетесь не злоупотреблять Сайтом, включая попытки несанкционированного доступа, нарушение работы сервиса, scraping или перегрузку Сайта, обход ограничений частоты запросов, отправку незаконного контента или использование Сайта для причинения вреда другим лицам либо нарушения применимого законодательства.</p>
<h2>Интеллектуальная собственность</h2>
<p>Сайт, его дизайн, тексты, программный код и другие материалы принадлежат нам или нашим лицензиарам и защищены применимым законодательством. Вы можете использовать Сайт для личных или внутренних деловых целей, но не вправе копировать, перепродавать или воспроизводить существенные части Сайта без разрешения.</p>
<h2>Сторонние сервисы и ссылки</h2>
<p>Сайт может содержать ссылки на сторонние ресурсы или использовать сторонние сервисы. Мы не отвечаем за сторонние сайты, сервисы, контент, политики или практики.</p>
<h2>Доступность и изменения</h2>
<p>Мы можем в любое время обновлять, приостанавливать, ограничивать или прекращать работу любой части Сайта. Мы не гарантируем, что Сайт всегда будет доступен, будет работать без ошибок или будет совместим с любым устройством и браузером.</p>
<h2>Отказ от гарантий и ограничение ответственности</h2>
<p>Сайт предоставляется “как есть” и “по мере доступности”. В максимальной степени, разрешённой законом, мы отказываемся от гарантий и не несём ответственности за косвенные, случайные, специальные, последующие или штрафные убытки, потерю данных, упущенную выгоду или ущерб, вызванный неправильным использованием Сайта. Ничто в настоящих Условиях не ограничивает права, которые не могут быть ограничены обязательными нормами о защите прав потребителей.</p>
<h2>Конфиденциальность</h2>
<p>Обработка персональных данных описана в <a href="https://ciphersonline.com/ru/privacy-policy">Политике конфиденциальности</a>. Использование cookie и похожих технологий описано в <a href="https://ciphersonline.com/ru/cookie-policy">Политике использования Cookie</a>.</p>
<h2>Применимое право</h2>
<p>Настоящие Условия регулируются правом Сербии, если обязательные нормы страны вашего проживания не предусматривают иное. Ваши права потребителя, предоставленные применимым законом, сохраняются.</p>
<h2>Изменения условий</h2>
<p>Мы можем время от времени обновлять настоящие Условия. Обновлённая версия вступает в силу с момента публикации на Сайте. При существенных изменениях мы можем предоставить дополнительное уведомление, если это уместно.</p>
<h2>Контакты</h2>
<p>Если у вас есть вопросы по настоящим Условиям, свяжитесь с нами по адресу <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML,
                1,
            ],
            'de' => [
                'de',
                'terms-of-service',
                'Nutzungsbedingungen',
                <<<'HTML'
<p><strong>Letzte Aktualisierung:</strong> 05.06.2026</p>
<p>Diese Nutzungsbedingungen regeln Ihren Zugriff auf ciphersonline.com (die “Website”, “CiphersOnline”, “wir”) und deren Nutzung. Durch die Nutzung der Website stimmen Sie diesen Bedingungen zu. Wenn Sie nicht zustimmen, nutzen Sie die Website bitte nicht.</p>
<h2>Wer wir sind</h2>
<p>Die Website wird von BRUKVA PR betrieben. Kontakt: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>. Adresse: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Leistungsumfang</h2>
<p>CiphersOnline stellt Bildungs- und Hilfswerkzeuge für klassische Chiffren, Kodierungen, Dekodierungen und verwandte Lernmaterialien bereit. Die Website dient ausschließlich Informations-, Bildungs-, Debugging- und Komfortzwecken.</p>
<h2>Keine professionelle Beratung</h2>
<p>Die Website bietet keine Rechts-, Cybersicherheits-, kryptografische, Compliance- oder sonstige professionelle Beratung. Klassische Chiffren und Kodierungswerkzeuge auf der Website können für den realen Schutz von Informationen unsicher sein. Sie sind selbst dafür verantwortlich, zu prüfen, ob Ergebnisse für Ihre Zwecke geeignet sind.</p>
<h2>Nutzereingaben und sensible Daten</h2>
<p>Geben Sie keine Passwörter, privaten Schlüssel, Geheimnisse, personenbezogenen Daten, vertraulichen Geschäftsinformationen, Produktiv-Tokens oder Informationen ein, zu deren Verarbeitung Sie nicht berechtigt sind. Einige Werkzeuge können Daten in Ihrem Browser verarbeiten, andere können Eingaben an den Server oder die API der Website senden.</p>
<h2>Konten</h2>
<p>Wenn Sie ein Konto erstellen, müssen Sie korrekte Angaben machen, Ihre Zugangsdaten schützen und uns informieren, wenn Sie eine Kompromittierung vermuten. Wir können Konten sperren oder beenden, die gegen diese Bedingungen verstoßen oder Risiken für die Website oder andere Nutzer verursachen.</p>
<h2>Zulässige Nutzung</h2>
<p>Sie dürfen die Website nicht missbrauchen, insbesondere nicht unbefugten Zugriff versuchen, den Dienst stören, die Website überlasten oder scrapen, Ratenbegrenzungen umgehen, rechtswidrige Inhalte übermitteln oder die Website nutzen, um anderen zu schaden oder geltendes Recht zu verletzen.</p>
<h2>Geistiges Eigentum</h2>
<p>Die Website, ihr Design, ihre Texte, Software und sonstigen Materialien gehören uns oder unseren Lizenzgebern und sind rechtlich geschützt. Sie dürfen die Website für persönliche oder interne geschäftliche Zwecke nutzen, wesentliche Teile jedoch nicht ohne Erlaubnis kopieren, weiterverkaufen oder vervielfältigen.</p>
<h2>Drittanbieter und Links</h2>
<p>Die Website kann Links zu Ressourcen Dritter enthalten oder Dienste Dritter nutzen. Für Websites, Dienste, Inhalte, Richtlinien oder Praktiken Dritter sind wir nicht verantwortlich.</p>
<h2>Verfügbarkeit und Änderungen</h2>
<p>Wir können jeden Teil der Website jederzeit aktualisieren, aussetzen, einschränken oder einstellen. Wir garantieren nicht, dass die Website immer verfügbar, fehlerfrei oder mit jedem Gerät oder Browser kompatibel ist.</p>
<h2>Haftungsausschluss und Haftungsbeschränkung</h2>
<p>Die Website wird “wie besehen” und “wie verfügbar” bereitgestellt. Soweit gesetzlich zulässig, schließen wir Gewährleistungen aus und haften nicht für mittelbare, zufällige, besondere, Folge- oder Strafschäden, Datenverlust, entgangenen Gewinn oder Schäden durch Missbrauch der Website. Zwingende Verbraucherrechte bleiben unberührt.</p>
<h2>Datenschutz</h2>
<p>Die Verarbeitung personenbezogener Daten ist in unserer <a href="https://ciphersonline.com/de/privacy-policy">Datenschutzerklärung</a> beschrieben. Cookies und ähnliche Technologien werden in unserer <a href="https://ciphersonline.com/de/cookie-policy">Cookie-Richtlinie</a> erläutert.</p>
<h2>Anwendbares Recht</h2>
<p>Diese Bedingungen unterliegen dem Recht Serbiens, sofern zwingende Vorschriften Ihres Wohnsitzlandes nichts anderes bestimmen. Gesetzliche Verbraucherrechte bleiben unberührt.</p>
<h2>Änderungen dieser Bedingungen</h2>
<p>Wir können diese Bedingungen von Zeit zu Zeit aktualisieren. Die aktualisierte Fassung gilt ab Veröffentlichung auf der Website. Bei wesentlichen Änderungen können wir, soweit angemessen, zusätzliche Hinweise bereitstellen.</p>
<h2>Kontakt</h2>
<p>Bei Fragen zu diesen Bedingungen kontaktieren Sie uns unter <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML,
                1,
            ],
            'es' => [
                'es',
                'terms-of-service',
                'Términos de Servicio',
                <<<'HTML'
<p><strong>Última actualización:</strong> 05.06.2026</p>
<p>Estos Términos de Servicio regulan su acceso y uso de ciphersonline.com (el “Sitio”, “CiphersOnline”, “nosotros”). Al acceder al Sitio o utilizarlo, usted acepta estos Términos. Si no está de acuerdo, no utilice el Sitio.</p>
<h2>Quiénes somos</h2>
<p>El Sitio es operado por BRUKVA PR. Contacto: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>. Dirección: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Qué ofrece el Sitio</h2>
<p>CiphersOnline ofrece herramientas educativas y utilitarias para cifrados clásicos, codificaciones, decodificación y materiales de aprendizaje relacionados. El Sitio se proporciona solo con fines informativos, educativos, de depuración y de conveniencia.</p>
<h2>No constituye asesoramiento profesional</h2>
<p>El Sitio no ofrece asesoramiento legal, de ciberseguridad, criptográfico, de cumplimiento ni otro asesoramiento profesional. Los cifrados clásicos y las herramientas de codificación del Sitio pueden ser inseguros para proteger información en entornos reales. Usted es responsable de evaluar si los resultados son adecuados para su uso.</p>
<h2>Datos introducidos y datos sensibles</h2>
<p>No introduzca contraseñas, claves privadas, secretos, datos personales, información comercial confidencial, tokens de producción ni información que no esté autorizado a procesar. Algunas herramientas pueden procesar datos en su navegador, mientras que otras pueden enviar la entrada al servidor o API del Sitio.</p>
<h2>Cuentas</h2>
<p>Si crea una cuenta, debe proporcionar información exacta, proteger sus credenciales y notificarnos si cree que su cuenta ha sido comprometida. Podemos suspender o cancelar cuentas que incumplan estos Términos o creen riesgos para el Sitio u otros usuarios.</p>
<h2>Uso aceptable</h2>
<p>Usted se compromete a no hacer un uso indebido del Sitio, incluyendo intentar acceso no autorizado, interrumpir el servicio, realizar scraping o sobrecargar el Sitio, eludir límites de uso, enviar contenido ilegal o utilizar el Sitio para dañar a otros o infringir la ley aplicable.</p>
<h2>Propiedad intelectual</h2>
<p>El Sitio, su diseño, textos, software y otros materiales nos pertenecen a nosotros o a nuestros licenciantes y están protegidos por la ley. Puede usar el Sitio para fines personales o internos de negocio, pero no puede copiar, revender ni reproducir partes sustanciales del Sitio sin permiso.</p>
<h2>Servicios y enlaces de terceros</h2>
<p>El Sitio puede incluir enlaces a recursos de terceros o utilizar servicios de terceros. No somos responsables de sitios, servicios, contenidos, políticas o prácticas de terceros.</p>
<h2>Disponibilidad y cambios</h2>
<p>Podemos actualizar, suspender, limitar o discontinuar cualquier parte del Sitio en cualquier momento. No garantizamos que el Sitio esté siempre disponible, libre de errores o compatible con todos los dispositivos o navegadores.</p>
<h2>Exención de garantías y limitación de responsabilidad</h2>
<p>El Sitio se proporciona “tal cual” y “según disponibilidad”. En la máxima medida permitida por la ley, rechazamos garantías y no somos responsables de daños indirectos, incidentales, especiales, consecuentes o punitivos, pérdida de datos, lucro cesante o daños causados por el uso indebido del Sitio. Nada en estos Términos limita derechos que no puedan limitarse bajo normas obligatorias de protección del consumidor.</p>
<h2>Privacidad</h2>
<p>El tratamiento de datos personales se describe en la <a href="https://ciphersonline.com/es/privacy-policy">Política de Privacidad</a>. El uso de cookies y tecnologías similares se describe en la <a href="https://ciphersonline.com/es/cookie-policy">Política de Cookies</a>.</p>
<h2>Ley aplicable</h2>
<p>Estos Términos se rigen por las leyes de Serbia, salvo que las normas obligatorias de su país de residencia dispongan lo contrario. Sus derechos de consumidor aplicables por ley no se ven afectados.</p>
<h2>Cambios en estos Términos</h2>
<p>Podemos actualizar estos Términos periódicamente. La versión actualizada entra en vigor al publicarse en el Sitio. Si los cambios son importantes, podemos proporcionar aviso adicional cuando corresponda.</p>
<h2>Contacto</h2>
<p>Si tiene preguntas sobre estos Términos, contáctenos en <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML,
                1,
            ],
            'fr' => [
                'fr',
                'terms-of-service',
                'Conditions d’utilisation',
                <<<'HTML'
<p><strong>Dernière mise à jour :</strong> 05.06.2026</p>
<p>Les présentes Conditions d’utilisation régissent votre accès à ciphersonline.com (le “Site”, “CiphersOnline”, “nous”) et son utilisation. En accédant au Site ou en l’utilisant, vous acceptez ces Conditions. Si vous ne les acceptez pas, veuillez ne pas utiliser le Site.</p>
<h2>Qui nous sommes</h2>
<p>Le Site est exploité par BRUKVA PR. Contact : <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>. Adresse : MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Ce que fournit le Site</h2>
<p>CiphersOnline fournit des outils éducatifs et utilitaires pour les chiffrements classiques, les encodages, le décodage et des supports d’apprentissage associés. Le Site est fourni uniquement à des fins d’information, d’éducation, de débogage et de commodité.</p>
<h2>Aucun conseil professionnel</h2>
<p>Le Site ne fournit pas de conseil juridique, de cybersécurité, cryptographique, de conformité ou autre conseil professionnel. Les chiffrements classiques et outils d’encodage présentés sur le Site peuvent être insuffisants pour protéger des informations en situation réelle. Vous êtes responsable d’évaluer si les résultats conviennent à votre usage.</p>
<h2>Saisies utilisateur et données sensibles</h2>
<p>N’entrez pas de mots de passe, clés privées, secrets, données personnelles, informations commerciales confidentielles, jetons de production ou informations que vous n’êtes pas autorisé à traiter. Certains outils peuvent traiter les données dans votre navigateur, tandis que d’autres peuvent envoyer les données au serveur ou à l’API du Site.</p>
<h2>Comptes</h2>
<p>Si vous créez un compte, vous devez fournir des informations exactes, protéger vos identifiants et nous avertir si vous pensez que votre compte a été compromis. Nous pouvons suspendre ou résilier les comptes qui violent ces Conditions ou créent un risque pour le Site ou d’autres utilisateurs.</p>
<h2>Utilisation acceptable</h2>
<p>Vous vous engagez à ne pas faire un usage abusif du Site, notamment en tentant un accès non autorisé, en perturbant le service, en surchargeant ou en extrayant massivement le Site, en contournant les limites de requêtes, en soumettant du contenu illégal ou en utilisant le Site pour nuire à autrui ou enfreindre la loi applicable.</p>
<h2>Propriété intellectuelle</h2>
<p>Le Site, sa conception, ses textes, logiciels et autres éléments nous appartiennent ou appartiennent à nos concédants et sont protégés par la loi. Vous pouvez utiliser le Site à des fins personnelles ou professionnelles internes, mais vous ne pouvez pas copier, revendre ou reproduire des parties substantielles du Site sans autorisation.</p>
<h2>Services tiers et liens</h2>
<p>Le Site peut contenir des liens vers des ressources tierces ou utiliser des services tiers. Nous ne sommes pas responsables des sites, services, contenus, politiques ou pratiques de tiers.</p>
<h2>Disponibilité et modifications</h2>
<p>Nous pouvons mettre à jour, suspendre, limiter ou interrompre toute partie du Site à tout moment. Nous ne garantissons pas que le Site sera toujours disponible, exempt d’erreurs ou compatible avec chaque appareil ou navigateur.</p>
<h2>Exclusion de garanties et limitation de responsabilité</h2>
<p>Le Site est fourni “tel quel” et “selon disponibilité”. Dans toute la mesure permise par la loi, nous excluons les garanties et ne sommes pas responsables des dommages indirects, accessoires, spéciaux, consécutifs ou punitifs, de la perte de données, du manque à gagner ou des dommages causés par une mauvaise utilisation du Site. Rien dans ces Conditions ne limite les droits qui ne peuvent pas être limités en vertu des règles impératives de protection des consommateurs.</p>
<h2>Confidentialité</h2>
<p>Le traitement des données personnelles est décrit dans la <a href="https://ciphersonline.com/fr/privacy-policy">Politique de Confidentialité</a>. L’utilisation des cookies et technologies similaires est décrite dans la <a href="https://ciphersonline.com/fr/cookie-policy">Politique de Cookies</a>.</p>
<h2>Loi applicable</h2>
<p>Ces Conditions sont régies par les lois de Serbie, sauf si les lois impératives de votre pays de résidence en disposent autrement. Vos droits de consommateur applicables par la loi restent inchangés.</p>
<h2>Modifications de ces Conditions</h2>
<p>Nous pouvons mettre à jour ces Conditions de temps à autre. La version mise à jour prend effet dès sa publication sur le Site. En cas de modifications importantes, nous pouvons fournir un avis supplémentaire lorsque cela est approprié.</p>
<h2>Contact</h2>
<p>Pour toute question concernant ces Conditions, contactez-nous à <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML,
                1,
            ],
            'it' => [
                'it',
                'terms-of-service',
                'Termini di Servizio',
                <<<'HTML'
<p><strong>Ultimo aggiornamento:</strong> 05.06.2026</p>
<p>I presenti Termini di Servizio regolano l’accesso e l’utilizzo di ciphersonline.com (il “Sito”, “CiphersOnline”, “noi”). Accedendo al Sito o utilizzandolo, accetti questi Termini. Se non li accetti, ti invitiamo a non utilizzare il Sito.</p>
<h2>Chi siamo</h2>
<p>Il Sito è gestito da BRUKVA PR. Contatto: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>. Indirizzo: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Cosa offre il Sito</h2>
<p>CiphersOnline offre strumenti educativi e di utilità per cifrari classici, codifiche, decodifica e materiali didattici correlati. Il Sito è fornito esclusivamente per scopi informativi, educativi, di debug e di comodità.</p>
<h2>Nessuna consulenza professionale</h2>
<p>Il Sito non fornisce consulenza legale, di cybersicurezza, crittografica, di conformità o altra consulenza professionale. I cifrari classici e gli strumenti di codifica mostrati sul Sito possono essere insicuri per la protezione reale delle informazioni. Sei responsabile di valutare se i risultati siano adatti al tuo utilizzo.</p>
<h2>Input dell’utente e dati sensibili</h2>
<p>Non inserire password, chiavi private, segreti, dati personali, informazioni aziendali riservate, token di produzione o informazioni che non sei autorizzato a trattare. Alcuni strumenti possono elaborare i dati nel browser, mentre altri possono inviare l’input al server o all’API del Sito.</p>
<h2>Account</h2>
<p>Se crei un account, devi fornire informazioni accurate, proteggere le tue credenziali e informarci se ritieni che l’account sia stato compromesso. Possiamo sospendere o chiudere account che violano questi Termini o creano rischi per il Sito o altri utenti.</p>
<h2>Uso accettabile</h2>
<p>Accetti di non abusare del Sito, inclusi tentativi di accesso non autorizzato, interruzione del servizio, scraping o sovraccarico del Sito, aggiramento dei limiti di richiesta, invio di contenuti illeciti o uso del Sito per danneggiare altri o violare la legge applicabile.</p>
<h2>Proprietà intellettuale</h2>
<p>Il Sito, il suo design, i testi, il software e gli altri materiali appartengono a noi o ai nostri licenzianti e sono protetti dalla legge. Puoi usare il Sito per scopi personali o aziendali interni, ma non puoi copiare, rivendere o riprodurre parti sostanziali del Sito senza autorizzazione.</p>
<h2>Servizi e link di terzi</h2>
<p>Il Sito può includere link a risorse di terzi o utilizzare servizi di terzi. Non siamo responsabili di siti, servizi, contenuti, politiche o pratiche di terzi.</p>
<h2>Disponibilità e modifiche</h2>
<p>Possiamo aggiornare, sospendere, limitare o interrompere qualsiasi parte del Sito in qualsiasi momento. Non garantiamo che il Sito sia sempre disponibile, privo di errori o compatibile con ogni dispositivo o browser.</p>
<h2>Esclusione di garanzie e limitazione di responsabilità</h2>
<p>Il Sito è fornito “così com’è” e “secondo disponibilità”. Nella misura massima consentita dalla legge, escludiamo garanzie e non siamo responsabili per danni indiretti, incidentali, speciali, consequenziali o punitivi, perdita di dati, perdita di profitti o danni causati da uso improprio del Sito. Nulla in questi Termini limita diritti che non possono essere limitati dalle norme imperative di tutela dei consumatori.</p>
<h2>Privacy</h2>
<p>Il trattamento dei dati personali è descritto nella <a href="https://ciphersonline.com/it/privacy-policy">Informativa sulla Privacy</a>. L’uso di cookie e tecnologie simili è descritto nella <a href="https://ciphersonline.com/it/cookie-policy">Politica sui Cookie</a>.</p>
<h2>Legge applicabile</h2>
<p>Questi Termini sono regolati dalle leggi della Serbia, salvo che le norme imperative del tuo paese di residenza dispongano diversamente. I tuoi diritti di consumatore previsti dalla legge restano invariati.</p>
<h2>Modifiche ai Termini</h2>
<p>Possiamo aggiornare questi Termini di tanto in tanto. La versione aggiornata diventa efficace quando viene pubblicata sul Sito. In caso di modifiche sostanziali, possiamo fornire un avviso aggiuntivo ove opportuno.</p>
<h2>Contatto</h2>
<p>Per domande su questi Termini, contattaci a <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML,
                1,
            ],
            'pt' => [
                'pt',
                'terms-of-service',
                'Termos de Serviço',
                <<<'HTML'
<p><strong>Última atualização:</strong> 05.06.2026</p>
<p>Estes Termos de Serviço regem o seu acesso e uso de ciphersonline.com (o “Site”, “CiphersOnline”, “nós”). Ao acessar ou usar o Site, você concorda com estes Termos. Se não concordar, por favor não use o Site.</p>
<h2>Quem somos</h2>
<p>O Site é operado por BRUKVA PR. Contato: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>. Endereço: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>O que o Site oferece</h2>
<p>CiphersOnline fornece ferramentas educacionais e utilitárias para cifras clássicas, codificações, decodificação e materiais de aprendizagem relacionados. O Site é fornecido apenas para fins informativos, educacionais, de depuração e conveniência.</p>
<h2>Não constitui aconselhamento profissional</h2>
<p>O Site não fornece aconselhamento jurídico, de cibersegurança, criptográfico, de conformidade ou outro aconselhamento profissional. Cifras clássicas e ferramentas de codificação mostradas no Site podem ser inseguras para proteção real de informações. Você é responsável por avaliar se os resultados são adequados ao seu uso.</p>
<h2>Entrada do usuário e dados sensíveis</h2>
<p>Não insira senhas, chaves privadas, segredos, dados pessoais, informações comerciais confidenciais, tokens de produção ou informações que você não esteja autorizado a processar. Algumas ferramentas podem processar dados no seu navegador, enquanto outras podem enviar a entrada ao servidor ou à API do Site.</p>
<h2>Contas</h2>
<p>Se você criar uma conta, deverá fornecer informações precisas, manter suas credenciais seguras e nos avisar se acreditar que sua conta foi comprometida. Podemos suspender ou encerrar contas que violem estes Termos ou criem risco para o Site ou outros usuários.</p>
<h2>Uso aceitável</h2>
<p>Você concorda em não usar indevidamente o Site, incluindo tentar acesso não autorizado, interromper o serviço, fazer scraping ou sobrecarregar o Site, contornar limites de requisição, enviar conteúdo ilegal ou usar o Site para prejudicar terceiros ou violar a lei aplicável.</p>
<h2>Propriedade intelectual</h2>
<p>O Site, seu design, textos, software e outros materiais pertencem a nós ou a nossos licenciadores e são protegidos por lei. Você pode usar o Site para fins pessoais ou empresariais internos, mas não pode copiar, revender ou reproduzir partes substanciais do Site sem permissão.</p>
<h2>Serviços e links de terceiros</h2>
<p>O Site pode incluir links para recursos de terceiros ou usar serviços de terceiros. Não somos responsáveis por sites, serviços, conteúdos, políticas ou práticas de terceiros.</p>
<h2>Disponibilidade e alterações</h2>
<p>Podemos atualizar, suspender, limitar ou descontinuar qualquer parte do Site a qualquer momento. Não garantimos que o Site estará sempre disponível, livre de erros ou compatível com todos os dispositivos ou navegadores.</p>
<h2>Isenção de garantias e limitação de responsabilidade</h2>
<p>O Site é fornecido “como está” e “conforme disponível”. Na máxima extensão permitida por lei, rejeitamos garantias e não somos responsáveis por danos indiretos, incidentais, especiais, consequenciais ou punitivos, perda de dados, lucros cessantes ou danos causados por uso indevido do Site. Nada nestes Termos limita direitos que não possam ser limitados por normas obrigatórias de proteção ao consumidor.</p>
<h2>Privacidade</h2>
<p>O tratamento de dados pessoais é descrito na <a href="https://ciphersonline.com/pt/privacy-policy">Política de Privacidade</a>. O uso de cookies e tecnologias semelhantes é descrito na <a href="https://ciphersonline.com/pt/cookie-policy">Política de Cookies</a>.</p>
<h2>Lei aplicável</h2>
<p>Estes Termos são regidos pelas leis da Sérvia, salvo se leis obrigatórias do seu país de residência dispuserem de outra forma. Seus direitos de consumidor previstos em lei permanecem inalterados.</p>
<h2>Alterações destes Termos</h2>
<p>Podemos atualizar estes Termos periodicamente. A versão atualizada entra em vigor quando publicada no Site. Se as alterações forem relevantes, podemos fornecer aviso adicional quando apropriado.</p>
<h2>Contato</h2>
<p>Se tiver dúvidas sobre estes Termos, entre em contato em <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>.</p>
HTML,
                1,
            ],
            'tr' => [
                'tr',
                'terms-of-service',
                'Hizmet Şartları',
                <<<'HTML'
<p><strong>Son güncelleme:</strong> 05.06.2026</p>
<p>Bu Hizmet Şartları, ciphersonline.com (“Web Sitesi”, “CiphersOnline”, “biz”) sitesine erişiminizi ve siteyi kullanımınızı düzenler. Web Sitesi’ne erişerek veya siteyi kullanarak bu Şartları kabul etmiş olursunuz. Kabul etmiyorsanız lütfen Web Sitesi’ni kullanmayın.</p>
<h2>Biz kimiz</h2>
<p>Web Sitesi BRUKVA PR tarafından işletilmektedir. İletişim: <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a>. Adres: MITE RUŽIĆA 2, NOVI SAD, SERBIA.</p>
<h2>Web Sitesi ne sağlar</h2>
<p>CiphersOnline klasik şifreler, kodlamalar, kod çözme ve ilgili öğrenme materyalleri için eğitim ve yardımcı araçlar sağlar. Web Sitesi yalnızca bilgilendirme, eğitim, hata ayıklama ve kolaylık amaçlarıyla sunulur.</p>
<h2>Profesyonel tavsiye değildir</h2>
<p>Web Sitesi hukuki, siber güvenlik, kriptografi, uyum veya başka bir profesyonel danışmanlık sağlamaz. Web Sitesi’nde gösterilen klasik şifreler ve kodlama araçları gerçek dünyada bilgileri korumak için güvenli olmayabilir. Çıktıların kendi kullanımınıza uygun olup olmadığını değerlendirmek sizin sorumluluğunuzdadır.</p>
<h2>Kullanıcı girdisi ve hassas veriler</h2>
<p>Parola, özel anahtar, sır, kişisel veri, gizli ticari bilgi, üretim token’ı veya işleme yetkiniz olmayan herhangi bir bilgiyi girmeyin. Bazı araçlar verileri tarayıcınızda işleyebilir, bazıları ise girdiyi Web Sitesi sunucusuna veya API’sine gönderebilir.</p>
<h2>Hesaplar</h2>
<p>Bir hesap oluşturursanız doğru bilgi sağlamalı, kimlik bilgilerinizi güvende tutmalı ve hesabınızın ele geçirildiğini düşünüyorsanız bizi bilgilendirmelisiniz. Bu Şartları ihlal eden veya Web Sitesi ya da diğer kullanıcılar için risk oluşturan hesapları askıya alabilir veya sonlandırabiliriz.</p>
<h2>Kabul edilebilir kullanım</h2>
<p>Web Sitesi’ni kötüye kullanmamayı kabul edersiniz. Buna yetkisiz erişim girişimleri, hizmeti bozma, scraping veya aşırı yükleme, hız sınırlarını aşma, yasa dışı içerik gönderme ya da Web Sitesi’ni başkalarına zarar vermek veya geçerli hukuku ihlal etmek için kullanma dahildir.</p>
<h2>Fikri mülkiyet</h2>
<p>Web Sitesi, tasarımı, metinleri, yazılımı ve diğer materyalleri bize veya lisans verenlerimize aittir ve yasalarla korunur. Web Sitesi’ni kişisel veya kurum içi iş amaçlarıyla kullanabilirsiniz, ancak Web Sitesi’nin önemli bölümlerini izinsiz kopyalayamaz, yeniden satamaz veya çoğaltamazsınız.</p>
<h2>Üçüncü taraf hizmetleri ve bağlantılar</h2>
<p>Web Sitesi üçüncü taraf kaynaklara bağlantılar içerebilir veya üçüncü taraf hizmetleri kullanabilir. Üçüncü taraf web siteleri, hizmetleri, içerikleri, politikaları veya uygulamalarından sorumlu değiliz.</p>
<h2>Kullanılabilirlik ve değişiklikler</h2>
<p>Web Sitesi’nin herhangi bir bölümünü istediğimiz zaman güncelleyebilir, askıya alabilir, sınırlayabilir veya sonlandırabiliriz. Web Sitesi’nin her zaman erişilebilir, hatasız veya her cihaz ve tarayıcıyla uyumlu olacağını garanti etmiyoruz.</p>
<h2>Garanti reddi ve sorumluluğun sınırlandırılması</h2>
<p>Web Sitesi “olduğu gibi” ve “mevcut olduğu şekilde” sunulur. Kanunun izin verdiği en geniş ölçüde garantileri reddederiz ve dolaylı, arızi, özel, sonuçsal veya cezai zararlardan, veri kaybından, kâr kaybından veya Web Sitesi’nin kötüye kullanımından kaynaklanan zararlardan sorumlu olmayız. Bu Şartlardaki hiçbir hüküm, zorunlu tüketici koruma yasaları uyarınca sınırlandırılamayan hakları sınırlandırmaz.</p>
<h2>Gizlilik</h2>
<p>Kişisel verilerin işlenmesi <a href="https://ciphersonline.com/tr/privacy-policy">Gizlilik Politikası</a> içinde açıklanmıştır. Çerezler ve benzer teknolojilerin kullanımı <a href="https://ciphersonline.com/tr/cookie-policy">Çerez Politikası</a> içinde açıklanmıştır.</p>
<h2>Uygulanacak hukuk</h2>
<p>Bu Şartlar, ikamet ettiğiniz ülkenin zorunlu yasaları aksini gerektirmedikçe Sırbistan yasalarına tabidir. Kanunen sahip olduğunuz tüketici hakları etkilenmez.</p>
<h2>Bu Şartlardaki değişiklikler</h2>
<p>Bu Şartları zaman zaman güncelleyebiliriz. Güncellenen sürüm Web Sitesi’nde yayınlandığında yürürlüğe girer. Değişiklikler önemliyse uygun olduğunda ek bildirim sağlayabiliriz.</p>
<h2>İletişim</h2>
<p>Bu Şartlar hakkında sorularınız varsa bize <a href="mailto:contact@ciphersonline.com">contact@ciphersonline.com</a> adresinden ulaşın.</p>
HTML,
                1,
            ],
        ];
    }
}
