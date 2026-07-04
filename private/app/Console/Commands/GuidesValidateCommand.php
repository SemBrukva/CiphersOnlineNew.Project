<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;

/**
 * Проверяет JSON-файлы гайдов: соответствие схеме, полноту перевода и корректность связей.
 */
final readonly class GuidesValidateCommand implements CommandInterface
{
    /** Допустимые категории статей. */
    private const array CATEGORIES = ['how-to', 'deep-dive', 'history', 'lists'];

    /** Допустимые статусы статьи. */
    private const array STATUSES = ['draft', 'published'];

    /**
     * Выполняет проверку файлов гайдов.
     *
     * @param  string[] $args Аргументы команды.
     * @return int            Код завершения: 0 — успех, 1 — есть ошибки.
     */
    public function handle(array $args): int
    {
        $base = STORAGE_PATH . '/content/guides';
        /** @var string[] $locales */
        $locales = (array) config('locale.locales', ['en']);
        $default = (string) config('locale.locale', 'en');

        echo 'Guides content: ' . $base . PHP_EOL;

        if (!is_dir($base)) {
            echo 'No guides directory — nothing to validate.' . PHP_EOL;
            return 0;
        }

        $guideSlugs = array_map('basename', glob($base . '/*', GLOB_ONLYDIR) ?: []);
        $toolSlugs  = $this->collectToolSlugs();
        $termSlugs  = $this->collectTermSlugs();

        $issues = [];
        $relatedGuideRefs = [];
        $relatedToolRefs = [];
        $relatedTermRefs = [];

        foreach ($guideSlugs as $slug) {
            if (preg_match('/^[a-z0-9-]+$/', $slug) !== 1) {
                $issues[] = "{$slug}: invalid slug (allowed: a-z, 0-9, -)";
            }

            foreach ($locales as $locale) {
                $file = "{$base}/{$slug}/{$locale}.json";
                $rel  = "guides/{$slug}/{$locale}.json";

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

                foreach ((array) ($data['related_guides'] ?? []) as $ref) {
                    $relatedGuideRefs[(string) $ref][] = $rel;
                }
                foreach ((array) ($data['related_tools'] ?? []) as $tool) {
                    if (is_array($tool) && isset($tool['slug'])) {
                        $relatedToolRefs[(string) $tool['slug']][] = $rel;
                    }
                }
                foreach ((array) ($data['related_terms'] ?? []) as $term) {
                    $ref = is_array($term) ? (string) ($term['slug'] ?? '') : (string) $term;
                    if ($ref !== '') {
                        $relatedTermRefs[$ref][] = $rel;
                    }
                }
            }
        }

        // Битые ссылки на другие статьи.
        foreach ($relatedGuideRefs as $ref => $sources) {
            if (!in_array($ref, $guideSlugs, true)) {
                $issues[] = "related_guides → '{$ref}' does not exist (referenced in " . $sources[0] . ')';
            }
        }

        // Битые ссылки на инструменты.
        foreach ($relatedToolRefs as $ref => $sources) {
            if (!in_array($ref, $toolSlugs, true)) {
                $issues[] = "related_tools → '{$ref}' is not a known tool (referenced in " . $sources[0] . ')';
            }
        }

        // Битые ссылки на термины глоссария (мягкая проверка — только если глоссарий есть).
        if ($termSlugs !== []) {
            foreach ($relatedTermRefs as $ref => $sources) {
                if (!in_array($ref, $termSlugs, true)) {
                    $issues[] = "related_terms → '{$ref}' is not a known glossary term (referenced in " . $sources[0] . ')';
                }
            }
        }

        echo 'Guides: ' . count($guideSlugs) . PHP_EOL;
        echo 'Locales: ' . implode(', ', $locales) . " (default: {$default})" . PHP_EOL;

        if ($issues === []) {
            echo PHP_EOL . 'OK: guides are valid.' . PHP_EOL;
            return 0;
        }

        echo PHP_EOL . 'Issues (' . count($issues) . '):' . PHP_EOL;
        foreach ($issues as $issue) {
            echo '  - ' . $issue . PHP_EOL;
        }

        return 1;
    }

    /**
     * Проверяет структуру одного файла статьи.
     *
     * @param  array<string, mixed> $data
     * @return string[]             Список сообщений об ошибках.
     */
    private function validateStructure(string $rel, string $slug, string $locale, array $data): array
    {
        $issues = [];
        $meta  = (array) ($data['meta'] ?? []);
        $guide = (array) ($data['guide'] ?? []);

        if (($meta['schema'] ?? '') !== 'guide-article.v1') {
            $issues[] = "{$rel}: meta.schema must be 'guide-article.v1'";
        }
        if (($meta['guide_slug'] ?? '') !== $slug) {
            $issues[] = "{$rel}: meta.guide_slug must equal folder name '{$slug}'";
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
        if (trim((string) ($guide['title'] ?? '')) === '') {
            $issues[] = "{$rel}: guide.title is required";
        }
        if (trim((string) ($guide['excerpt'] ?? '')) === '') {
            $issues[] = "{$rel}: guide.excerpt is required";
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
            if ($category === 'glossary' || $category === 'guides') {
                continue;
            }
            foreach (glob($categoryDir . '/*', GLOB_ONLYDIR) ?: [] as $toolDir) {
                $slugs[] = $category . '/' . basename($toolDir);
            }
        }

        return $slugs;
    }

    /**
     * Собирает slug'и существующих терминов глоссария (имена подкаталогов).
     *
     * @return string[]
     */
    private function collectTermSlugs(): array
    {
        $root = STORAGE_PATH . '/content/glossary';
        if (!is_dir($root)) {
            return [];
        }

        return array_map('basename', glob($root . '/*', GLOB_ONLYDIR) ?: []);
    }
}
