<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Analytics\AnalyticsService;
use App\Auth\Auth;
use App\Http\Request;
use App\Http\Response;

/**
 * API-контроллер для приёма событий аналитики от клиентских инструментов.
 */
final readonly class AnalyticsController
{
    /**
     * Создаёт экземпляр контроллера аналитики.
     */
    public function __construct(
        private AnalyticsService $analytics,
        private Auth $auth,
    ) {
    }

    /**
     * Принимает событие использования инструмента от клиентского JS.
     *
     * POST /api/analytics/use
     *
     * Тело запроса: { "tool": "encoding/base64", "mode": "encode", "source": "local" }
     */
    public function record(Request $request): Response
    {
        $payload = $request->json();
        if (!is_array($payload)) {
            return Response::json(['ok' => true]);
        }

        $tool = trim((string) ($payload['tool'] ?? ''));
        $mode = trim((string) ($payload['mode'] ?? 'encode'));
        $source = trim((string) ($payload['source'] ?? 'local'));

        if ($tool === '') {
            return Response::json(['ok' => true]);
        }

        $ip = $request->ip();
        $ipHash = hash('sha256', $ip);
        $userId = $this->auth->id() !== null ? (int) $this->auth->id() : null;

        $this->analytics->recordUse($tool, $userId, $ipHash, $mode, $source);

        return Response::json(['ok' => true]);
    }
}
