<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;

/**
 * Проверяет JSON-файлы глоссария: соответствие схеме, полноту перевода и корректность связей.
 */
final readonly class GlossaryValidateCommand implements CommandInterface
{
    /** Допустимые категории терминов. */
    private const array CATEGORIES = ['concepts', 'classical', 'cryptanalysis', 'encoding', 'hashing'];

    /** Допустимые статусы термина. */
    private const array STATUSES = ['draft', 'published'];

    /**
     * Выполняет проверку файлов глоссария.
     *
     * @param  string[] $args Аргументы команды.
     * @return int            Код завершения: 0 — успех, 1 — есть ошибки.
     */
    public function handle(array $args): int
    {
        $base = STORAGE_PATH . '/content/glossary';
        /** @var string[] $locales */
        $locales = (array) config('locale.locales', ['en']);
        $default = (string) config('locale.locale', 'en');

        echo 'Glossary content: ' . $base . PHP_EOL;

        if (!is_dir($base)) {
            echo 'No glossary directory — nothing to validate.' . PHP_EOL;
            return 0;
        }

        $termSlugs = array_map('basename', glob($base . '/*', GLOB_ONLYDIR) ?: []);
        $toolSlugs = $this->collectToolSlugs();

        $issues = [];
        $relatedTermRefs = [];
        $relatedToolRefs = [];

        foreach ($termSlugs as $slug) {
            if (preg_match('/^[a-z0-9-]+$/', $slug) !== 1) {
                $issues[] = "{$slug}: invalid slug (allowed: a-z, 0-9, -)";
            }

            foreach ($locales as $locale) {
                $file = "{$base}/{$slug}/{$locale}.json";
                $rel  = "glossary/{$slug}/{$locale}.json";

                if (!is_file($file)) {
                    $issues[] = "{$rel}: missing translation (full translation is required for all locales)";
                    continue;
                }

                $data = json_decode((string) file_get_contents($file), true);
                if (!is_array($data)) {
                    $issues[] = "{$rel}: invalid JSON";
                    continue;
                }

                $issues = array_merge($issues, $this->validateStructure($rel, $slug, $locale, $data));

                foreach ((array) ($data['related_terms'] ?? []) as $ref) {
                    $relatedTermRefs[(string) $ref][] = $rel;
                }
                foreach ((array) ($data['related_tools'] ?? []) as $tool) {
                    if (is_array($tool) && isset($tool['slug'])) {
                        $relatedToolRefs[(string) $tool['slug']][] = $rel;
                    }
                }
            }
        }

        // Битые ссылки на другие термины.
        foreach ($relatedTermRefs as $ref => $sources) {
            if (!in_array($ref, $termSlugs, true)) {
                $issues[] = "related_terms → '{$ref}' does not exist (referenced in " . $sources[0] . ')';
            }
        }

        // Битые ссылки на инструменты.
        foreach ($relatedToolRefs as $ref => $sources) {
            if (!in_array($ref, $toolSlugs, true)) {
                $issues[] = "related_tools → '{$ref}' is not a known tool (referenced in " . $sources[0] . ')';
            }
        }

        echo 'Terms: ' . count($termSlugs) . PHP_EOL;
        echo 'Locales: ' . implode(', ', $locales) . " (default: {$default})" . PHP_EOL;

        if ($issues === []) {
            echo PHP_EOL . 'OK: glossary is valid.' . PHP_EOL;
            return 0;
        }

        echo PHP_EOL . 'Issues (' . count($issues) . '):' . PHP_EOL;
        foreach ($issues as $issue) {
            echo '  - ' . $issue . PHP_EOL;
        }

        return 1;
    }

    /**
     * Проверяет структуру одного файла термина.
     *
     * @param  array<string, mixed> $data
     * @return string[]             Список сообщений об ошибках.
     */
    private function validateStructure(string $rel, string $slug, string $locale, array $data): array
    {
        $issues = [];
        $meta = (array) ($data['meta'] ?? []);
        $term = (array) ($data['term'] ?? []);

        if (($meta['schema'] ?? '') !== 'glossary-term.v1') {
            $issues[] = "{$rel}: meta.schema must be 'glossary-term.v1'";
        }
        if (($meta['term_slug'] ?? '') !== $slug) {
            $issues[] = "{$rel}: meta.term_slug must equal folder name '{$slug}'";
        }
        if (($meta['language'] ?? '') !== $locale) {
            $issues[] = "{$rel}: meta.language must equal '{$locale}'";
        }
        if (!in_array((string) ($meta['status'] ?? ''), self::STATUSES, true)) {
            $issues[] = "{$rel}: meta.status must be one of " . implode('|', self::STATUSES);
        }
        if (!in_array((string) ($data['category'] ?? ''), self::CATEGORIES, true)) {
            $issues[] = "{$rel}: category must be one of " . implode('|', self::CATEGORIES);
        }
        if (trim((string) ($term['name'] ?? '')) === '') {
            $issues[] = "{$rel}: term.name is required";
        }
        if (trim((string) ($term['short'] ?? '')) === '') {
            $issues[] = "{$rel}: term.short is required";
        }
        if (($data['body'] ?? []) === [] || !is_array($data['body'] ?? null)) {
            $issues[] = "{$rel}: body must be a non-empty array";
        }

        return $issues;
    }

    /**
     * Собирает slug'и существующих инструментов вида «category/tool» из storage/content (без БД).
     *
     * @return string[]
     */
    private function collectToolSlugs(): array
    {
        $root = STORAGE_PATH . '/content';
        $slugs = [];

        foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $categoryDir) {
            $category = basename($categoryDir);
            if ($category === 'glossary') {
                continue;
            }
            foreach (glob($categoryDir . '/*', GLOB_ONLYDIR) ?: [] as $toolDir) {
                $slugs[] = $category . '/' . basename($toolDir);
            }
        }

        return $slugs;
    }
}
