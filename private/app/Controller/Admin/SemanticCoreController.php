<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Request;
use App\Http\Response;
use App\Repository\SemanticCoreRepository as SemanticCoreDbRepository;
use App\Semantic\SemanticCoreRepository;
use App\View\View;

/**
 * Контроллер просмотра семантического ядра в панели администратора.
 */
final readonly class SemanticCoreController
{
    /**
     * Создаёт контроллер семантического ядра.
     */
    public function __construct(
        private View $view,
        private SemanticCoreRepository $semanticCore,
        private SemanticCoreDbRepository $semanticDb,
    ) {
    }

    /**
     * Показывает список кластеров и сводку по семантике.
     */
    public function index(Request $request): Response
    {
        $this->view
            ->setTitle('Семантическое ядро')
            ->setBreadcrumbs([['label' => 'Семантическое ядро']])
            ->setContent($this->view->fetch('admin/semantic_core/index.tpl', [
                'admin_path' => config('admin.path', '/admin'),
                'clusters' => $this->semanticCore->all(),
                'summary' => $this->semanticCore->summary(),
                'issues' => $this->semanticCore->validateAll(),
                'root_path' => $this->semanticCore->rootPath(),
                'db_summary' => $this->semanticDb->dashboardSummary(),
                'db_clusters' => $this->semanticDb->dashboardRows(),
            ]));

        return new Response($this->view->render('admin/layouts/admin.tpl'));
    }
}
