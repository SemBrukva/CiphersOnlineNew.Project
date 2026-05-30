<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\View\View;

/**
 * Контроллер страницы избранных сервисов пользователя.
 */
final readonly class FavoritesController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(private View $view)
    {
    }

    /**
     * Отображает страницу избранных инструментов.
     */
    public function index(Request $request): Response
    {
        $this->view
            ->setTitle(trans('FAVORITES_PAGE_TITLE'))
            ->setMeta(trans('FAVORITES_PAGE_META'))
            ->setBreadcrumbs([
                ['label' => trans('FAVORITES_PAGE_HEADING')],
            ])
            ->setContent($this->view->fetch('favorites/index.tpl'));

        return new Response($this->view->render());
    }
}
