<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет связки "часто используют вместе" для категории encoding и их локализации.
 */
class SeedEncodingCategoryUsedTogether extends Migration
{
    /**
     * Создаёт или обновляет связки used together и их переводы.
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

        foreach ($this->pairs() as $sortOrder => $pair) {
            $first = $this->findCipherId($categoryId, $pair['first_alias']);
            $second = $this->findCipherId($categoryId, $pair['second_alias']);

            if ($first === null || $second === null) {
                continue;
            }

            $usedTogetherId = $this->upsertUsedTogether($categoryId, $first, $second, $sortOrder, $now);

            foreach ($pair['translations'] as $language => $title) {
                $this->upsertTranslation($usedTogetherId, $language, $title, $now);
            }
        }
    }

    /**
     * Удаляет добавленные связки для категории encoding.
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

        foreach ($this->pairs() as $pair) {
            $first = $this->findCipherId($categoryId, $pair['first_alias']);
            $second = $this->findCipherId($categoryId, $pair['second_alias']);

            if ($first === null || $second === null) {
                continue;
            }

            $row = $this->db->fetch(
                'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER
                . ' WHERE category_id = ? AND relation_cipher_first_id = ? AND relation_cipher_second_id = ? LIMIT 1',
                [$categoryId, $first, $second]
            );

            if ($row === false) {
                continue;
            }

            $this->db->execute(
                'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER . ' WHERE id = ?',
                [(int) $row['id']]
            );
        }
    }

    /**
     * Возвращает ID шифра по alias в пределах категории.
     */
    private function findCipherId(int $categoryId, string $alias): ?int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, $alias]
        );

        return $row === false ? null : (int) $row['id'];
    }

    /**
     * Создаёт или обновляет запись used together.
     */
    private function upsertUsedTogether(int $categoryId, int $firstId, int $secondId, int $sortOrder, string $now): int
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER
            . ' WHERE category_id = ? AND relation_cipher_first_id = ? AND relation_cipher_second_id = ? LIMIT 1',
            [$categoryId, $firstId, $secondId]
        );

        if ($existing === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER
                . ' (category_id, relation_cipher_first_id, relation_cipher_second_id, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, $firstId, $secondId, $sortOrder, 1, $now, $now]
            );
        }

        $id = (int) $existing['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER . ' SET sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            [$sortOrder, 1, $now, $id]
        );

        return $id;
    }

    /**
     * Создаёт или обновляет перевод связки.
     */
    private function upsertTranslation(int $usedTogetherId, string $language, string $title, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS
            . ' WHERE used_together_id = ? AND language = ? LIMIT 1',
            [$usedTogetherId, $language]
        );

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS
                . ' (used_together_id, language, title, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [$usedTogetherId, $language, $title, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS . ' SET title = ?, updated_at = ? WHERE id = ?',
            [$title, $now, (int) $existing['id']]
        );
    }

    /**
     * Возвращает связки с переводами.
     *
     * @return array<int, array{
     *   first_alias:string,
     *   second_alias:string,
     *   translations: array<string, string>
     * }>
     */
    private function pairs(): array
    {
        return [
            10 => [
                'first_alias' => 'base64',
                'second_alias' => 'hex',
                'translations' => [
                    'en' => 'Move between text-safe Base64 and byte-level hex format for protocol debugging.',
                    'ru' => 'Переходите между текстобезопасным Base64 и побайтовым hex-форматом для отладки протоколов.',
                    'de' => 'Wechseln Sie zwischen texttauglichem Base64 und Hex-Format auf Byte-Ebene für das Protokoll-Debugging.',
                    'es' => 'Cambia entre Base64 seguro para texto y formato hex a nivel de bytes para depuración de protocolos.',
                    'fr' => 'Passez entre Base64 compatible texte et format hexadécimal au niveau octet pour le débogage de protocoles.',
                    'it' => 'Passa tra Base64 sicuro per testo e formato hex a livello di byte per il debug dei protocolli.',
                    'pt' => 'Alterne entre Base64 seguro para texto e formato hex em nível de byte para depuração de protocolos.',
                    'tr' => 'Protokol hata ayıklaması için metin uyumlu Base64 ile bayt düzeyindeki hex formatı arasında geçiş yapın.',
                ],
            ],
            20 => [
                'first_alias' => 'hex',
                'second_alias' => 'binary-converter',
                'translations' => [
                    'en' => 'Represent binary structures through hex before detailed bit-level analysis.',
                    'ru' => 'Представляйте бинарные структуры через hex перед детальным побитовым анализом.',
                    'de' => 'Stellen Sie binäre Strukturen zunächst als Hex dar, bevor Sie eine detaillierte Bit-Analyse durchführen.',
                    'es' => 'Representa estructuras binarias mediante hex antes de un análisis detallado a nivel de bits.',
                    'fr' => 'Représentez les structures binaires en hexadécimal avant une analyse détaillée au niveau des bits.',
                    'it' => 'Rappresenta le strutture binarie in hex prima di un’analisi dettagliata a livello di bit.',
                    'pt' => 'Represente estruturas binárias em hex antes da análise detalhada em nível de bit.',
                    'tr' => 'Ayrıntılı bit düzeyi analizden önce ikili yapıları hex üzerinden temsil edin.',
                ],
            ],
            30 => [
                'first_alias' => 'jwt-decoder',
                'second_alias' => 'base64',
                'translations' => [
                    'en' => 'Inspect token sections and verify Base64URL payload interpretation in auth pipelines.',
                    'ru' => 'Проверяйте секции токена и верифицируйте интерпретацию Base64URL payload в auth-пайплайнах.',
                    'de' => 'Prüfen Sie Token-Abschnitte und verifizieren Sie die Base64URL-Payload-Interpretation in Auth-Pipelines.',
                    'es' => 'Inspecciona secciones del token y verifica la interpretación del payload Base64URL en flujos de autenticación.',
                    'fr' => 'Inspectez les sections du token et vérifiez l’interprétation du payload Base64URL dans les pipelines d’authentification.',
                    'it' => 'Ispeziona le sezioni del token e verifica l’interpretazione del payload Base64URL nelle pipeline di autenticazione.',
                    'pt' => 'Inspecione seções do token e verifique a interpretação do payload Base64URL em pipelines de autenticação.',
                    'tr' => 'Auth süreçlerinde token bölümlerini inceleyin ve Base64URL payload yorumunu doğrulayın.',
                ],
            ],
            40 => [
                'first_alias' => 'url-encode',
                'second_alias' => 'base64',
                'translations' => [
                    'en' => 'Prepare Base64 data for query parameters and callback URLs in web integrations.',
                    'ru' => 'Подготавливайте данные Base64 для query-параметров и callback URL в веб-интеграциях.',
                    'de' => 'Bereiten Sie Base64-Daten für Query-Parameter und Callback-URLs in Web-Integrationen vor.',
                    'es' => 'Prepara datos Base64 para parámetros query y URLs de callback en integraciones web.',
                    'fr' => 'Préparez les données Base64 pour les paramètres de requête et les URL de callback dans les intégrations web.',
                    'it' => 'Prepara i dati Base64 per parametri query e URL di callback nelle integrazioni web.',
                    'pt' => 'Prepare dados Base64 para parâmetros de consulta e URLs de callback em integrações web.',
                    'tr' => 'Web entegrasyonlarında query parametreleri ve callback URL’leri için Base64 verisini hazırlayın.',
                ],
            ],
        ];
    }
}
