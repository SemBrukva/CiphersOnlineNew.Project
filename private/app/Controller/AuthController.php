<?php

declare(strict_types=1);

namespace App\Controller;

use App\Auth\Auth;
use App\Http\Request;
use App\Http\Response;
use App\View\View;

/**
 * Контроллер аутентификации.
 *
 * Обрабатывает отображение формы входа,
 * проверку учётных данных и выход из системы.
 */
final readonly class AuthController
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
     * Отображает форму входа.
     * Перенаправляет на главную страницу, если пользователь уже вошёл.
     */
    public function loginForm(Request $request): Response
    {
        if ($this->auth->check()) {
            return redirect(locale_url('/'));
        }

        $this->view
            ->setTitle(trans('AUTH_SIGN_IN_TITLE'))
            ->setContent($this->view->fetch('auth/login.tpl'));

        return new Response($this->view->render());
    }

    /**
     * Отображает страницу регистрации через API.
     * Если регистрация отключена, возвращает 404.
     */
    public function registrationForm(Request $request): Response
    {
        if (!config('app.user_registration', false)) {
            $this->view
                ->setTitle(trans('ERROR_404_TITLE'))
                ->setContent($this->view->fetch('errors/404.tpl'));

            return new Response($this->view->render(), 404);
        }

        if ($this->auth->check()) {
            return redirect(locale_url('/'));
        }

        $this->view
            ->setTitle(trans('AUTH_SIGN_UP_TITLE'))
            ->setContent($this->view->fetch('auth/registration.tpl'));

        return new Response($this->view->render());
    }

    /**
     * Завершает сессию пользователя и перенаправляет на страницу входа.
     */
    public function logout(Request $request): Response
    {
        $this->auth->logout();

        return redirect(locale_url('/login'));
    }
}
