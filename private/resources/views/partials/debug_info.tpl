{* ---- Пороговые классы цвета ---- *}
{if $debug_info.execution_time < 100}
    {assign var="_dbg_time_cls" value="green"}
{elseif $debug_info.execution_time < 500}
    {assign var="_dbg_time_cls" value="yellow"}
{else}
    {assign var="_dbg_time_cls" value="red"}
{/if}

{if $debug_info.sql_total_time < 50}
    {assign var="_dbg_sql_cls" value="blue"}
{elseif $debug_info.sql_total_time < 200}
    {assign var="_dbg_sql_cls" value="yellow"}
{else}
    {assign var="_dbg_sql_cls" value="red"}
{/if}

{if $debug_info.memory_peak < 16}
    {assign var="_dbg_mem_cls" value="green"}
{elseif $debug_info.memory_peak < 64}
    {assign var="_dbg_mem_cls" value="yellow"}
{else}
    {assign var="_dbg_mem_cls" value="red"}
{/if}

<div id="dbg-spacer" style="flex-shrink:0" aria-hidden="true"></div>
<div class="dbg-panel" id="dbgPanel">

    {* ==================== Toggle bar ==================== *}
    <div class="dbg-toggle" id="dbgToggle">
        <span class="dbg-toggle__icon">🐞</span>
        <span class="dbg-toggle__label">Debug</span>

        <div class="dbg-toggle__metrics">
            <span class="dbg-badge dbg-badge--{$_dbg_time_cls}">
                <span class="dbg-badge__dot"></span>
                {$debug_info.execution_time} мс
            </span>
            <span class="dbg-badge dbg-badge--{$_dbg_mem_cls}">
                <span class="dbg-badge__dot"></span>
                {$debug_info.memory_peak} МБ
            </span>
            <span class="dbg-badge dbg-badge--{$_dbg_sql_cls}">
                <span class="dbg-badge__dot"></span>
                SQL {$debug_info.sql_total_time} мс
            </span>
            <span class="dbg-badge">
                <span class="dbg-badge__dot"></span>
                {$debug_info.loaded_files} файлов
            </span>
            {if $debug_info.is_auth}
                <span class="dbg-badge dbg-badge--purple">
                    <span class="dbg-badge__dot"></span>
                    {$debug_info.user_name|default:$debug_info.user_email}
                    {if $debug_info.is_admin} ★{/if}
                </span>
            {/if}
            {if $debug_info.memcached_usage !== null}
                <span class="dbg-badge dbg-badge--blue">
                    <span class="dbg-badge__dot"></span>
                    MC {$debug_info.memcached_usage}/{$debug_info.memcached_total|default:'?'} MB
                </span>
            {/if}
            {if $debug_info.response_status !== null}
                <span class="dbg-badge dbg-badge--{if $debug_info.response_status < 300}green{elseif $debug_info.response_status < 400}yellow{else}red{/if}">
                    <span class="dbg-badge__dot"></span>
                    HTTP {$debug_info.response_status}
                </span>
            {/if}
            {if $debug_info.translation_missing|@count > 0}
                <span class="dbg-badge dbg-badge--yellow">
                    <span class="dbg-badge__dot"></span>
                    {$debug_info.translation_missing|@count} missing i18n
                </span>
            {/if}
            <span class="dbg-badge dbg-badge--{if $debug_info.geo_ad_network === 'RSY'}purple{else}blue{/if}">
                <span class="dbg-badge__dot"></span>
                {$debug_info.geo_country|default:'?'} · {$debug_info.geo_ad_network}
            </span>
        </div>

        <span class="dbg-toggle__arrow">▲</span>
    </div>

    {* ==================== Body ==================== *}
    <div class="dbg-body" id="dbgBody">

        {* ---- Метрики производительности ---- *}
        <div class="dbg-metrics">
            <div class="dbg-metric dbg-metric--{$_dbg_time_cls}">
                <span class="dbg-metric__label">Время выполнения</span>
                <span class="dbg-metric__value">{$debug_info.execution_time} <span class="dbg-metric__unit">мс</span></span>
            </div>
            <div class="dbg-metric dbg-metric--{$_dbg_mem_cls}">
                <span class="dbg-metric__label">Пиковая память</span>
                <span class="dbg-metric__value">{$debug_info.memory_peak} <span class="dbg-metric__unit">МБ</span></span>
            </div>
            <div class="dbg-metric">
                <span class="dbg-metric__label">Текущая память</span>
                <span class="dbg-metric__value">{$debug_info.memory_usage} <span class="dbg-metric__unit">МБ</span></span>
            </div>
            <div class="dbg-metric dbg-metric--{$_dbg_sql_cls}">
                <span class="dbg-metric__label">Время SQL</span>
                <span class="dbg-metric__value">{$debug_info.sql_total_time} <span class="dbg-metric__unit">мс</span></span>
            </div>
            <div class="dbg-metric">
                <span class="dbg-metric__label">PHP-файлов</span>
                <span class="dbg-metric__value">{$debug_info.loaded_files}</span>
            </div>
            {if $debug_info.memcached_usage !== null}
                <div class="dbg-metric dbg-metric--blue">
                    <span class="dbg-metric__label">Memcached</span>
                    <span class="dbg-metric__value">{$debug_info.memcached_usage} <span class="dbg-metric__unit">/ {$debug_info.memcached_total|default:'?'} MB</span></span>
                </div>
            {/if}
            {assign var="_dbg_cache_total" value=$debug_info.cache_stats.hits + $debug_info.cache_stats.misses}
            {if $_dbg_cache_total > 0}
                {if $debug_info.cache_stats.misses === 0}
                    {assign var="_dbg_cache_cls" value="green"}
                {elseif $debug_info.cache_stats.hits > $debug_info.cache_stats.misses}
                    {assign var="_dbg_cache_cls" value="yellow"}
                {else}
                    {assign var="_dbg_cache_cls" value="red"}
                {/if}
                <div class="dbg-metric dbg-metric--{$_dbg_cache_cls}">
                    <span class="dbg-metric__label">Cache hit/miss</span>
                    <span class="dbg-metric__value">{$debug_info.cache_stats.hits}<span class="dbg-metric__unit"> / {$debug_info.cache_stats.misses}</span></span>
                </div>
            {/if}
            {if $debug_info.php_errors|@count > 0}
                <div class="dbg-metric dbg-metric--red">
                    <span class="dbg-metric__label">PHP-ошибки</span>
                    <span class="dbg-metric__value">{$debug_info.php_errors|@count}</span>
                </div>
            {/if}
            {if $debug_info.response_status !== null}
                {if $debug_info.response_status < 300}
                    {assign var="_dbg_resp_cls" value="green"}
                {elseif $debug_info.response_status < 400}
                    {assign var="_dbg_resp_cls" value="yellow"}
                {else}
                    {assign var="_dbg_resp_cls" value="red"}
                {/if}
                <div class="dbg-metric dbg-metric--{$_dbg_resp_cls}">
                    <span class="dbg-metric__label">HTTP-статус</span>
                    <span class="dbg-metric__value">{$debug_info.response_status}</span>
                </div>
                <div class="dbg-metric">
                    <span class="dbg-metric__label">Размер ответа</span>
                    <span class="dbg-metric__value">
                        {if $debug_info.response_size >= 1024}
                            {math equation="round(size/1024,1)" size=$debug_info.response_size} <span class="dbg-metric__unit">КБ</span>
                        {else}
                            {$debug_info.response_size} <span class="dbg-metric__unit">Б</span>
                        {/if}
                    </span>
                </div>
            {/if}
            {if $debug_info.translation_missing|@count > 0}
                <div class="dbg-metric dbg-metric--yellow">
                    <span class="dbg-metric__label">Пропущ. перев.</span>
                    <span class="dbg-metric__value">{$debug_info.translation_missing|@count}</span>
                </div>
            {/if}
        </div>

        {* ---- Tabs nav ---- *}
        <div class="dbg-tabs">
            <button class="dbg-tab dbg-tab--active" data-tab="request">Запрос</button>
            <button class="dbg-tab" data-tab="route">Маршрут</button>
            <button class="dbg-tab" data-tab="session">Сессия</button>
            {if $_dbg_cache_total > 0}
                <button class="dbg-tab" data-tab="cache">Cache</button>
            {/if}
            {if $debug_info.php_errors|@count > 0}
                <button class="dbg-tab dbg-tab--error" data-tab="errors">
                    Ошибки <span class="dbg-tab__count">{$debug_info.php_errors|@count}</span>
                </button>
            {/if}
            <button class="dbg-tab" data-tab="i18n">
                Переводы
                {if $debug_info.translation_missing|@count > 0}
                    <span class="dbg-tab__count dbg-tab__count--warn">{$debug_info.translation_missing|@count}</span>
                {/if}
            </button>
            {if $debug_info.timeline|@count > 0}
                <button class="dbg-tab" data-tab="timeline">Timeline</button>
            {/if}
            <button class="dbg-tab" data-tab="sql">
                SQL
                {if $debug_info.sql_queries|@count > 0}
                    <span class="dbg-tab__count dbg-tab__count--blue">{$debug_info.sql_queries|@count}</span>
                {/if}
            </button>
            <button class="dbg-tab" data-tab="env">Env</button>
        </div>

        {* ==================== Tab: Request ==================== *}
        <div class="dbg-tabpanel" id="dbgTab-request">

            {* Базовые параметры *}
            <div class="dbg-section">
                <div class="dbg-section__head">HTTP-запрос</div>
                <div class="dbg-kv-grid">
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Метод</span>
                        <span class="dbg-kv-val">
                            <span class="dbg-method dbg-method--{$debug_info.method|lower}">{$debug_info.method}</span>
                        </span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">URI</span>
                        <span class="dbg-kv-val dbg-kv-val--mono">{$debug_info.full_uri|escape}</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">IP</span>
                        <span class="dbg-kv-val dbg-kv-val--mono">{$debug_info.ip|escape}</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Страна</span>
                        <span class="dbg-kv-val">
                            {if $debug_info.geo_country !== null}
                                <span class="dbg-kv-val--mono">{$debug_info.geo_country|escape}</span>
                            {else}
                                <span class="dbg-kv-val--dim">не определена</span>
                            {/if}
                        </span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Реклама</span>
                        <span class="dbg-kv-val">
                            <span class="dbg-badge dbg-badge--{if $debug_info.geo_ad_network === 'RSY'}purple{else}blue{/if}">
                                <span class="dbg-badge__dot"></span>
                                {$debug_info.geo_ad_network}
                            </span>
                        </span>
                    </div>
                    {if $debug_info.referer}
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Referer</span>
                            <span class="dbg-kv-val dbg-kv-val--mono">{$debug_info.referer|escape}</span>
                        </div>
                    {/if}
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">User-Agent</span>
                        <span class="dbg-kv-val dbg-kv-val--mono dbg-kv-val--wrap">{$debug_info.user_agent|escape}</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Время</span>
                        <span class="dbg-kv-val">{$debug_info.timestamp}</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Окружение</span>
                        <span class="dbg-kv-val">
                            <span class="dbg-env-badge dbg-env-badge--{$debug_info.app_env}">{$debug_info.app_env}</span>
                        </span>
                    </div>
                </div>
            </div>

            {* GET параметры *}
            {if $debug_info.get_params|@count > 0}
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        GET-параметры
                        <span class="dbg-section__head-count">{$debug_info.get_params|@count}</span>
                    </div>
                    <div class="dbg-kv-grid">
                        {foreach from=$debug_info.get_params key=k item=v}
                            <div class="dbg-kv-row">
                                <span class="dbg-kv-key dbg-kv-key--param">{$k|escape}</span>
                                <span class="dbg-kv-val dbg-kv-val--mono">{$v|escape}</span>
                            </div>
                        {/foreach}
                    </div>
                </div>
            {/if}

            {* POST параметры *}
            {if $debug_info.post_params|@count > 0}
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        POST-параметры
                        <span class="dbg-section__head-count">{$debug_info.post_params|@count}</span>
                    </div>
                    <div class="dbg-kv-grid">
                        {foreach from=$debug_info.post_params key=k item=v}
                            <div class="dbg-kv-row">
                                <span class="dbg-kv-key dbg-kv-key--param">{$k|escape}</span>
                                <span class="dbg-kv-val dbg-kv-val--mono">{$v|escape}</span>
                            </div>
                        {/foreach}
                    </div>
                </div>
            {/if}

            {* Заголовки *}
            {if $debug_info.headers|@count > 0}
                <div class="dbg-section">
                    <div class="dbg-section__head dbg-section__head--collapsible" data-collapsible>
                        Заголовки запроса
                        <span class="dbg-section__head-count">{$debug_info.headers|@count}</span>
                        <span class="dbg-section__arrow">▾</span>
                    </div>
                    <div class="dbg-section__body dbg-section__body--collapsed">
                        <div class="dbg-kv-grid">
                            {foreach from=$debug_info.headers key=k item=v}
                                <div class="dbg-kv-row">
                                    <span class="dbg-kv-key">{$k|escape}</span>
                                    <span class="dbg-kv-val dbg-kv-val--mono dbg-kv-val--wrap">{$v|escape}</span>
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            {/if}

        </div>{* /Tab: Request *}

        {* ==================== Tab: Route ==================== *}
        <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-route">

            <div class="dbg-section">
                <div class="dbg-section__head">Совпавший маршрут</div>
                <div class="dbg-kv-grid">
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Паттерн</span>
                        <span class="dbg-kv-val dbg-kv-val--mono">{$debug_info.route_pattern|default:'—'|escape}</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Контроллер</span>
                        <span class="dbg-kv-val dbg-kv-val--mono">{$debug_info.route_controller|default:'—'|escape}</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Метод</span>
                        <span class="dbg-kv-val dbg-kv-val--mono">{$debug_info.route_action|default:'—'|escape}</span>
                    </div>
                </div>
            </div>

            {if $debug_info.route_middleware|@count > 0}
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        Middleware маршрута
                        <span class="dbg-section__head-count">{$debug_info.route_middleware|@count}</span>
                    </div>
                    <div class="dbg-steps">
                        {foreach from=$debug_info.route_middleware item=mw}
                            <div class="dbg-step">
                                <span class="dbg-step__name">{$mw|escape}</span>
                            </div>
                        {/foreach}
                    </div>
                </div>
            {/if}

        </div>{* /Tab: Route *}

        {* ==================== Tab: Session ==================== *}
        <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-session">

            <div class="dbg-section">
                <div class="dbg-section__head">Сессия</div>
                <div class="dbg-kv-grid">
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Session ID</span>
                        <span class="dbg-kv-val dbg-kv-val--mono">{$debug_info.session_id|default:'—'|escape}</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">CSRF</span>
                        <span class="dbg-kv-val dbg-kv-val--mono">{$debug_info.session_data.csrf|default:'—'}</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Пользователь</span>
                        <span class="dbg-kv-val">
                            {if $debug_info.is_auth}
                                <span class="dbg-badge dbg-badge--purple">
                                    #{$debug_info.user_id} {$debug_info.user_name|default:''|escape}
                                    {if $debug_info.is_admin} ★ admin{/if}
                                </span>
                            {else}
                                <span class="dbg-kv-val--dim">гость</span>
                            {/if}
                        </span>
                    </div>
                </div>
            </div>

            {if $debug_info.session_data.data|@count > 0}
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        Данные сессии
                        <span class="dbg-section__head-count">{$debug_info.session_data.data|@count}</span>
                    </div>
                    <div class="dbg-kv-grid">
                        {foreach from=$debug_info.session_data.data key=k item=v}
                            <div class="dbg-kv-row">
                                <span class="dbg-kv-key dbg-kv-key--param">{$k|escape}</span>
                                <span class="dbg-kv-val dbg-kv-val--mono">{$v|@json_encode|escape}</span>
                            </div>
                        {/foreach}
                    </div>
                </div>
            {/if}

            {if $debug_info.session_data.flash|@count > 0}
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        Flash-сообщения
                        <span class="dbg-section__head-count">{$debug_info.session_data.flash|@count}</span>
                    </div>
                    <div class="dbg-kv-grid">
                        {foreach from=$debug_info.session_data.flash key=k item=v}
                            <div class="dbg-kv-row">
                                <span class="dbg-kv-key dbg-kv-key--param">{$k|escape}</span>
                                <span class="dbg-kv-val dbg-kv-val--mono">{$v|@json_encode|escape}</span>
                            </div>
                        {/foreach}
                    </div>
                </div>
            {/if}

        </div>{* /Tab: Session *}

        {* ==================== Tab: Cache ==================== *}
        {if $_dbg_cache_total > 0}
            <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-cache">
                <div class="dbg-section">
                    <div class="dbg-section__head">Статистика кеша</div>
                    <div class="dbg-kv-grid">
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Hits</span>
                            <span class="dbg-kv-val">
                                <span class="dbg-cache-num dbg-cache-num--hit">{$debug_info.cache_stats.hits}</span>
                            </span>
                        </div>
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Misses</span>
                            <span class="dbg-kv-val">
                                <span class="dbg-cache-num dbg-cache-num--miss">{$debug_info.cache_stats.misses}</span>
                            </span>
                        </div>
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Ratio</span>
                            <span class="dbg-kv-val dbg-kv-val--mono">
                                {if $_dbg_cache_total > 0}
                                    {math equation="round(hits / total * 100, 1)" hits=$debug_info.cache_stats.hits total=$_dbg_cache_total}%
                                {else}
                                    —
                                {/if}
                            </span>
                        </div>
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Драйвер</span>
                            <span class="dbg-kv-val dbg-kv-val--mono">{$debug_info.cache_driver|escape}</span>
                        </div>
                    </div>
                </div>
                <div class="dbg-section">
                    <div class="dbg-section__head">Hit ratio</div>
                    <div class="dbg-cache-bar-wrap">
                        {if $_dbg_cache_total > 0}
                            {assign var="_dbg_hit_pct" value=$debug_info.cache_stats.hits / $_dbg_cache_total * 100}
                        {else}
                            {assign var="_dbg_hit_pct" value=0}
                        {/if}
                        <div class="dbg-cache-bar">
                            <div class="dbg-cache-bar__fill" style="width:{$_dbg_hit_pct|string_format:'%.1f'}%"></div>
                        </div>
                        <span class="dbg-cache-bar__label">
                            {$debug_info.cache_stats.hits} hit / {$debug_info.cache_stats.misses} miss
                        </span>
                    </div>
                </div>
            </div>
        {/if}

        {* ==================== Tab: PHP Errors ==================== *}
        {if $debug_info.php_errors|@count > 0}
            <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-errors">
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        PHP-ошибки
                        <span class="dbg-section__head-count">{$debug_info.php_errors|@count}</span>
                    </div>
                    <div class="dbg-errors">
                        {foreach from=$debug_info.php_errors item=err}
                            <div class="dbg-error">
                                <div class="dbg-error__head">
                                    <span class="dbg-error__level dbg-error__level--{$err.level|lower|replace:' ':'-'}">{$err.level|escape}</span>
                                    <span class="dbg-error__location">{$err.file|escape}:{$err.line}</span>
                                </div>
                                <div class="dbg-error__message">{$err.message|escape}</div>
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>
        {/if}

        {* ==================== Tab: Timeline ==================== *}
        {if $debug_info.timeline|@count > 0}
            <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-timeline">
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        Timeline запроса
                        <span class="dbg-section__head-count">{$debug_info.timeline|@count} spans · {$debug_info.execution_time} мс всего</span>
                    </div>

                    {* Легенда категорий *}
                    <div class="dbg-tl-legend">
                        <span class="dbg-tl-legend-item dbg-tl-legend-item--middleware">middleware</span>
                        <span class="dbg-tl-legend-item dbg-tl-legend-item--controller">controller</span>
                        <span class="dbg-tl-legend-item dbg-tl-legend-item--sql">sql</span>
                        <span class="dbg-tl-legend-item dbg-tl-legend-item--app">app</span>
                    </div>

                    <div class="dbg-trace">
                        {foreach from=$debug_info.timeline item=span}
                            <div class="dbg-trace-row">
                                <span class="dbg-trace-name" title="{$span.name|escape}">{$span.name|escape}</span>
                                <div class="dbg-trace-track">
                                    <div class="dbg-trace-bar dbg-trace-bar--{$span.category}"
                                         style="margin-left:{$span.pct_offset|string_format:'%.2f'}%; width:max({$span.pct_width|string_format:'%.2f'}%, 2px)"
                                         title="{$span.name|escape}: {$span.duration_ms}мс (offset {$span.offset_ms}мс)">
                                    </div>
                                </div>
                                <span class="dbg-trace-dur">{$span.duration_ms} мс</span>
                            </div>
                            {if $span.detail}
                                <div class="dbg-trace-detail">{$span.detail|escape}</div>
                            {/if}
                        {/foreach}
                    </div>
                </div>
            </div>
        {/if}

        {* ==================== Tab: i18n ==================== *}
        <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-i18n">

            {if $debug_info.translation_missing|@count > 0}
                <div class="dbg-section">
                    <div class="dbg-section__head dbg-section__head--warn">
                        Ненайденные ключи
                        <span class="dbg-section__head-count">{$debug_info.translation_missing|@count}</span>
                    </div>
                    <div class="dbg-missing-keys">
                        {foreach from=$debug_info.translation_missing item=mk}
                            <span class="dbg-missing-key">{$mk|escape}</span>
                        {/foreach}
                    </div>
                </div>
            {/if}

            <div class="dbg-section">
                <div class="dbg-section__head dbg-section__head--collapsible" data-collapsible>
                    Использованные ключи
                    <span class="dbg-section__head-count">{$debug_info.translation_used|@count}</span>
                    <span class="dbg-section__arrow">▾</span>
                </div>
                <div class="dbg-section__body">
                    {if $debug_info.translation_used|@count > 0}
                        <div class="dbg-kv-grid">
                            {foreach from=$debug_info.translation_used key=tk item=tv}
                                <div class="dbg-kv-row">
                                    <span class="dbg-kv-key dbg-kv-key--param">{$tk|escape}</span>
                                    <span class="dbg-kv-val dbg-kv-val--mono">{$tv|escape}</span>
                                </div>
                            {/foreach}
                        </div>
                    {else}
                        <div class="dbg-empty">Нет использованных ключей</div>
                    {/if}
                </div>
            </div>

        </div>{* /Tab: i18n *}

        {* ==================== Tab: Env ==================== *}
        <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-env">
            <div class="dbg-section">
                <div class="dbg-section__head">Окружение</div>
                <div class="dbg-kv-grid">
                    {foreach from=$debug_info.env_snapshot key=ek item=ev}
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">{$ek|escape}</span>
                            <span class="dbg-kv-val dbg-kv-val--mono">
                                {if $ek === 'APP_ENV'}
                                    <span class="dbg-env-badge dbg-env-badge--{$ev}">{$ev|escape}</span>
                                {else}
                                    {$ev|escape}
                                {/if}
                            </span>
                        </div>
                    {/foreach}
                </div>
            </div>
            {if $debug_info.response_status !== null}
                <div class="dbg-section">
                    <div class="dbg-section__head">HTTP-ответ</div>
                    <div class="dbg-kv-grid">
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Статус</span>
                            <span class="dbg-kv-val">
                                <span class="dbg-method dbg-method--{if $debug_info.response_status < 300}get{elseif $debug_info.response_status < 400}patch{else}delete{/if}">
                                    {$debug_info.response_status}
                                </span>
                            </span>
                        </div>
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Размер</span>
                            <span class="dbg-kv-val dbg-kv-val--mono">
                                {$debug_info.response_size} Б
                                {if $debug_info.response_size >= 1024}
                                    ({math equation="round(size/1024,1)" size=$debug_info.response_size} КБ)
                                {/if}
                            </span>
                        </div>
                    </div>
                </div>
            {/if}
        </div>{* /Tab: Env *}

        {* ==================== Tab: SQL ==================== *}
        <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-sql">
            <div class="dbg-section">
                <div class="dbg-section__head">
                    SQL-запросы
                    {if $debug_info.sql_queries|@count > 0}
                        <span class="dbg-section__head-count">
                            {$debug_info.sql_queries|@count} запросов · {$debug_info.sql_total_time} мс
                        </span>
                    {/if}
                </div>
                {if $debug_info.sql_queries|@count > 0}
                    <div class="dbg-sq-list">
                        {foreach from=$debug_info.sql_queries item=q}
                            <div class="dbg-sq">
                                <div class="dbg-sq__head">
                                    <span class="dbg-sq__num">#{$q.num}</span>
                                    <span class="dbg-sq__type dbg-sq__type--{$q.type|lower}">{$q.type|escape}</span>
                                    <div class="dbg-sq__track">
                                        <div class="dbg-sq__fill dbg-sq__fill--{$q.time_cls}"
                                             style="width:{$q.time_pct|string_format:'%.1f'}%"></div>
                                    </div>
                                    <span class="dbg-sq__dur dbg-sq__dur--{$q.time_cls}">{$q.execution_time} мс</span>
                                </div>
                                <pre class="dbg-sq__sql">{$q.sql|escape}</pre>
                                {if $q.bindings|@count > 0}
                                    <div class="dbg-sq__bindings">
                                        <span class="dbg-sq__bindings-label">bindings</span>
                                        {foreach from=$q.bindings item=b}
                                            <span class="dbg-sq__binding">{$b|escape}</span>
                                        {/foreach}
                                    </div>
                                {/if}
                            </div>
                        {/foreach}
                    </div>
                {else}
                    <div class="dbg-empty">Нет SQL-запросов</div>
                {/if}
            </div>
        </div>

    </div>{* /dbg-body *}
