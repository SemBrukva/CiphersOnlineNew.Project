<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет постоянные редиректы со старых URL страниц шифров.
 */
class SeedLegacyCipherRedirects extends Migration
{
    /**
     * Создаёт или обновляет постоянные редиректы.
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->transaction(function () use ($now): void {
            foreach ($this->redirects() as [$fromPath, $toPath]) {
                $this->upsertRedirect($fromPath, $toPath, $now);
            }
        });
    }

    /**
     * Удаляет добавленные редиректы.
     */
    public function down(): void
    {
        $this->db->transaction(function (): void {
            foreach ($this->redirects() as [$fromPath]) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::REDIRECTS . ' WHERE from_path = ?',
                    [$fromPath]
                );
            }
        });
    }

    /**
     * Создаёт новый редирект или обновляет существующий.
     */
    private function upsertRedirect(string $fromPath, string $toPath, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::REDIRECTS . ' WHERE from_path = ? LIMIT 1',
            [$fromPath]
        );

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::REDIRECTS
                . ' (from_path, to_path, status_code, is_active, hit_count, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$fromPath, $toPath, 301, 1, 0, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::REDIRECTS
            . ' SET to_path = ?, status_code = ?, is_active = ?, updated_at = ? WHERE id = ?',
            [$toPath, 301, 1, $now, (int) $existing['id']]
        );
    }

    /**
     * Возвращает карту старых URL к новой структуре.
     *
     * @return array<int, array{0:string, 1:string}>
     */
    private function redirects(): array
    {
        return [
            ['/vigenere-cipher', '/classical-ciphers/vigenere'],
            ['/vernam-cipher', '/classical-ciphers/vernam'],
            ['/gronsfeld-cipher', '/classical-ciphers/gronsfeld'],
            ['/caesar-cipher', '/classical-ciphers/caesar'],
            ['/beaufort-cipher', '/classical-ciphers/beaufort'],
            ['/bacon-cipher', '/classical-ciphers/bacon'],
            ['/atbash-cipher', '/classical-ciphers/atbash'],
            ['/a1z26-cipher', '/classical-ciphers/a1z26'],
            ['/es/cifrado-vernam', '/es/classical-ciphers/vernam'],
            ['/es/cifrado-de-vigenere', '/es/classical-ciphers/vigenere'],
            ['/es/cifrado-gronsfeld', '/es/classical-ciphers/gronsfeld'],
            ['/es/cifrado-cesar', '/es/classical-ciphers/caesar'],
            ['/es/cifrado-beaufort', '/es/classical-ciphers/beaufort'],
            ['/es/cifrado-bacon', '/es/classical-ciphers/bacon'],
            ['/es/cifrado-atbash', '/es/classical-ciphers/atbash'],
            ['/es/cifrado-A1Z26', '/es/classical-ciphers/a1z26'],
            ['/pt/cifra-de-vigenere', '/pt/classical-ciphers/vigenere'],
            ['/pt/cifra-de-vernam', '/pt/classical-ciphers/vernam'],
            ['/pt/cifra-gronsfeld', '/pt/classical-ciphers/gronsfeld'],
            ['/pt/cifra-de-cesar', '/pt/classical-ciphers/caesar'],
            ['/pt/cifra-de-deaufort', '/pt/classical-ciphers/beaufort'],
            ['/pt/cifra-de-atbash', '/pt/classical-ciphers/atbash'],
            ['/pt/cifra-a1z26', '/pt/classical-ciphers/a1z26'],
            ['/ru/shifr-cezarya', '/ru/classical-ciphers/caesar'],
            ['/ru/shifr-gronsfelda', '/ru/classical-ciphers/gronsfeld'],
            ['/ru/shifr-vizhenera', '/ru/classical-ciphers/vigenere'],
            ['/ru/shifr-vernama', '/ru/classical-ciphers/vernam'],
            ['/ru/shifr-behkona', '/ru/classical-ciphers/bacon'],
            ['/ru/shifr-bofora', '/ru/classical-ciphers/beaufort'],
            ['/ru/shifr-atbash', '/ru/classical-ciphers/atbash'],
            ['/ru/shifr-a1z26', '/ru/classical-ciphers/a1z26'],
            ['/tr/vigenere-sifresi', '/tr/classical-ciphers/vigenere'],
            ['/tr/vernam-sifresi', '/tr/classical-ciphers/vernam'],
            ['/tr/sezar-sifresi', '/tr/classical-ciphers/caesar'],
            ['/tr/gronsfeld-sifresi', '/tr/classical-ciphers/gronsfeld'],
            ['/tr/beaufort-sifresi', '/tr/classical-ciphers/beaufort'],
            ['/tr/bacon-sifresi', '/tr/classical-ciphers/bacon'],
            ['/tr/atbash-sifresi', '/tr/classical-ciphers/atbash'],
            ['/tr/a1z26-sifresi', '/tr/classical-ciphers/a1z26'],
            ['/fr/chiffre-d-atbash', '/fr/classical-ciphers/atbash'],
            ['/fr/chiffre-de-vigenere', '/fr/classical-ciphers/vigenere'],
            ['/fr/chiffre-de-vernam', '/fr/classical-ciphers/vernam'],
            ['/fr/chiffre-gronsfeld', '/fr/classical-ciphers/gronsfeld'],
            ['/fr/chiffre-de-cesar', '/fr/classical-ciphers/caesar'],
            ['/fr/chiffre-de-beaufort', '/fr/classical-ciphers/beaufort'],
            ['/fr/chiffre-a1z26', '/fr/classical-ciphers/a1z26'],
            ['/de/vigenere-chiffre', '/de/classical-ciphers/vigenere'],
            ['/de/vernam-chiffre', '/de/classical-ciphers/vernam'],
            ['/de/gronsfeld-chiffre', '/de/classical-ciphers/gronsfeld'],
            ['/de/caesar-chiffre', '/de/classical-ciphers/caesar'],
            ['/de/beaufort-chiffre', '/de/classical-ciphers/beaufort'],
            ['/de/atbash-chiffre', '/de/classical-ciphers/atbash'],
            ['/de/a1z26-verschluesselung', '/de/classical-ciphers/a1z26'],
            ['/it/cifrario-di-vigenere', '/it/classical-ciphers/vigenere'],
            ['/it/cifrario-di-vernam', '/it/classical-ciphers/vernam'],
            ['/it/cifrario-gronsfeld', '/it/classical-ciphers/gronsfeld'],
            ['/it/cifrario-di-cesare', '/it/classical-ciphers/caesar'],
            ['/it/cifrario-di-beaufort', '/it/classical-ciphers/beaufort'],
            ['/it/cifrario-di-atbash', '/it/classical-ciphers/atbash'],
            ['/it/cifrario-a1z26', '/it/classical-ciphers/a1z26'],
        ];
    }
}
