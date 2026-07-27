<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет клиентский инструмент «Password Generator» в категорию utilities.
 * Базовый inline-контент (en/ru); подробный контент прорабатывается отдельно.
 */
class SeedPasswordGenerator extends Migration
{
    /**
     * Создаёт или обновляет инструмент, переводы карточки и базовый контент.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['utilities']
        );

        if ($category === false) {
            return;
        }

        $categoryId = (int) $category['id'];
        $now = date('Y-m-d H:i:s');

        $cipherId = $this->upsertCipher($categoryId, 'password-generator', 20, $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }

        $this->upsertContent($cipherId, $this->content(), $now);
    }

    /**
     * Удаляет добавленный инструмент (контент уходит каскадом по FK).
     */
    public function down(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['utilities']
        );

        if ($category === false) {
            return;
        }

        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ?',
            [(int) $category['id'], 'password-generator']
        );
    }

    /**
     * Создаёт или обновляет запись инструмента.
     */
    private function upsertCipher(int $categoryId, string $alias, int $sortOrder, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, $alias]
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, $alias, 'client', $sortOrder, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['client', $sortOrder, 1, $now, $cipherId]
        );

        return $cipherId;
    }

    /**
     * Создаёт или обновляет перевод карточки инструмента.
     *
     * @param array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string} $translation Данные перевода.
     */
    private function upsertTranslation(int $cipherId, string $language, array $translation, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ? AND language = ? LIMIT 1',
            [$cipherId, $language]
        );

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_TRANSLATIONS
                . ' (app_id, language, name, name_short, description, description_stort, meta_title, meta_description, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $cipherId,
                    $language,
                    $translation['name'],
                    $translation['name_short'],
                    $translation['description'],
                    $translation['description_stort'],
                    $translation['meta_title'],
                    $translation['meta_description'],
                    $now,
                    $now,
                ]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_TRANSLATIONS
            . ' SET name = ?, name_short = ?, description = ?, description_stort = ?, meta_title = ?, meta_description = ?, updated_at = ? '
            . 'WHERE id = ?',
            [
                $translation['name'],
                $translation['name_short'],
                $translation['description'],
                $translation['description_stort'],
                $translation['meta_title'],
                $translation['meta_description'],
                $now,
                (int) $existing['id'],
            ]
        );
    }

    /**
     * Создаёт базовый контент страницы: блок, примеры-пресеты, FAQ, теги.
     *
     * @param array<string, mixed> $content Контент инструмента.
     */
    private function upsertContent(int $cipherId, array $content, string $now): void
    {
        $block = $this->upsertBlock($cipherId, 10, $now);
        foreach ($content['block'] as $language => $data) {
            $this->upsertBlockTranslation($block, $language, $data['title'], $data['text'], $now);
        }

        foreach ($content['examples'] as $example) {
            $exampleId = $this->upsertExample($cipherId, $example['sort'], $example['settings'], $now);
            foreach ($example['translations'] as $language => $data) {
                $this->upsertExampleTranslation($exampleId, $language, $data['title'], $data['input'], $data['output'], $data['description'], $now);
            }
        }

        foreach ($content['faq'] as $faq) {
            $faqId = $this->upsertFaq($cipherId, $faq['sort'], $now);
            foreach ($faq['translations'] as $language => $data) {
                $this->upsertFaqTranslation($faqId, $language, $data['question'], $data['answer'], $now);
            }
        }

        foreach ($content['tags'] as $tag) {
            $tagId = $this->upsertTag($cipherId, $tag['sort'], $now);
            foreach ($tag['translations'] as $language => $value) {
                $this->upsertTagTranslation($tagId, $language, $value, $now);
            }
        }
    }

    /**
     * Создаёт или обновляет блок контента по сортировке.
     */
    private function upsertBlock(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_BLOCKS . ' SET published = 1, updated_at = ? WHERE id = ?', [$now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод блока.
     */
    private function upsertBlockTranslation(int $blockId, string $language, string $title, string $text, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ? LIMIT 1',
            [$blockId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' SET title = ?, text = ?, updated_at = ? WHERE id = ?',
                [$title, $text, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' (block_id, language, title, text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$blockId, $language, $title, $text, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет пример-пресет по сортировке. `$settings` — JSON пресета
     * генератора (режим/длина/наборы/число слов и т. п.).
     */
    private function upsertExample(int $cipherId, int $sortOrder, string $settings, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET published = 1, settings = ?, updated_at = ? WHERE id = ?', [$settings, $now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, settings, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?)',
            [$cipherId, $sortOrder, $settings, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(int $exampleId, string $language, string $title, string $input, string $output, string $description, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' SET title = ?, input = ?, output = ?, description = ?, updated_at = ? WHERE id = ?',
                [$title, $input, $output, $description, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' (example_id, language, title, input, output, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$exampleId, $language, $title, $input, $output, $description, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет FAQ по сортировке.
     */
    private function upsertFaq(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_FAQ . ' SET published = 1, show_in_category = 0, updated_at = ? WHERE id = ?', [$now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_FAQ . ' (app_id, sort_order, show_in_category, published, created_at, updated_at) VALUES (?, ?, 0, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод FAQ.
     */
    private function upsertFaqTranslation(int $faqId, string $language, string $question, string $answer, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' WHERE faq_id = ? AND language = ? LIMIT 1',
            [$faqId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' SET question = ?, answer = ?, updated_at = ? WHERE id = ?',
                [$question, $answer, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' (faq_id, language, question, answer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$faqId, $language, $question, $answer, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет тег по сортировке.
     */
    private function upsertTag(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_TAGS . ' SET published = 1, updated_at = ? WHERE id = ?', [$now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_TAGS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод тега.
     */
    private function upsertTagTranslation(int $tagId, string $language, string $tag, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' WHERE tag_id = ? AND language = ? LIMIT 1',
            [$tagId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' SET tag = ?, updated_at = ? WHERE id = ?',
                [$tag, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' (tag_id, language, tag, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$tagId, $language, $tag, $now, $now]
        );
    }

    /**
     * Возвращает переводы карточки инструмента на 8 языков.
     *
     * @return array<string, array<string, string>>
     */
    private function translations(): array
    {
        return [
            'en' => ['name' => 'Password Generator', 'name_short' => 'Password Generator', 'description' => 'Generate strong, random passwords and memorable passphrases in your browser. Choose length, character sets, exclude look-alike characters, or build a Diceware passphrase from the EFF word list. A live strength meter shows the entropy of each result. Everything runs locally — nothing is sent to a server.', 'description_stort' => 'Create strong random passwords and Diceware passphrases online, in your browser.', 'meta_title' => 'Password Generator — Strong Random Passwords & Passphrases | Ciphers Online', 'meta_description' => 'Free online password generator. Create strong random passwords or memorable Diceware passphrases with a live strength meter, locally in your browser. Nothing is sent to a server.'],
            'ru' => ['name' => 'Генератор паролей', 'name_short' => 'Генератор паролей', 'description' => 'Создавайте надёжные случайные пароли и запоминающиеся парольные фразы прямо в браузере. Выбирайте длину, наборы символов, исключайте похожие символы или собирайте Diceware-фразу из словаря EFF. Индикатор надёжности показывает энтропию каждого результата. Всё работает локально — ничего не отправляется на сервер.', 'description_stort' => 'Создавайте надёжные случайные пароли и Diceware-фразы онлайн, в браузере.', 'meta_title' => 'Генератор паролей — надёжные случайные пароли и фразы | Ciphers Online', 'meta_description' => 'Бесплатный онлайн-генератор паролей. Создавайте надёжные случайные пароли или запоминающиеся Diceware-фразы с индикатором надёжности, локально в браузере. Ничего не отправляется на сервер.'],
            'de' => ['name' => 'Passwort-Generator', 'name_short' => 'Passwort-Generator', 'description' => 'Erzeugen Sie starke, zufällige Passwörter und einprägsame Passphrasen im Browser. Wählen Sie Länge und Zeichensätze, schließen Sie ähnlich aussehende Zeichen aus oder erstellen Sie eine Diceware-Passphrase aus der EFF-Wortliste. Eine Stärkeanzeige zeigt die Entropie jedes Ergebnisses. Alles läuft lokal — nichts wird an einen Server gesendet.', 'description_stort' => 'Erzeugen Sie starke Zufallspasswörter und Diceware-Passphrasen online im Browser.', 'meta_title' => 'Passwort-Generator — starke Zufallspasswörter & Passphrasen | Ciphers Online', 'meta_description' => 'Kostenloser Online-Passwort-Generator. Erstellen Sie starke Zufallspasswörter oder einprägsame Diceware-Passphrasen mit Stärkeanzeige, lokal im Browser. Nichts wird an einen Server gesendet.'],
            'es' => ['name' => 'Generador de contraseñas', 'name_short' => 'Generador contraseñas', 'description' => 'Genera contraseñas aleatorias seguras y frases de contraseña fáciles de recordar en tu navegador. Elige la longitud y los conjuntos de caracteres, excluye caracteres parecidos o crea una frase Diceware a partir de la lista de palabras de la EFF. Un medidor de seguridad muestra la entropía de cada resultado. Todo se ejecuta localmente: nada se envía a un servidor.', 'description_stort' => 'Crea contraseñas aleatorias seguras y frases Diceware online, en tu navegador.', 'meta_title' => 'Generador de contraseñas — contraseñas y frases seguras | Ciphers Online', 'meta_description' => 'Generador de contraseñas online gratis. Crea contraseñas aleatorias seguras o frases Diceware fáciles de recordar con medidor de seguridad, localmente en tu navegador.'],
            'fr' => ['name' => 'Générateur de mots de passe', 'name_short' => 'Générateur mdp', 'description' => 'Générez des mots de passe aléatoires robustes et des phrases secrètes mémorisables dans votre navigateur. Choisissez la longueur et les jeux de caractères, excluez les caractères ressemblants ou créez une phrase Diceware à partir de la liste de mots de l\'EFF. Un indicateur de robustesse affiche l\'entropie de chaque résultat. Tout s\'exécute localement — rien n\'est envoyé à un serveur.', 'description_stort' => 'Générez des mots de passe aléatoires robustes et des phrases Diceware dans le navigateur.', 'meta_title' => 'Générateur de mots de passe — robustes et phrases secrètes | Ciphers Online', 'meta_description' => 'Générateur de mots de passe en ligne gratuit. Créez des mots de passe aléatoires robustes ou des phrases Diceware mémorisables avec indicateur de robustesse, localement dans votre navigateur.'],
            'it' => ['name' => 'Generatore di password', 'name_short' => 'Generatore password', 'description' => 'Genera password casuali robuste e passphrase memorizzabili nel browser. Scegli lunghezza e set di caratteri, escludi i caratteri simili o crea una passphrase Diceware dalla lista di parole EFF. Un indicatore di robustezza mostra l\'entropia di ogni risultato. Tutto viene eseguito localmente: nulla viene inviato a un server.', 'description_stort' => 'Crea password casuali robuste e passphrase Diceware online, nel browser.', 'meta_title' => 'Generatore di password — password casuali robuste e passphrase | Ciphers Online', 'meta_description' => 'Generatore di password online gratuito. Crea password casuali robuste o passphrase Diceware memorizzabili con indicatore di robustezza, localmente nel browser.'],
            'pt' => ['name' => 'Gerador de senhas', 'name_short' => 'Gerador de senhas', 'description' => 'Gere senhas aleatórias fortes e frases-senha fáceis de lembrar no navegador. Escolha o comprimento e os conjuntos de caracteres, exclua caracteres parecidos ou crie uma frase Diceware a partir da lista de palavras da EFF. Um medidor de força mostra a entropia de cada resultado. Tudo roda localmente — nada é enviado a um servidor.', 'description_stort' => 'Crie senhas aleatórias fortes e frases Diceware online, no navegador.', 'meta_title' => 'Gerador de senhas — senhas aleatórias fortes e frases | Ciphers Online', 'meta_description' => 'Gerador de senhas online grátis. Crie senhas aleatórias fortes ou frases Diceware fáceis de lembrar com medidor de força, localmente no navegador.'],
            'tr' => ['name' => 'Parola Üreteci', 'name_short' => 'Parola Üreteci', 'description' => 'Tarayıcınızda güçlü, rastgele parolalar ve akılda kalıcı parola cümleleri üretin. Uzunluk ve karakter kümelerini seçin, benzer görünen karakterleri hariç tutun veya EFF kelime listesinden bir Diceware parola cümlesi oluşturun. Bir güç göstergesi her sonucun entropisini gösterir. Her şey yerel çalışır — hiçbir şey sunucuya gönderilmez.', 'description_stort' => 'Güçlü rastgele parolalar ve Diceware parola cümlelerini tarayıcıda çevrimiçi üretin.', 'meta_title' => 'Parola Üreteci — güçlü rastgele parolalar ve parola cümleleri | Ciphers Online', 'meta_description' => 'Ücretsiz çevrimiçi parola üreteci. Güç göstergesiyle güçlü rastgele parolalar veya akılda kalıcı Diceware parola cümlelerini tarayıcınızda yerel olarak oluşturun.'],
        ];
    }

    /**
     * Возвращает базовый контент инструмента (en/ru): блок, примеры-пресеты, FAQ, теги.
     *
     * @return array<string, mixed>
     */
    private function content(): array
    {
        return [
            'examples' => [
                ['sort' => 10, 'settings' => '{"mode":"password","length":16,"lower":true,"upper":true,"digits":true,"symbols":true}', 'translations' => [
                    'en' => ['title' => 'Strong 16-character password', 'input' => '', 'output' => '', 'description' => 'A 16-character mix of lowercase, uppercase, digits and symbols — a solid default for most accounts.'],
                    'ru' => ['title' => 'Надёжный пароль 16 символов', 'input' => '', 'output' => '', 'description' => 'Смесь из 16 символов: строчные, прописные, цифры и спецсимволы — надёжный вариант по умолчанию для большинства аккаунтов.'],
                ]],
                ['sort' => 20, 'settings' => '{"mode":"password","length":24,"lower":true,"upper":true,"digits":true,"symbols":true,"excludeSimilar":true}', 'translations' => [
                    'en' => ['title' => 'Readable 24 (no look-alikes)', 'input' => '', 'output' => '', 'description' => 'Excludes look-alike characters like l, 1, O and 0 so the password is easy to type from a screen.'],
                    'ru' => ['title' => 'Читаемый 24 (без похожих)', 'input' => '', 'output' => '', 'description' => 'Исключает похожие символы вроде l, 1, O и 0, чтобы пароль было легко ввести с экрана.'],
                ]],
                ['sort' => 30, 'settings' => '{"mode":"passphrase","words":6,"separator":"-","wordCase":"capitalize"}', 'translations' => [
                    'en' => ['title' => 'Diceware passphrase (6 words)', 'input' => '', 'output' => '', 'description' => 'Six random words from the EFF list — about 77 bits of entropy and far easier to remember than a random string.'],
                    'ru' => ['title' => 'Diceware-фраза (6 слов)', 'input' => '', 'output' => '', 'description' => 'Шесть случайных слов из словаря EFF — около 77 бит энтропии и намного легче запомнить, чем случайную строку.'],
                ]],
            ],
            'block' => [
                'en' => ['title' => 'What makes a strong password?', 'text' => '<p>A strong password resists guessing and brute-force attacks. Two things matter most: <strong>length</strong> and <strong>randomness</strong>. The strength of a truly random password is measured in <em>entropy</em> (bits): each extra character multiplies the number of possible combinations. A 16-character password drawn from lowercase, uppercase, digits and symbols has roughly 100 bits of entropy — far beyond what any attacker can brute-force today.</p><p>This tool offers two approaches. <strong>Random passwords</strong> maximise entropy per character and are ideal when stored in a password manager. <strong>Passphrases</strong> (the Diceware method) string together several random words from a curated list; they are slightly longer but much easier to type and remember while still reaching 70+ bits of entropy. Every value is produced with the browser\'s cryptographically secure random generator (<code>crypto.getRandomValues</code>) and never leaves your device.</p>'],
                'ru' => ['title' => 'Что делает пароль надёжным?', 'text' => '<p>Надёжный пароль устойчив к угадыванию и перебору. Важнее всего два фактора: <strong>длина</strong> и <strong>случайность</strong>. Стойкость по-настоящему случайного пароля измеряется в <em>энтропии</em> (битах): каждый дополнительный символ умножает число возможных комбинаций. Пароль из 16 символов, составленный из строчных, прописных, цифр и спецсимволов, имеет около 100 бит энтропии — намного больше, чем можно перебрать сегодня.</p><p>Инструмент предлагает два подхода. <strong>Случайные пароли</strong> дают максимум энтропии на символ и идеальны при хранении в менеджере паролей. <strong>Парольные фразы</strong> (метод Diceware) собирают несколько случайных слов из выверенного словаря; они чуть длиннее, но их гораздо проще вводить и запоминать, при этом энтропия остаётся 70+ бит. Каждое значение создаётся криптографически стойким генератором браузера (<code>crypto.getRandomValues</code>) и никогда не покидает ваше устройство.</p>'],
            ],
            'faq' => [
                ['sort' => 10, 'translations' => [
                    'en' => ['question' => 'How long should my password be?', 'answer' => 'For accounts stored in a password manager, use 16 characters or more with all character sets enabled. For a password you must type or remember, a 4–6 word Diceware passphrase is a better trade-off between security and usability. Avoid anything shorter than 12 characters for important accounts.'],
                    'ru' => ['question' => 'Какой длины должен быть пароль?', 'answer' => 'Для аккаунтов в менеджере паролей используйте 16 символов и больше со всеми наборами символов. Для пароля, который нужно вводить или запоминать, лучше подойдёт Diceware-фраза из 4–6 слов — компромисс между безопасностью и удобством. Для важных аккаунтов избегайте паролей короче 12 символов.'],
                ]],
                ['sort' => 20, 'translations' => [
                    'en' => ['question' => 'Are the generated passwords safe to use?', 'answer' => 'Yes. Every password and passphrase is generated locally in your browser using the cryptographically secure Web Crypto API (crypto.getRandomValues) with rejection sampling to avoid bias. Nothing is transmitted to a server, logged or stored, so the values are yours alone.'],
                    'ru' => ['question' => 'Безопасно ли использовать сгенерированные пароли?', 'answer' => 'Да. Каждый пароль и фраза создаются локально в браузере через криптографически стойкий Web Crypto API (crypto.getRandomValues) с rejection sampling, исключающим смещение. Ничего не передаётся на сервер, не логируется и не сохраняется — значения принадлежат только вам.'],
                ]],
            ],
            'tags' => [
                ['sort' => 10, 'translations' => ['en' => 'password', 'ru' => 'пароль']],
                ['sort' => 20, 'translations' => ['en' => 'passphrase', 'ru' => 'парольная фраза']],
            ],
        ];
    }
}