</div>

<script nonce="{$csp_nonce}">
(function () {
    var panel  = document.getElementById('dbgPanel');
    var key    = 'dbg_open';
    var tabKey = 'dbg_tab';

    if (sessionStorage.getItem(key) === '1') {
        panel.classList.add('dbg-panel--open');
    }

    document.getElementById('dbgToggle').addEventListener('click', function () {
        var open = panel.classList.toggle('dbg-panel--open');
        sessionStorage.setItem(key, open ? '1' : '0');
    });

    function activateTab(btn, id) {
        panel.querySelectorAll('.dbg-tab').forEach(function (b) {
            b.classList.remove('dbg-tab--active');
        });
        panel.querySelectorAll('.dbg-tabpanel').forEach(function (p) {
            p.classList.add('dbg-tabpanel--hidden');
        });
        btn.classList.add('dbg-tab--active');
        var el = document.getElementById('dbgTab-' + id);
        if (el) el.classList.remove('dbg-tabpanel--hidden');
        sessionStorage.setItem(tabKey, id);
    }

    panel.addEventListener('click', function (e) {
        var tab = e.target.closest('[data-tab]');
        if (tab) {
            activateTab(tab, tab.dataset.tab);
            return;
        }
        var head = e.target.closest('[data-collapsible]');
        if (head) {
            var body = head.nextElementSibling;
            if (!body) return;
            var collapsed = body.classList.toggle('dbg-section__body--collapsed');
            var arrow = head.querySelector('.dbg-section__arrow');
            if (arrow) arrow.textContent = collapsed ? '▾' : '▴';
        }
    });

    // Восстановить активную вкладку после перезагрузки
    var savedTab = sessionStorage.getItem(tabKey);
    if (savedTab) {
        var btn = panel.querySelector('[data-tab="' + savedTab + '"]');
        if (btn) activateTab(btn, savedTab);
    }

    // Спейсер компенсирует высоту тогл-бара, чтобы он не перекрывал футер
    var spacer = document.getElementById('dbg-spacer');
    function updateSpacer() {
        var toggle = document.getElementById('dbgToggle');
        if (spacer && toggle) {
            spacer.style.height = toggle.offsetHeight + 'px';
        }
    }
    updateSpacer();
    window.addEventListener('resize', updateSpacer);
})();
</script>
