<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\View\View;

/**
 * Контроллер личного кабинета авторизованного пользователя.
 */
final readonly class CabinetController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(private View $view)
    {
    }

    /**
     * Отображает личный кабинет текущего пользователя.
     */
    public function index(Request $request): Response
    {
        $this->view
            ->setTitle(trans('CABINET_TITLE'))
            ->setContent($this->view->fetch('cabinet/index.tpl'));

        return new Response($this->view->render());
    }
}
