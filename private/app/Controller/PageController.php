<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Repository\SystemPageRepository;
use App\View\View;

/**
 * Контроллер системных страниц (Privacy Policy и др.).
 */
final readonly class PageController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private View     $view,
        private SystemPageRepository $pages
    ) {
    }

    /**
     * Отображает системную страницу по алиасу и текущей локали.
     */
    public function show(Request $request): Response
    {
        $alias    = $request->route('alias', '');
        $language = locale();

        $page = $this->pages->findPublishedByAliasAndLanguage((string) $alias, $language);

        // Запасной вариант: попытка получить страницу на языке по умолчанию
        if ($page === null) {
            $page = $this->pages->findPublishedByAlias((string) $alias);
        }

        if ($page === null) {
            $this->view
                ->setTitle(trans('ERROR_404_TITLE'))
                ->setContent($this->view->fetch('errors/404.tpl'));

            return new Response($this->view->render(), 404);
        }

        $this->view
            ->setTitle($page['name'])
            ->setContent($this->view->fetch('page/show.tpl', ['page' => $page]));

        return new Response($this->view->render());
    }
}
