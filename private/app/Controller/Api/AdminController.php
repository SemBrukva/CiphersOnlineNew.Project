<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\Attribute\ApiOperation;
use App\Http\Attribute\ApiResponse;
use App\Http\Request;
use App\Http\Response;
use App\Repository\UserRepository;

/**
 * API-эндпоинты для администраторов.
 *
 * Защищены ApiAdminMiddleware — не-администраторы получают 401 или 403.
 */
final class AdminController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(private readonly UserRepository $users)
    {
    }

    /**
     * Возвращает базовую статистику приложения.
     *
     * GET /api/admin/stats
     */
    #[ApiOperation(summary: 'Статистика приложения', tags: ['admin'])]
    #[ApiResponse(status: 200, description: 'Статистика', schema: ['type' => 'object', 'properties' => ['users_count' => ['type' => 'integer']]])]
    #[ApiResponse(status: 401, description: 'Требуется авторизация')]
    #[ApiResponse(status: 403, description: 'Недостаточно прав')]
    public function stats(Request $request): Response
    {
        $usersCount = $this->users->countAll();

        return Response::json([
            'users_count' => $usersCount,
        ]);
    }
}
