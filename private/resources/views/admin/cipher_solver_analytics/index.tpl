<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-magic me-2"></i>Аналитика Cipher Solver</h1>
    <a href="/cipher-solver" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-box-arrow-up-right me-1"></i>Открыть страницу
    </a>
</div>

{if !$analytics_enabled}
<div class="alert alert-warning">
    Сбор аналитики отключён (<code>analytics.enabled = false</code>). Данные не пишутся и не отображаются.
</div>
{/if}

<p class="text-muted">
    Источник — события <code>tool_usage_events</code> с <code>tool_slug = '{$tool_slug}'</code>
    (каждый вызов API-решателя). Ниже — базовые метрики; профильные разрезы прорабатываются.
</p>

{* --- Реальные базовые метрики --- *}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="admin-stat-icon bg-success bg-opacity-10 text-success rounded-3 p-3">
                    <i class="bi bi-lightning fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Запусков за 7 дней</div>
                    <div class="fs-4 fw-bold">{$total_7}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="admin-stat-icon bg-info bg-opacity-10 text-info rounded-3 p-3">
                    <i class="bi bi-bar-chart fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Запусков за 30 дней</div>
                    <div class="fs-4 fw-bold">{$total_30}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="admin-stat-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                    <i class="bi bi-globe fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">На сайте (30 дн.)</div>
                    <div class="fs-4 fw-bold">{$source_30.local}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="admin-stat-icon bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                    <i class="bi bi-window fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Через embed (30 дн.)</div>
                    <div class="fs-4 fw-bold">{$source_30.embed}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{* --- Динамика по дням: данные готовы (daily_json), график — позже --- *}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6 text-muted mb-3">Динамика по дням (30 дн.)</h2>
        <canvas id="solver-daily-chart" height="80" data-daily='{$daily_json}'></canvas>
        <p class="text-muted small mb-0 mt-2">
            <i class="bi bi-info-circle me-1"></i>Данные подготовлены в <code>data-daily</code>; отрисовку графика подключим на этапе проработки.
        </p>
    </div>
</div>

{* --- Плейсхолдеры под solver-специфичные разрезы (требуют расширения инструментирования) --- *}
<div class="row g-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-2"><i class="bi bi-diagram-3 me-2 text-muted"></i>Распределение типов шифров</h2>
                <p class="text-muted small mb-0">Какие шифры чаще всего распознаёт решатель. <span class="badge bg-secondary-subtle text-secondary">в разработке</span></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-2"><i class="bi bi-check2-circle me-2 text-muted"></i>Доля успешных расшифровок</h2>
                <p class="text-muted small mb-0">Как часто найден осмысленный <code>best</code>-ответ. <span class="badge bg-secondary-subtle text-secondary">в разработке</span></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 mb-2"><i class="bi bi-rulers me-2 text-muted"></i>Длина входного текста</h2>
                <p class="text-muted small mb-0">Гистограмма длины шифртекста на входе. <span class="badge bg-secondary-subtle text-secondary">в разработке</span></p>
            </div>
        </div>
    </div>
</div>
