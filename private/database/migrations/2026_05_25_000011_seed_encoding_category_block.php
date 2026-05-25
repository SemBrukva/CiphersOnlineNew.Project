<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет информационный блок для категории "Кодирование и преобразование данных" и его локализации.
 */
class SeedEncodingCategoryBlock extends Migration
{
    /**
     * Создаёт или обновляет блок и переводы для категории encoding.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['encoding']
        );

        if ($category === false) {
            return;
        }

        $categoryId = (int) $category['id'];
        $now = date('Y-m-d H:i:s');

        $block = $this->db->fetch(
            'SELECT b.id FROM ' . Tables::CIPHERS_CATEGORIES_BLOCKS . ' b '
            . 'INNER JOIN ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS . ' bt ON bt.block_id = b.id '
            . 'WHERE b.category_id = ? AND bt.language = ? AND bt.title = ? LIMIT 1',
            [$categoryId, 'ru', 'Что такое кодирование данных?']
        );

        if ($block === false) {
            $blockId = (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_BLOCKS . ' (category_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [$categoryId, 10, 1, $now, $now]
            );
        } else {
            $blockId = (int) $block['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_CATEGORIES_BLOCKS . ' SET sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [10, 1, $now, $blockId]
            );
        }

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation(
                $blockId,
                $language,
                (string) $translation['title'],
                (string) $translation['text'],
                $now
            );
        }
    }

    /**
     * Удаляет добавленный блок для категории encoding.
     */
    public function down(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['encoding']
        );

        if ($category === false) {
            return;
        }

        $categoryId = (int) $category['id'];

        $block = $this->db->fetch(
            'SELECT b.id FROM ' . Tables::CIPHERS_CATEGORIES_BLOCKS . ' b '
            . 'INNER JOIN ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS . ' bt ON bt.block_id = b.id '
            . 'WHERE b.category_id = ? AND bt.language = ? AND bt.title = ? LIMIT 1',
            [$categoryId, 'ru', 'Что такое кодирование данных?']
        );

        if ($block === false) {
            return;
        }

        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_BLOCKS . ' WHERE id = ?',
            [(int) $block['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод блока.
     */
    private function upsertTranslation(int $blockId, string $language, string $title, string $text, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ? LIMIT 1',
            [$blockId, $language]
        );

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS . ' (block_id, language, title, text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$blockId, $language, $title, $text, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS . ' SET title = ?, text = ?, updated_at = ? WHERE id = ?',
            [$title, $text, $now, (int) $existing['id']]
        );
    }

