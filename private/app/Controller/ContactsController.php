<?php

declare(strict_types=1);

namespace App\Controller;

use App\Auth\Auth;
use App\Http\Request;
use App\Http\Response;
use App\View\View;

/**
 * Контроллер страницы контактов.
 */
final readonly class ContactsController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private View $view,
        private Auth $auth
    ) {
    }

    /**
     * Отображает страницу контактов.
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->user();

        $this->view
            ->setTitle(trans('CONTACTS_TITLE'))
            ->setContent($this->view->fetch('contacts/index.tpl', [
                'contact_prefill_name' => (string) ($user['name'] ?? ''),
                'contact_prefill_email' => (string) ($user['email'] ?? ''),
                'language' => locale(),
                'timestamp' => (string) time(),
                'token' => csrf_token(),
            ]));

        return new Response($this->view->render());
    }
}
