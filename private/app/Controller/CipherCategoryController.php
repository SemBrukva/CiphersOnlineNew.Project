<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Repository\CipherCategoryRepository;
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
        private CipherCategoryRepository $categories
    ) {
    }

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

        $this->view
            ->setTitle($title)
            ->setMeta($metaDescription)
            ->setContent($this->view->fetch('cipher_category/show.tpl', [
                'category' => $category,
            ]));

        return new Response($this->view->render());
    }
}
