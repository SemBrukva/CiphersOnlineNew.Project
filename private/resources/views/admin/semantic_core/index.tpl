<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Семантическое ядро</h1>
        <div class="text-muted small font-monospace">{$root_path}</div>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="{$admin_path}/">
        <i class="bi bi-arrow-left me-1"></i>Дашборд
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Кластеры</div>
                <div class="fs-4 fw-bold">{$summary.clusters}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Запросы</div>
                <div class="fs-4 fw-bold">{$summary.queries}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Суммарный score</div>
                <div class="fs-4 fw-bold">{$summary.total_volume}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Проблемы</div>
                <div class="fs-4 fw-bold{if $summary.issues > 0} text-danger{else} text-success{/if}">{$summary.issues}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="bi bi-database me-2 text-success"></i>БД и аналитика</h5>
        {if $db_summary.ready}
            <span class="badge text-bg-success">sync ready</span>
        {else}
            <span class="badge text-bg-warning">нужны миграции</span>
        {/if}
    </div>
    <div class="card-body">
        {if $db_summary.ready}
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="text-muted small">Кластеры в БД</div>
                    <div class="fs-5 fw-bold">{$db_summary.clusters}</div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="text-muted small">Запросы в БД</div>
                    <div class="fs-5 fw-bold">{$db_summary.queries}</div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="text-muted small">Score в БД</div>
                    <div class="fs-5 fw-bold">{$db_summary.total_score}</div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="text-muted small">Снимки позиций</div>
                    <div class="fs-5 fw-bold">{$db_summary.rank_snapshots}</div>
                </div>
            </div>
            {if $db_clusters}
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Кластер</th>
                                <th>Статус</th>
                                <th class="text-end">Primary</th>
                                <th class="text-end">Secondary</th>
                                <th class="text-end">Long-tail</th>
                                <th class="text-end">Лучшая позиция</th>
                                <th>Последняя проверка</th>
                                <th>Sync</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $db_clusters as $row}
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{$row.cluster}</div>
                                        <div class="text-muted small font-monospace">{$row.locale}/{$row.tool_slug}</div>
                                    </td>
                                    <td><span class="badge text-bg-light border">{$row.status}</span></td>
                                    <td class="text-end">{$row.primary_queries|default:0}</td>
                                    <td class="text-end">{$row.secondary_queries|default:0}</td>
                                    <td class="text-end">{$row.long_tail_queries|default:0}</td>
                                    <td class="text-end">{if $row.best_position}{$row.best_position}{else}<span class="text-muted">—</span>{/if}</td>
                                    <td>{if $row.last_rank_checked_at}{$row.last_rank_checked_at}{else}<span class="text-muted">нет данных</span>{/if}</td>
                                    <td class="text-muted small">{$row.synced_at}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            {else}
                <div class="text-muted">Данные ещё не синхронизированы. Запустите <code>php bin/console semantic:sync</code>.</div>
            {/if}
        {else}
            <div class="text-muted">
                Таблицы семантического ядра ещё не созданы. Выполните <code>php bin/console migrate</code>, затем <code>php bin/console semantic:sync</code>.
            </div>
        {/if}
    </div>
</div>

{if $issues}
<div class="alert alert-warning shadow-sm">
    <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Найдены проблемы в семантике</div>
    <ul class="mb-0">
        {foreach $issues as $issue}
            <li><span class="font-monospace">{$issue.file}</span>: {$issue.message}</li>
        {/foreach}
    </ul>
</div>
{/if}

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="bi bi-search me-2 text-primary"></i>Кластеры</h5>
        <span class="badge text-bg-light">semantic-core.v1</span>
    </div>
    <div class="card-body p-0">
        {if $clusters}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Кластер</th>
                        <th>Язык</th>
                        <th>Инструмент</th>
                        <th>Статус</th>
                        <th class="text-end">Запросов</th>
                        <th class="text-end pe-4">Score</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $clusters as $cluster}
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{$cluster.cluster}</div>
                            <div class="text-muted small font-monospace">{$cluster._file}</div>
                            {if $cluster.notes}
                                <div class="text-muted small mt-1">{$cluster.notes}</div>
                            {/if}
                        </td>
                        <td><span class="badge text-bg-secondary">{$cluster.locale}</span></td>
                        <td>
                            <a href="{$cluster.tool.url}" target="_blank" rel="noopener" class="font-monospace text-decoration-none">{$cluster.tool.slug}</a>
                            <div class="text-muted small font-monospace">{$cluster.tool.content_file}</div>
                        </td>
                        <td><span class="badge text-bg-light border">{$cluster.status}</span></td>
                        <td class="text-end">{$cluster._queries_count}</td>
                        <td class="text-end pe-4 fw-semibold">{$cluster._total_volume}</td>
                    </tr>
                    <tr>
                        <td colspan="6" class="bg-light ps-4 pe-4">
                            <div class="d-flex flex-wrap gap-2 py-2">
                                {foreach $cluster.queries as $query}
                                    <span class="badge rounded-pill text-bg-white border text-dark">
                                        {$query.query}
                                        {if isset($query.volume)}
                                            <span class="text-muted ms-1">{$query.volume}</span>
                                        {/if}
                                        {if $query.priority === 'primary'}
                                            <span class="text-primary ms-1">primary</span>
                                        {/if}
                                    </span>
                                {/foreach}
                            </div>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {else}
        <div class="p-4 text-muted text-center">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
            Файлы семантического ядра пока не добавлены
        </div>
        {/if}
    </div>
</div>
