<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Обзор инструментов</h1>
    {if $yandex_configured}
    <form method="POST" action="{$admin_path}/tools-overview/refresh-indexation"
          onsubmit="return confirm('Запросить статусы индексации из Яндекс Вебмастера?')">
        <input type="hidden" name="_csrf_token" value="{$csrf_token}">
        <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-repeat me-1"></i>Обновить индексацию (Яндекс)
        </button>
    </form>
    {else}
    <span class="badge text-bg-warning">Яндекс Вебмастер не настроен</span>
    {/if}
</div>

{if $success}
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {$success}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
{/if}

{if $error}
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {$error}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
{/if}

{* Сводные карточки *}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Всего инструментов</div>
                <div class="fs-4 fw-bold">{$summary.total}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Опубликовано</div>
                <div class="fs-4 fw-bold text-success">{$summary.published}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Без переводов</div>
                <div class="fs-4 fw-bold{if $summary.missing_translations > 0} text-danger{else} text-success{/if}">{$summary.missing_translations}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">С семантикой</div>
                <div class="fs-4 fw-bold">{$summary.with_semantic} <span class="text-muted fs-6">/ {$summary.total}</span></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">С позициями</div>
                <div class="fs-4 fw-bold">{$summary.with_rank} <span class="text-muted fs-6">/ {$summary.total}</span></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Индексация</div>
                <div class="fs-4 fw-bold">{$summary.with_indexation} <span class="text-muted fs-6">/ {$summary.total}</span></div>
            </div>
        </div>
    </div>
</div>

{* Легенда полноты переводов *}
<div class="d-flex align-items-center gap-3 mb-3 small text-muted">
    <span><span class="badge bg-success">EN</span> 100% — все поля</span>
    <span><span class="badge" style="background:#6c8ebf">EN</span> 75% — 3 поля</span>
    <span><span class="badge bg-warning text-dark">EN</span> 50% — 2 поля</span>
    <span><span class="badge bg-danger">EN</span> &lt;50% — мало</span>
    <span><span class="badge bg-secondary">EN</span> — нет перевода</span>
    <span class="ms-3">Поля: название · meta title · meta description · описание</span>
</div>

