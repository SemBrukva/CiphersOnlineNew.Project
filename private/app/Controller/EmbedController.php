<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cipher\BaseToolUiFactory;
use App\Cipher\ToolUiDecorator;
use App\Http\Request;
use App\Http\Response;
use App\Repository\CipherRepository;
use App\View\View;

/**
 * Контроллер встраиваемого (iframe) виджета инструмента.
 *
 * Отдаёт облегчённую страницу без навигации и подвала сайта — только сам
 * калькулятор инструмента и небольшой брендовый бэклинк. Ответ помечается
 * noindex и разрешён к фреймингу (см. SecurityHeadersMiddleware).
 */
final readonly class EmbedController
{
    /**
     * Создаёт экземпляр контроллера.
     */
    public function __construct(
        private View $view,
        private CipherRepository $ciphers,
        private BaseToolUiFactory $uiFactory,
        private ToolUiDecorator $uiDecorator
    ) {
    }

    /**
     * Отображает встраиваемый виджет инструмента по alias категории и инструмента.
     */
    public function show(Request $request): Response
    {
        $categoryAlias = (string) $request->route('category', '');
        $cipherAlias = (string) $request->route('cipher', '');
        $language = locale();
        $defaultLanguage = (string) config('locale.locale', 'en');

        $cipher = $this->ciphers->findPublishedCipherPageByAliases(
            $categoryAlias,
            $cipherAlias,
            $language,
            $defaultLanguage
        );

        if ($cipher === null) {
            return new Response('', 404);
        }

        $toolSlug = $categoryAlias . '/' . $cipherAlias;
        $calculationMode = (string) ($cipher['calculation_mode'] ?? 'client');
        $toolUi = $this->uiDecorator->decorate(
            $this->uiFactory->build($toolSlug, $calculationMode),
            $categoryAlias,
            $cipherAlias
        );

        $appUrl = rtrim((string) config('app.url', ''), '/');
        $toolPageUrl = $appUrl . locale_url('/' . $toolSlug);

        $this->view
            ->setTitle((string) ($cipher['meta_title'] ?: $cipher['name']))
            ->setMeta((string) ($cipher['meta_description'] ?: $cipher['description']))
            ->setRobots('noindex, nofollow')
            ->setContent($this->view->fetch('embed/show.tpl', [
                'cipher' => $cipher,
                'tool_slug' => $toolSlug,
                'tool_ui' => $toolUi,
                'tool_ui_json' => (string) json_encode($toolUi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'embed_tool_url' => $toolPageUrl,
                'embed_tool_name' => (string) $cipher['name'],
            ]));

        return new Response($this->view->render('layouts/embed.tpl'));
    }
}
