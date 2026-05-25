<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет популярные задачи для категории encoding и их локализации.
 */
class SeedEncodingCategoryTasks extends Migration
{
    /**
     * Создаёт или обновляет задачи и переводы для категории encoding.
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
        $tasks = $this->taskDefinitions();

        foreach ($tasks as $sortOrder => $task) {
            $cipher = $this->db->fetch(
                'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
                [$categoryId, $task['cipher_alias']]
            );

            if ($cipher === false) {
                continue;
            }

            $relationCipherId = (int) $cipher['id'];
            $taskId = $this->upsertTask($categoryId, $relationCipherId, $sortOrder, $now);

            foreach ($task['translations'] as $language => $translation) {
                $this->upsertTaskTranslation(
                    $taskId,
                    $language,
                    (string) $translation['title'],
                    (string) $translation['description'],
                    $now
                );
            }
        }
    }

    /**
     * Удаляет задачи, добавленные данной миграцией, для категории encoding.
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
        $aliases = array_map(static fn (array $task): string => $task['cipher_alias'], $this->taskDefinitions());

        if ($aliases === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($aliases), '?'));
        $bindings = array_merge([$categoryId], $aliases);

        $rows = $this->db->fetchAll(
            'SELECT t.id FROM ' . Tables::CIPHERS_CATEGORIES_TASKS . ' t '
            . 'INNER JOIN ' . Tables::CIPHERS . ' c ON c.id = t.relation_cipher_id '
            . 'WHERE t.category_id = ? AND c.alias IN (' . $placeholders . ')',
            $bindings
        );

        foreach ($rows as $row) {
            $this->db->execute(
                'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_TASKS . ' WHERE id = ?',
                [(int) $row['id']]
            );
        }
    }

    /**
     * Создаёт или обновляет задачу категории.
     */
    private function upsertTask(int $categoryId, int $relationCipherId, int $sortOrder, string $now): int
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_TASKS . ' WHERE category_id = ? AND relation_cipher_id = ? LIMIT 1',
            [$categoryId, $relationCipherId]
        );

        if ($existing === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_TASKS . ' (category_id, relation_cipher_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$categoryId, $relationCipherId, $sortOrder, 1, $now, $now]
            );
        }

        $taskId = (int) $existing['id'];

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_TASKS . ' SET sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            [$sortOrder, 1, $now, $taskId]
        );

        return $taskId;
    }

    /**
     * Создаёт или обновляет перевод задачи.
     */
    private function upsertTaskTranslation(int $taskId, string $language, string $title, string $description, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS . ' WHERE task_id = ? AND language = ? LIMIT 1',
            [$taskId, $language]
        );

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS . ' (task_id, language, title, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$taskId, $language, $title, $description, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS . ' SET title = ?, description = ?, updated_at = ? WHERE id = ?',
            [$title, $description, $now, (int) $existing['id']]
        );
    }

    /**
     * Возвращает набор задач и локализованных текстов.
     *
     * @return array<int, array{
     *   cipher_alias:string,
     *   translations: array<string, array{title:string, description:string}>
     * }>
     */
    private function taskDefinitions(): array
    {
        return [
            10 => [
                'cipher_alias' => 'base64',
                'translations' => [
                    'en' => [
                        'title' => 'Encode API and JSON payloads',
                        'description' => 'Use Base64 when you need text-safe transport for binary or service payloads.',
                    ],
                    'ru' => [
                        'title' => 'Кодирование API и JSON payload',
                        'description' => 'Используйте Base64, когда нужен безопасный для текста транспорт бинарных данных или сервисных payload.',
                    ],
                    'de' => [
                        'title' => 'API- und JSON-Payloads kodieren',
                        'description' => 'Nutzen Sie Base64, wenn Sie einen texttauglichen Transport für Binärdaten oder Service-Payloads benötigen.',
                    ],
                    'es' => [
                        'title' => 'Codificar payloads de API y JSON',
                        'description' => 'Usa Base64 cuando necesites transporte seguro en texto para datos binarios o payloads de servicios.',
                    ],
                    'fr' => [
                        'title' => 'Encoder les payloads API et JSON',
                        'description' => 'Utilisez Base64 lorsque vous avez besoin d’un transport sûr en texte pour des données binaires ou des payloads de service.',
                    ],
                    'it' => [
                        'title' => 'Codifica payload API e JSON',
                        'description' => 'Usa Base64 quando hai bisogno di un trasporto sicuro in formato testo per dati binari o payload di servizio.',
                    ],
                    'pt' => [
                        'title' => 'Codificar payloads de API e JSON',
                        'description' => 'Use Base64 quando precisar de transporte seguro em texto para dados binários ou payloads de serviço.',
                    ],
                    'tr' => [
                        'title' => 'API ve JSON payload kodlama',
                        'description' => 'İkili veriler veya servis payloadları için metin uyumlu taşıma gerektiğinde Base64 kullanın.',
                    ],
                ],
            ],
            20 => [
                'cipher_alias' => 'url-encode',
                'translations' => [
                    'en' => [
                        'title' => 'Safe query parameter transport',
                        'description' => 'Encode special characters for stable URLs, redirects, and HTTP requests.',
                    ],
                    'ru' => [
                        'title' => 'Безопасная передача query-параметров',
                        'description' => 'Кодируйте специальные символы для стабильных URL, редиректов и HTTP-запросов.',
                    ],
                    'de' => [
                        'title' => 'Sicherer Transport von Query-Parametern',
                        'description' => 'Kodieren Sie Sonderzeichen für stabile URLs, Redirects und HTTP-Anfragen.',
                    ],
                    'es' => [
                        'title' => 'Transporte seguro de parámetros query',
                        'description' => 'Codifica caracteres especiales para URLs estables, redirecciones y solicitudes HTTP.',
                    ],
                    'fr' => [
                        'title' => 'Transport sûr des paramètres de requête',
                        'description' => 'Encodez les caractères spéciaux pour des URL stables, des redirections et des requêtes HTTP.',
                    ],
                    'it' => [
                        'title' => 'Trasporto sicuro dei parametri query',
                        'description' => 'Codifica i caratteri speciali per URL stabili, redirect e richieste HTTP.',
                    ],
                    'pt' => [
                        'title' => 'Transporte seguro de parâmetros query',
                        'description' => 'Codifique caracteres especiais para URLs estáveis, redirecionamentos e requisições HTTP.',
                    ],
                    'tr' => [
                        'title' => 'Query parametrelerini güvenli taşıma',
                        'description' => 'Kararlı URL, yönlendirme ve HTTP istekleri için özel karakterleri kodlayın.',
                    ],
                ],
            ],
            30 => [
                'cipher_alias' => 'jwt-decoder',
                'translations' => [
                    'en' => [
                        'title' => 'Inspect JWT header/payload/claims',
                        'description' => 'Decode token structure quickly during auth and API debugging workflows.',
                    ],
                    'ru' => [
                        'title' => 'Проверка JWT header/payload/claims',
                        'description' => 'Быстро декодируйте структуру токена во время отладки auth и API сценариев.',
                    ],
                    'de' => [
                        'title' => 'JWT Header/Payload/Claims prüfen',
                        'description' => 'Dekodieren Sie die Token-Struktur schnell während Auth- und API-Debugging-Workflows.',
                    ],
                    'es' => [
                        'title' => 'Inspeccionar JWT header/payload/claims',
                        'description' => 'Decodifica rápidamente la estructura del token durante flujos de depuración de auth y API.',
                    ],
                    'fr' => [
                        'title' => 'Inspecter JWT header/payload/claims',
                        'description' => 'Décodez rapidement la structure du token pendant les workflows de débogage auth et API.',
                    ],
                    'it' => [
                        'title' => 'Ispeziona JWT header/payload/claims',
                        'description' => 'Decodifica rapidamente la struttura del token durante i flussi di debug auth e API.',
                    ],
                    'pt' => [
                        'title' => 'Inspecionar JWT header/payload/claims',
                        'description' => 'Decodifique rapidamente a estrutura do token durante fluxos de depuração de auth e API.',
                    ],
                    'tr' => [
                        'title' => 'JWT header/payload/claims inceleme',
                        'description' => 'Auth ve API hata ayıklama süreçlerinde token yapısını hızlıca çözümleyin.',
                    ],
                ],
            ],
            40 => [
                'cipher_alias' => 'hex',
                'translations' => [
                    'en' => [
                        'title' => 'Analyze bytes and hex strings',
                        'description' => 'Work with low-level byte sequences and protocol fragments in readable form.',
                    ],
                    'ru' => [
                        'title' => 'Анализ байтов и hex-строк',
                        'description' => 'Работайте с низкоуровневыми последовательностями байтов и фрагментами протоколов в читаемом виде.',
                    ],
                    'de' => [
                        'title' => 'Bytes und Hex-Strings analysieren',
                        'description' => 'Arbeiten Sie mit Byte-Sequenzen niedriger Ebene und Protokollfragmenten in lesbarer Form.',
                    ],
                    'es' => [
                        'title' => 'Analizar bytes y cadenas hex',
                        'description' => 'Trabaja con secuencias de bytes de bajo nivel y fragmentos de protocolo en formato legible.',
                    ],
                    'fr' => [
                        'title' => 'Analyser les octets et chaînes hex',
                        'description' => 'Travaillez avec des séquences d’octets bas niveau et des fragments de protocole sous une forme lisible.',
                    ],
                    'it' => [
                        'title' => 'Analizza byte e stringhe hex',
                        'description' => 'Lavora con sequenze di byte a basso livello e frammenti di protocollo in forma leggibile.',
                    ],
                    'pt' => [
                        'title' => 'Analisar bytes e strings hex',
                        'description' => 'Trabalhe com sequências de bytes de baixo nível e fragmentos de protocolo em formato legível.',
                    ],
                    'tr' => [
                        'title' => 'Bayt ve hex dizelerini analiz etme',
                        'description' => 'Düşük seviyeli bayt dizileri ve protokol parçalarıyla okunabilir biçimde çalışın.',
                    ],
                ],
            ],
        ];
    }
}
