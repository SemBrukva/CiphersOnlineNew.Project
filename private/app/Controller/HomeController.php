<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\View\View;

/**
 * Контроллер главной страницы приложения.
 */
final readonly class HomeController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(private View $view)
    {
    }

    /**
     * Отображает главную страницу.
     */
    public function index(Request $request): Response
    {
        $this->view
            ->setTitle(trans('HOME_HEADING'))
            ->setContent($this->view->fetch('home/index.tpl', [
                'heading' => trans('HOME_HEADING'),
            ]));

        return new Response($this->view->render());
    }
}
