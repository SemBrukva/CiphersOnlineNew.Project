<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет FAQ для категории encoding и локализованные переводы.
 */
class SeedEncodingCategoryFaq extends Migration
{
    /**
     * Создаёт или обновляет FAQ и переводы для категории encoding.
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

        foreach ($this->faqItems() as $sortOrder => $item) {
            $faqId = $this->upsertFaq($categoryId, $sortOrder, $now);

            foreach ($item as $language => $translation) {
                $this->upsertFaqTranslation(
                    $faqId,
                    $language,
                    (string) $translation['question'],
                    (string) $translation['answer'],
                    $now
                );
            }
        }
    }

    /**
     * Удаляет FAQ, добавленный этой миграцией, для категории encoding.
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
        $sortOrders = array_keys($this->faqItems());

        if ($sortOrders === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($sortOrders), '?'));
        $bindings = array_merge([$categoryId], $sortOrders);

        $rows = $this->db->fetchAll(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_FAQ
            . ' WHERE category_id = ? AND sort_order IN (' . $placeholders . ')',
            $bindings
        );

        foreach ($rows as $row) {
            $this->db->execute(
                'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_FAQ . ' WHERE id = ?',
                [(int) $row['id']]
            );
        }
    }

    /**
     * Создаёт или обновляет запись FAQ категории.
     */
    private function upsertFaq(int $categoryId, int $sortOrder, string $now): int
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_FAQ
            . ' WHERE category_id = ? AND sort_order = ? LIMIT 1',
            [$categoryId, $sortOrder]
        );

        if ($existing === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_FAQ
                . ' (category_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [$categoryId, $sortOrder, 1, $now, $now]
            );
        }

        $faqId = (int) $existing['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_FAQ . ' SET published = ?, updated_at = ? WHERE id = ?',
            [1, $now, $faqId]
        );

        return $faqId;
    }

    /**
     * Создаёт или обновляет перевод FAQ.
     */
    private function upsertFaqTranslation(int $faqId, string $language, string $question, string $answer, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS
            . ' WHERE faq_id = ? AND language = ? LIMIT 1',
            [$faqId, $language]
        );

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS
                . ' (faq_id, language, question, answer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$faqId, $language, $question, $answer, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS
            . ' SET question = ?, answer = ?, updated_at = ? WHERE id = ?',
            [$question, $answer, $now, (int) $existing['id']]
        );
    }

    /**
     * Возвращает FAQ-элементы с локализациями.
     *
     * @return array<int, array<string, array{question:string, answer:string}>>
     */
    private function faqItems(): array
    {
        return [
            10 => [
                'en' => [
                    'question' => 'How is encoding different from encryption?',
                    'answer' => '<p>Encoding changes representation for transport or storage. Encryption protects data with keys.</p>',
                ],
                'ru' => [
                    'question' => 'Чем кодирование отличается от шифрования?',
                    'answer' => '<p>Кодирование меняет представление данных для передачи или хранения. Шифрование защищает данные с помощью ключей.</p>',
                ],
                'de' => [
                    'question' => 'Worin unterscheidet sich Kodierung von Verschlüsselung?',
                    'answer' => '<p>Kodierung ändert die Darstellung für Transport oder Speicherung. Verschlüsselung schützt Daten mit Schlüsseln.</p>',
                ],
                'es' => [
                    'question' => '¿En qué se diferencia la codificación del cifrado?',
                    'answer' => '<p>La codificación cambia la representación para transporte o almacenamiento. El cifrado protege los datos con claves.</p>',
                ],
                'fr' => [
                    'question' => 'Quelle est la différence entre encodage et chiffrement ?',
                    'answer' => '<p>L’encodage change la représentation pour le transport ou le stockage. Le chiffrement protège les données avec des clés.</p>',
                ],
                'it' => [
                    'question' => 'Qual è la differenza tra codifica e crittografia?',
                    'answer' => '<p>La codifica cambia la rappresentazione per trasporto o archiviazione. La crittografia protegge i dati con chiavi.</p>',
                ],
                'pt' => [
                    'question' => 'Qual é a diferença entre codificação e criptografia?',
                    'answer' => '<p>A codificação altera a representação para transporte ou armazenamento. A criptografia protege os dados com chaves.</p>',
                ],
                'tr' => [
                    'question' => 'Kodlama ile şifreleme arasındaki fark nedir?',
                    'answer' => '<p>Kodlama, verinin taşıma veya depolama için gösterimini değiştirir. Şifreleme, veriyi anahtarlarla korur.</p>',
                ],
            ],
            20 => [
                'en' => [
                    'question' => 'What is Base64?',
                    'answer' => '<p>Base64 represents binary data using ASCII text symbols and is often used in APIs and HTTP.</p>',
                ],
                'ru' => [
                    'question' => 'Что такое Base64?',
                    'answer' => '<p>Base64 представляет бинарные данные с помощью ASCII-символов и часто используется в API и HTTP.</p>',
                ],
                'de' => [
                    'question' => 'Was ist Base64?',
                    'answer' => '<p>Base64 stellt Binärdaten mit ASCII-Textzeichen dar und wird häufig in APIs und HTTP verwendet.</p>',
                ],
                'es' => [
                    'question' => '¿Qué es Base64?',
                    'answer' => '<p>Base64 representa datos binarios usando símbolos de texto ASCII y se usa con frecuencia en APIs y HTTP.</p>',
                ],
                'fr' => [
                    'question' => 'Qu’est-ce que Base64 ?',
                    'answer' => '<p>Base64 représente des données binaires à l’aide de symboles texte ASCII et est souvent utilisé dans les API et HTTP.</p>',
                ],
                'it' => [
                    'question' => 'Cos’è Base64?',
                    'answer' => '<p>Base64 rappresenta dati binari usando simboli di testo ASCII ed è spesso usato in API e HTTP.</p>',
                ],
                'pt' => [
                    'question' => 'O que é Base64?',
                    'answer' => '<p>Base64 representa dados binários usando símbolos de texto ASCII e é frequentemente usado em APIs e HTTP.</p>',
                ],
                'tr' => [
                    'question' => 'Base64 nedir?',
                    'answer' => '<p>Base64, ikili veriyi ASCII metin sembolleriyle temsil eder ve API ile HTTP’de sıkça kullanılır.</p>',
                ],
            ],
            30 => [
                'en' => [
                    'question' => 'What is Hex used for?',
                    'answer' => '<p>Hex represents each byte as two symbols and is useful for debugging and binary analysis.</p>',
                ],
                'ru' => [
                    'question' => 'Для чего используется Hex?',
                    'answer' => '<p>Hex представляет каждый байт двумя символами и полезен для отладки и бинарного анализа.</p>',
                ],
                'de' => [
                    'question' => 'Wofür wird Hex verwendet?',
                    'answer' => '<p>Hex stellt jedes Byte als zwei Zeichen dar und ist nützlich für Debugging und Binäranalyse.</p>',
                ],
                'es' => [
                    'question' => '¿Para qué se usa Hex?',
                    'answer' => '<p>Hex representa cada byte con dos símbolos y es útil para depuración y análisis binario.</p>',
                ],
                'fr' => [
                    'question' => 'À quoi sert Hex ?',
                    'answer' => '<p>Hex représente chaque octet avec deux symboles et est utile pour le débogage et l’analyse binaire.</p>',
                ],
                'it' => [
                    'question' => 'A cosa serve Hex?',
                    'answer' => '<p>Hex rappresenta ogni byte con due simboli ed è utile per debug e analisi binaria.</p>',
                ],
                'pt' => [
                    'question' => 'Para que serve Hex?',
                    'answer' => '<p>Hex representa cada byte com dois símbolos e é útil para depuração e análise binária.</p>',
                ],
                'tr' => [
                    'question' => 'Hex ne için kullanılır?',
                    'answer' => '<p>Hex, her baytı iki sembolle temsil eder ve hata ayıklama ile ikili analiz için yararlıdır.</p>',
                ],
            ],
            40 => [
                'en' => [
                    'question' => 'Can encoded data be restored?',
                    'answer' => '<p>Yes. Base64 and Hex are typically reversible when the input is valid.</p>',
                ],
                'ru' => [
                    'question' => 'Можно ли восстановить закодированные данные?',
                    'answer' => '<p>Да. Base64 и Hex обычно обратимы при корректных входных данных.</p>',
                ],
                'de' => [
                    'question' => 'Kann man kodierte Daten wiederherstellen?',
                    'answer' => '<p>Ja. Base64 und Hex sind bei gültigen Eingabedaten in der Regel umkehrbar.</p>',
                ],
                'es' => [
                    'question' => '¿Se pueden restaurar los datos codificados?',
                    'answer' => '<p>Sí. Base64 y Hex suelen ser reversibles cuando la entrada es válida.</p>',
                ],
                'fr' => [
                    'question' => 'Les données encodées peuvent-elles être restaurées ?',
                    'answer' => '<p>Oui. Base64 et Hex sont généralement réversibles lorsque l’entrée est valide.</p>',
                ],
                'it' => [
                    'question' => 'I dati codificati possono essere ripristinati?',
                    'answer' => '<p>Sì. Base64 e Hex sono in genere reversibili quando l’input è valido.</p>',
                ],
                'pt' => [
                    'question' => 'Os dados codificados podem ser restaurados?',
                    'answer' => '<p>Sim. Base64 e Hex normalmente são reversíveis quando a entrada é válida.</p>',
                ],
                'tr' => [
                    'question' => 'Kodlanmış veri geri alınabilir mi?',
                    'answer' => '<p>Evet. Girdi geçerliyse Base64 ve Hex genellikle tersine çevrilebilir.</p>',
                ],
            ],
            50 => [
                'en' => [
                    'question' => 'Is it safe to store passwords in Base64 or Hex?',
                    'answer' => '<p>No. Base64 and Hex are not secure storage methods for passwords.</p>',
                ],
                'ru' => [
                    'question' => 'Безопасно ли хранить пароли в Base64 или Hex?',
                    'answer' => '<p>Нет. Base64 и Hex не являются безопасными методами хранения паролей.</p>',
                ],
                'de' => [
                    'question' => 'Ist es sicher, Passwörter in Base64 oder Hex zu speichern?',
                    'answer' => '<p>Nein. Base64 und Hex sind keine sicheren Speichermethoden für Passwörter.</p>',
                ],
                'es' => [
                    'question' => '¿Es seguro guardar contraseñas en Base64 o Hex?',
                    'answer' => '<p>No. Base64 y Hex no son métodos seguros para almacenar contraseñas.</p>',
                ],
                'fr' => [
                    'question' => 'Est-il sûr de stocker des mots de passe en Base64 ou Hex ?',
                    'answer' => '<p>Non. Base64 et Hex ne sont pas des méthodes de stockage sécurisées pour les mots de passe.</p>',
                ],
                'it' => [
                    'question' => 'È sicuro memorizzare password in Base64 o Hex?',
                    'answer' => '<p>No. Base64 e Hex non sono metodi sicuri per archiviare password.</p>',
                ],
                'pt' => [
                    'question' => 'É seguro armazenar senhas em Base64 ou Hex?',
                    'answer' => '<p>Não. Base64 e Hex não são métodos seguros para armazenar senhas.</p>',
                ],
                'tr' => [
                    'question' => 'Parolaları Base64 veya Hex ile saklamak güvenli midir?',
                    'answer' => '<p>Hayır. Base64 ve Hex, parolalar için güvenli saklama yöntemleri değildir.</p>',
                ],
            ],
        ];
    }
}