    /**
     * Возвращает тексты блока по языкам.
     *
     * @return array<string, array{title:string, text:string}>
     */
    private function translations(): array
    {
        return [
            'ru' => [
                'title' => 'Что такое кодирование данных?',
                'text' => <<<'HTML'
<p class="encoding-hub-text">Кодирование данных — это преобразование информации в другой формат для передачи, хранения или совместимости между системами. Форматы Base64 и Hex помогают удобно работать с текстовыми и бинарными данными в API, email, HTTP, файлах и протоколах.</p>
<p class="encoding-hub-text">Encoding-инструменты используются разработчиками для безопасной передачи данных между системами, диагностики payload, обработки query-параметров, анализа байтовых представлений и преобразования данных между различными форматами.</p>
<div class="block-notice">
                <div class="block-__header">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Кодирование ≠ Шифрование</strong>
                </div>
                <p>Base64, Hex и похожие форматы не предназначены для защиты информации. Они меняют представление данных, но обычно легко обратимы без пароля и секретного ключа.</p>
                <p>Для защиты данных используются криптографические алгоритмы и системы шифрования.</p>            </div>
HTML,
            ],
            'en' => [
                'title' => 'What is data encoding?',
                'text' => <<<'HTML'
<p class="encoding-hub-text">Data encoding is the process of converting information into another format for transfer, storage, or compatibility between systems. Formats like Base64 and Hex make it easier to work with text and binary data in APIs, email, HTTP, files, and protocols.</p>
<p class="encoding-hub-text">Encoding tools are used by developers for safe data exchange between systems, payload diagnostics, query parameter handling, byte-level analysis, and converting data between different formats.</p>
<div class="block-notice">
                <div class="block-__header">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Encoding ≠ Encryption</strong>
                </div>
                <p>Base64, Hex, and similar formats are not meant to protect information. They change data representation, but are usually easily reversible without a password or secret key.</p>
                <p>Cryptographic algorithms and encryption systems are used to protect data.</p>            </div>
HTML,
            ],
            'de' => [
                'title' => 'Was ist Datenkodierung?',
                'text' => <<<'HTML'
<p class="encoding-hub-text">Datenkodierung ist die Umwandlung von Informationen in ein anderes Format für Übertragung, Speicherung oder Kompatibilität zwischen Systemen. Formate wie Base64 und Hex helfen dabei, bequem mit Text- und Binärdaten in APIs, E-Mails, HTTP, Dateien und Protokollen zu arbeiten.</p>
<p class="encoding-hub-text">Encoding-Tools werden von Entwicklern für den sicheren Datenaustausch zwischen Systemen, Payload-Diagnose, Verarbeitung von Query-Parametern, Analyse von Byte-Darstellungen und Umwandlung von Daten zwischen verschiedenen Formaten verwendet.</p>
<div class="block-notice">
                <div class="block-__header">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Kodierung ≠ Verschlüsselung</strong>
                </div>
                <p>Base64, Hex und ähnliche Formate sind nicht zum Schutz von Informationen gedacht. Sie ändern die Darstellung von Daten, sind aber in der Regel ohne Passwort und geheimen Schlüssel leicht umkehrbar.</p>
                <p>Zum Schutz von Daten werden kryptografische Algorithmen und Verschlüsselungssysteme verwendet.</p>            </div>
HTML,
            ],
            'es' => [
                'title' => '¿Qué es la codificación de datos?',
                'text' => <<<'HTML'
<p class="encoding-hub-text">La codificación de datos es la conversión de información a otro formato para su transmisión, almacenamiento o compatibilidad entre sistemas. Formatos como Base64 y Hex facilitan el trabajo con datos de texto y binarios en API, correo electrónico, HTTP, archivos y protocolos.</p>
<p class="encoding-hub-text">Las herramientas de encoding son usadas por desarrolladores para el intercambio seguro de datos entre sistemas, diagnóstico de payload, procesamiento de parámetros de consulta, análisis de representaciones en bytes y conversión de datos entre distintos formatos.</p>
<div class="block-notice">
                <div class="block-__header">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Codificación ≠ Cifrado</strong>
                </div>
                <p>Base64, Hex y formatos similares no están diseñados para proteger información. Cambian la representación de los datos, pero normalmente son reversibles sin contraseña ni clave secreta.</p>
                <p>Para proteger los datos se utilizan algoritmos criptográficos y sistemas de cifrado.</p>            </div>
HTML,
            ],
            'fr' => [
                'title' => 'Qu’est-ce que l’encodage des données ?',
                'text' => <<<'HTML'
<p class="encoding-hub-text">L’encodage des données consiste à transformer des informations dans un autre format pour la transmission, le stockage ou la compatibilité entre systèmes. Des formats comme Base64 et Hex facilitent le travail avec des données texte et binaires dans les API, les e-mails, HTTP, les fichiers et les protocoles.</p>
<p class="encoding-hub-text">Les outils d’encoding sont utilisés par les développeurs pour l’échange sécurisé de données entre systèmes, le diagnostic des payloads, le traitement des paramètres de requête, l’analyse des représentations en octets et la conversion de données entre différents formats.</p>
<div class="block-notice">
                <div class="block-__header">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Encodage ≠ Chiffrement</strong>
                </div>
                <p>Base64, Hex et formats similaires ne sont pas destinés à protéger les informations. Ils modifient la représentation des données, mais sont généralement facilement réversibles sans mot de passe ni clé secrète.</p>
                <p>Pour protéger les données, on utilise des algorithmes cryptographiques et des systèmes de chiffrement.</p>            </div>
HTML,
            ],
            'it' => [
                'title' => 'Che cos’è la codifica dei dati?',
                'text' => <<<'HTML'
<p class="encoding-hub-text">La codifica dei dati è la trasformazione delle informazioni in un altro formato per trasmissione, archiviazione o compatibilità tra sistemi. Formati come Base64 ed Hex aiutano a lavorare comodamente con dati testuali e binari in API, email, HTTP, file e protocolli.</p>
<p class="encoding-hub-text">Gli strumenti di encoding sono usati dagli sviluppatori per lo scambio sicuro di dati tra sistemi, la diagnostica dei payload, la gestione dei parametri di query, l’analisi delle rappresentazioni in byte e la conversione dei dati tra diversi formati.</p>
<div class="block-notice">
                <div class="block-__header">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Codifica ≠ Crittografia</strong>
                </div>
                <p>Base64, Hex e formati simili non sono pensati per proteggere le informazioni. Cambiano la rappresentazione dei dati, ma di solito sono facilmente reversibili senza password o chiave segreta.</p>
                <p>Per proteggere i dati si usano algoritmi crittografici e sistemi di cifratura.</p>            </div>
HTML,
            ],
            'pt' => [
                'title' => 'O que é codificação de dados?',
                'text' => <<<'HTML'
<p class="encoding-hub-text">Codificação de dados é a conversão de informações para outro formato para transmissão, armazenamento ou compatibilidade entre sistemas. Formatos como Base64 e Hex ajudam a trabalhar com dados textuais e binários em APIs, e-mail, HTTP, arquivos e protocolos.</p>
<p class="encoding-hub-text">Ferramentas de encoding são usadas por desenvolvedores para troca segura de dados entre sistemas, diagnóstico de payload, tratamento de parâmetros de consulta, análise de representações em bytes e conversão de dados entre diferentes formatos.</p>
<div class="block-notice">
                <div class="block-__header">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Codificação ≠ Criptografia</strong>
                </div>
                <p>Base64, Hex e formatos semelhantes não foram projetados para proteger informações. Eles mudam a representação dos dados, mas normalmente são facilmente reversíveis sem senha ou chave secreta.</p>
                <p>Para proteger dados, são usados algoritmos criptográficos e sistemas de criptografia.</p>            </div>
HTML,
            ],
            'tr' => [
                'title' => 'Veri kodlama nedir?',
                'text' => <<<'HTML'
<p class="encoding-hub-text">Veri kodlama, bilgilerin iletim, depolama veya sistemler arası uyumluluk için başka bir formata dönüştürülmesidir. Base64 ve Hex gibi formatlar; API, e-posta, HTTP, dosya ve protokollerde metin ve ikili verilerle rahat çalışmayı sağlar.</p>
<p class="encoding-hub-text">Encoding araçları geliştiriciler tarafından sistemler arası güvenli veri aktarımı, payload analizi, query parametresi işleme, bayt düzeyi inceleme ve farklı formatlar arasında veri dönüştürme için kullanılır.</p>
<div class="block-notice">
                <div class="block-__header">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Kodlama ≠ Şifreleme</strong>
                </div>
                <p>Base64, Hex ve benzeri formatlar bilgiyi korumak için tasarlanmamıştır. Verinin gösterimini değiştirirler, ancak genellikle parola veya gizli anahtar olmadan kolayca geri çevrilebilirler.</p>
                <p>Veri koruması için kriptografik algoritmalar ve şifreleme sistemleri kullanılır.</p>            </div>
HTML,
            ],
        ];
    }
}
