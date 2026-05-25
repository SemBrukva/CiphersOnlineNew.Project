<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Repository\CipherCategoryRepository;
use App\Repository\CipherRepository;
use App\View\View;

/**
 * Контроллер публичных страниц категорий шифров.
 */
final readonly class CipherCategoryController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private View $view,
        private CipherCategoryRepository $categories,
        private CipherRepository $ciphers
    ) {}

    /**
     * Отображает страницу категории по alias и текущей локали.
     */
    public function show(Request $request): Response
    {
        $alias = (string) $request->route('alias', '');
        $language = locale();

        $category = $this->categories->findPublishedCategoryPageByAliasAndLanguage($alias, $language);

        if ($category === null) {
            $category = $this->categories->findPublishedCategoryPageByAlias($alias);
        }

        if ($category === null) {
            $this->view
                ->setTitle(trans('ERROR_404_TITLE'))
                ->setContent($this->view->fetch('errors/404.tpl'));

            return new Response($this->view->render(), 404);
        }

        $title = (string) ($category['name'] ?? $category['alias']);
        $metaDescription = (string) ($category['meta_description'] ?? '');

        $defaultLanguage = (string) config('locale.locale', 'en');
        $tools = $this->ciphers->findPublishedByCategoryWithTranslation(
            (int) $category['id'],
            $language,
            $defaultLanguage
        );
        $blocks = $this->categories->findBlocksByCategoryIdWithTranslation(
            (int) $category['id'],
            $language,
            $defaultLanguage
        );
        $tasks = $this->categories->findTasksByCategoryIdWithTranslationAndCipher(
            (int) $category['id'],
            $language,
            $defaultLanguage
        );
        $usedTogether = $this->categories->findUsedTogetherByCategoryIdWithTranslationAndCiphers(
            (int) $category['id'],
            $language,
            $defaultLanguage
        );

        $cipherIds = array_map(static fn (array $t) => (int) $t['id'], $tools);
        $tagsByCipher = $this->ciphers->findTagsGroupedByCipherIds($cipherIds, $language, $defaultLanguage);

        foreach ($tools as &$tool) {
            $tool['tags'] = $tagsByCipher[(int) $tool['id']] ?? [];
        }
        unset($tool);

        foreach ($tasks as &$task) {
            $task['icon'] = $this->iconForCipherAlias((string) ($task['cipher_alias'] ?? ''));
        }
        unset($task);

        $this->view
            ->setTitle($title)
            ->setMeta($metaDescription)
            ->setContent($this->view->fetch('cipher_category/show.tpl', [
                'category' => $category,
                'tools' => $tools,
                'blocks' => $blocks,
                'tasks' => $tasks,
                'used_together' => $usedTogether,
            ]));

        return new Response($this->view->render());
    }

    /**
     * Возвращает CSS-класс иконки по alias шифра.
     */
    private function iconForCipherAlias(string $alias): string
    {
        return match ($alias) {
            'base64' => 'fa-solid fa-code',
            'url-encode' => 'fa-solid fa-percent',
            'jwt-decoder' => 'fa-solid fa-key',
            'hex' => 'fa-solid fa-magnifying-glass',
            default => 'fa-solid fa-bolt',
        };
    }
}
