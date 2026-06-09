<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cache\CacheInterface;
use App\Http\Request;
use App\Http\Response;
use App\I18n\Translator;
use App\Repository\CipherCategoryRepository;
use App\Repository\CipherRepository;

/**
 * Контроллер машинно-читаемого описания сайта для LLM-агентов.
 */
final readonly class LlmsController
{
    private const int CACHE_TTL = 3600;

    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private CipherCategoryRepository $categories,
        private CipherRepository         $ciphers,
        private Translator               $translator,
        private CacheInterface           $cache,
    ) {
    }

    /**
     * Возвращает llms.txt с основными публичными страницами сайта.
     */
    public function index(Request $request): Response
    {
        $content = $this->cache->remember('llms.txt.content', self::CACHE_TTL, fn (): string => $this->buildContent());

        return new Response($content, 200, [
            'Content-Type'  => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=' . self::CACHE_TTL,
        ]);
    }

    /**
     * Формирует Markdown-содержимое llms.txt.
     */
    private function buildContent(): string
    {
        $appUrl          = rtrim((string) config('app.url', ''), '/');
        $defaultLanguage = $this->translator->getDefaultLocale();
        $locales         = $this->translator->getLocales();
        $categories      = $this->categories->findPublishedCategoriesForHome($defaultLanguage, $defaultLanguage);

        $lines = [
            '# CiphersOnline',
            '',
            '> Educational and utility website for classical ciphers, encoding, decoding, and text transformation tools.',
            '',
            'CiphersOnline provides browser and API-assisted tools for learning, testing, encrypting, decrypting, encoding, and decoding text. Classical ciphers on this site are for education, experiments, and historical understanding, not for protecting sensitive information.',
            '',
            'Primary language for this file: English.',
            'Available website locales: ' . implode(', ', $locales),
            '',
            '## Core Pages',
            '',
            $this->linkLine('Home', $appUrl . '/', 'Main entry point for cipher and encoding tools.'),
            $this->linkLine('HTML Sitemap', $appUrl . '/sitemap', 'Human-readable list of published categories and tools.'),
            $this->linkLine('XML Sitemap', $appUrl . '/sitemap.xml', 'Complete crawl map with locale alternates.'),
            $this->linkLine('Contact', $appUrl . '/contacts', 'Contact page for site feedback and support requests.'),
            '',
            '## Tool Categories',
            '',
        ];

        foreach ($categories as $category) {
            $categoryAlias = (string) $category['alias'];
            $categoryName  = $this->plainText((string) ($category['name'] ?? $categoryAlias));
            $description   = $this->plainText((string) ($category['description'] ?? ''));

            $lines[] = $this->linkLine($categoryName, $appUrl . '/' . $categoryAlias, $description);
        }

        foreach ($categories as $category) {
            $categoryAlias = (string) $category['alias'];
            $categoryName  = $this->plainText((string) ($category['name'] ?? $categoryAlias));
            $tools         = $this->ciphers->findPublishedByCategoryWithTranslation(
                (int) $category['id'],
                $defaultLanguage,
                $defaultLanguage,
            );

            if ($tools === []) {
                continue;
            }

            $lines[] = '';
            $lines[] = '## ' . $categoryName . ' Tools';
            $lines[] = '';

            foreach ($tools as $tool) {
                $name        = $this->plainText((string) ($tool['name_short'] ?? $tool['name'] ?? $tool['alias']));
                $description = $this->plainText((string) ($tool['description_short'] ?? $tool['description'] ?? ''));

                $lines[] = $this->linkLine($name, $appUrl . '/' . $categoryAlias . '/' . $tool['alias'], $description);
            }
        }

        $lines[] = '';
        $lines[] = '## Usage Notes';
        $lines[] = '';
        $lines[] = '- Prefer canonical English URLs in this file for concise context.';
        $lines[] = '- Use `/sitemap.xml` for the full multilingual URL set and alternates.';
        $lines[] = '- Do not submit passwords, private keys, production tokens, or confidential data to tools.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Создаёт строку Markdown-ссылки с необязательным описанием.
     */
    private function linkLine(string $label, string $url, string $description = ''): string
    {
        $line = '- [' . $this->escapeMarkdown($label) . '](' . $url . ')';

        if ($description !== '') {
            $line .= ': ' . $description;
        }

        return $line;
    }

    /**
     * Приводит HTML и лишние пробелы к короткому plain text.
     */
    private function plainText(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * Экранирует спецсимволы Markdown внутри текста ссылки.
     */
    private function escapeMarkdown(string $value): string
    {
        return str_replace([']', '['], ['\]', '\['], $value);
    }
}