{* Главная таблица *}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        {if $tools}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="min-width:180px">Инструмент</th>
                        <th style="min-width:100px">Статус</th>
                        <th style="min-width:200px">Переводы</th>
                        <th style="min-width:130px">Контент</th>
                        <th class="text-end" style="width:90px">Запросов<br><span class="fw-normal text-muted">30 дней</span></th>
                        <th class="text-end" style="width:80px">Кластеры</th>
                        <th class="text-end" style="width:80px">Queries</th>
                        <th class="text-end" style="width:80px">Score</th>
                        <th class="text-end" style="width:80px">Ср. позиция</th>
                        <th class="text-end" style="width:80px">Показы</th>
                        <th class="text-end" style="width:70px">Клики</th>
                        <th style="min-width:130px">Яндекс</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $tools as $tool}
                    {assign var="row_class" value=""}
                    {if !$tool.published}{assign var="row_class" value=" table-secondary opacity-75"}{/if}
                    <tr class="{$row_class}">
                        <td class="ps-3">
                            <div class="fw-semibold font-monospace">{$tool.alias}</div>
                            <div class="text-muted" style="font-size:0.75rem">{$tool.category_alias}</div>
                        </td>
                        <td>
                            {if $tool.published}
                                <span class="badge bg-success">опубликован</span>
                            {else}
                                <span class="badge bg-secondary">черновик</span>
                            {/if}
                            {if $tool.calculation_mode === 'api'}
                                <br><span class="badge bg-info text-dark mt-1">API</span>
                            {/if}
                        </td>
                        <td>
                            {foreach $locales as $locale}
                                {assign var="lang_data" value=$tool.languages[$locale]}
                                {if $lang_data === null}
                                    <a href="{$admin_path}/ciphers/{$tool.id}/edit?language={$locale}"
                                       class="badge bg-secondary text-decoration-none me-1 mb-1"
                                       title="Нет перевода для {$locale|upper}">{$locale|upper}</a>
                                {else}
                                    {assign var="score" value=$lang_data.score}
                                    {if $score === 4}
                                        {assign var="badge_color" value="bg-success"}
                                        {assign var="badge_title" value="100% — все поля заполнены"}
                                    {elseif $score === 3}
                                        {assign var="badge_color" value="bg-primary"}
                                        {assign var="badge_title" value="75% — 3 из 4 полей"}
                                    {elseif $score === 2}
                                        {assign var="badge_color" value="bg-warning text-dark"}
                                        {assign var="badge_title" value="50% — 2 из 4 полей"}
                                    {else}
                                        {assign var="badge_color" value="bg-danger"}
                                        {assign var="badge_title" value="25% или меньше"}
                                    {/if}
                                    <a href="{$admin_path}/ciphers/{$tool.id}/edit?language={$locale}"
                                       class="badge {$badge_color} text-decoration-none me-1 mb-1"
                                       title="{$badge_title}">{$locale|upper}</a>
                                {/if}
                            {/foreach}
                        </td>
                        <td>
                            <span class="text-muted" title="Блоки">
                                <i class="bi bi-file-text"></i>&thinsp;{$tool.blocks_count}
                            </span>
                            &nbsp;
                            <span class="text-muted" title="FAQ">
                                <i class="bi bi-question-circle"></i>&thinsp;{$tool.faq_count}
                            </span>
                            &nbsp;
                            <span class="text-muted" title="Примеры">
                                <i class="bi bi-terminal"></i>&thinsp;{$tool.examples_count}
                            </span>
                            &nbsp;
                            <span class="text-muted" title="Теги">
                                <i class="bi bi-tag"></i>&thinsp;{$tool.tags_count}
                            </span>
                        </td>
                        <td class="text-end">
                            {if $tool.usage_30d > 0}
                                <span class="fw-semibold">{$tool.usage_30d}</span>
                            {else}
                                <span class="text-muted">—</span>
                            {/if}
                        </td>
                        <td class="text-end">
                            {if $tool.clusters_count > 0}
                                <span class="fw-semibold">{$tool.clusters_count}</span>
                            {else}
                                <span class="text-danger">0</span>
                            {/if}
                        </td>
                        <td class="text-end">
                            {if $tool.queries_count > 0}
                                {$tool.queries_count}
                            {else}
                                <span class="text-muted">—</span>
                            {/if}
                        </td>
                        <td class="text-end">
                            {if $tool.semantic_score > 0}
                                {$tool.semantic_score}
                            {else}
                                <span class="text-muted">—</span>
                            {/if}
                        </td>
                        <td class="text-end">
                            {if $tool.avg_position !== null}
                                {assign var="pos" value=$tool.avg_position}
                                {if $pos <= 10}
                                    <span class="fw-bold text-success">{$pos}</span>
                                {elseif $pos <= 30}
                                    <span class="fw-bold text-warning">{$pos}</span>
                                {else}
                                    <span class="text-danger">{$pos}</span>
                                {/if}
                            {else}
                                <span class="text-muted">—</span>
                            {/if}
                        </td>
                        <td class="text-end">
                            {if $tool.total_impressions > 0}
                                {$tool.total_impressions|number_format:0:'.':' '}
                            {else}
                                <span class="text-muted">—</span>
                            {/if}
                        </td>
                        <td class="text-end">
                            {if $tool.total_clicks > 0}
                                {$tool.total_clicks}
                            {else}
                                <span class="text-muted">—</span>
                            {/if}
                        </td>
                        <td>
                            {foreach $locales as $locale}
                                {if isset($tool.indexation[$locale])}
                                    {assign var="idx" value=$tool.indexation[$locale]}
                                    {assign var="idx_status" value=$idx.indexing_status}
                                    {if $idx_status === 'INDEXED'}
                                        <span class="badge bg-success me-1 mb-1" title="{$locale|upper}: индексирован · {$idx.crawl_date}">{$locale|upper} ✓</span>
                                    {elseif $idx_status === 'NOT_INDEXED'}
                                        <span class="badge bg-danger me-1 mb-1" title="{$locale|upper}: не индексирован · {$idx.crawl_date}">{$locale|upper} ✗</span>
                                    {elseif $idx_status === 'EXCLUDED'}
                                        <span class="badge bg-warning text-dark me-1 mb-1" title="{$locale|upper}: исключён · {$idx.crawl_date}">{$locale|upper} !</span>
                                    {else}
                                        <span class="badge bg-secondary me-1 mb-1" title="{$locale|upper}: статус неизвестен">{$locale|upper} ?</span>
                                    {/if}
                                {/if}
                            {/foreach}
                            {if $tool.indexation|count === 0}
                                <span class="text-muted">—</span>
                            {/if}
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {else}
        <div class="p-4 text-muted">Инструменты не найдены.</div>
        {/if}
    </div>
</div>

{if $tools}
<div class="text-muted small mt-2 ms-1">
    Позиции и показы — из последнего снимка Яндекс Вебмастера.
    Индексация — из кеша, обновите кнопкой выше.
</div>
{/if}
