<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Auth\Auth;
use App\Http\Attribute\ApiOperation;
use App\Http\Attribute\ApiResponse;
use App\Http\Request;
use App\Http\Response;

/**
 * API-эндпоинты для авторизованных пользователей.
 *
 * Защищены ApiAuthMiddleware — неавторизованные запросы получают 401.
 */
final class UserController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(private readonly Auth $auth)
    {
    }

    /**
     * Возвращает профиль текущего авторизованного пользователя.
     *
     * GET /api/user/profile
     */
    #[ApiOperation(summary: 'Профиль пользователя', tags: ['user'])]
    #[ApiResponse(status: 200, description: 'Данные профиля', schema: ['type' => 'object', 'properties' => ['id' => ['type' => 'integer'], 'name' => ['type' => 'string'], 'email' => ['type' => 'string', 'format' => 'email'], 'language' => ['type' => 'string', 'nullable' => true]]])]
    #[ApiResponse(status: 401, description: 'Требуется авторизация')]
    public function profile(Request $request): Response
    {
        $user = $this->auth->user();

        return Response::json([
            'id'       => $user['id'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'language' => $user['language'] ?? null,
        ]);
    }
}
