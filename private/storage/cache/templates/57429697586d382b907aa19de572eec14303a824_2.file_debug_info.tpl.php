<?php
/* Smarty version 5.8.0, created on 2026-05-24 11:04:44
  from 'file:partials/debug_info.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12db4c614088_49983637',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '57429697586d382b907aa19de572eec14303a824' => 
    array (
      0 => 'partials/debug_info.tpl',
      1 => 1779620569,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a12db4c614088_49983637 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/partials';
if ($_smarty_tpl->getValue('debug_info')['execution_time'] < 100) {?>
    <?php $_smarty_tpl->assign('_dbg_time_cls', "green", false, NULL);
} elseif ($_smarty_tpl->getValue('debug_info')['execution_time'] < 500) {?>
    <?php $_smarty_tpl->assign('_dbg_time_cls', "yellow", false, NULL);
} else { ?>
    <?php $_smarty_tpl->assign('_dbg_time_cls', "red", false, NULL);
}?>

<?php if ($_smarty_tpl->getValue('debug_info')['sql_total_time'] < 50) {?>
    <?php $_smarty_tpl->assign('_dbg_sql_cls', "blue", false, NULL);
} elseif ($_smarty_tpl->getValue('debug_info')['sql_total_time'] < 200) {?>
    <?php $_smarty_tpl->assign('_dbg_sql_cls', "yellow", false, NULL);
} else { ?>
    <?php $_smarty_tpl->assign('_dbg_sql_cls', "red", false, NULL);
}?>

<?php if ($_smarty_tpl->getValue('debug_info')['memory_peak'] < 16) {?>
    <?php $_smarty_tpl->assign('_dbg_mem_cls', "green", false, NULL);
} elseif ($_smarty_tpl->getValue('debug_info')['memory_peak'] < 64) {?>
    <?php $_smarty_tpl->assign('_dbg_mem_cls', "yellow", false, NULL);
} else { ?>
    <?php $_smarty_tpl->assign('_dbg_mem_cls', "red", false, NULL);
}?>

<div id="dbg-spacer" style="flex-shrink:0" aria-hidden="true"></div>
<div class="dbg-panel" id="dbgPanel">

        <div class="dbg-toggle" id="dbgToggle">
        <span class="dbg-toggle__icon">🐞</span>
        <span class="dbg-toggle__label">Debug</span>

        <div class="dbg-toggle__metrics">
            <span class="dbg-badge dbg-badge--<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('_dbg_time_cls')), ENT_QUOTES, 'UTF-8');?>
">
                <span class="dbg-badge__dot"></span>
                <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['execution_time']), ENT_QUOTES, 'UTF-8');?>
 мс
            </span>
            <span class="dbg-badge dbg-badge--<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('_dbg_mem_cls')), ENT_QUOTES, 'UTF-8');?>
">
                <span class="dbg-badge__dot"></span>
                <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['memory_peak']), ENT_QUOTES, 'UTF-8');?>
 МБ
            </span>
            <span class="dbg-badge dbg-badge--<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('_dbg_sql_cls')), ENT_QUOTES, 'UTF-8');?>
">
                <span class="dbg-badge__dot"></span>
                SQL <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['sql_total_time']), ENT_QUOTES, 'UTF-8');?>
 мс
            </span>
            <span class="dbg-badge">
                <span class="dbg-badge__dot"></span>
                <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['loaded_files']), ENT_QUOTES, 'UTF-8');?>
 файлов
            </span>
            <?php if ($_smarty_tpl->getValue('debug_info')['is_auth']) {?>
                <span class="dbg-badge dbg-badge--purple">
                    <span class="dbg-badge__dot"></span>
                    <?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('debug_info')['user_name'] ?? null)===null||$tmp==='' ? $_smarty_tpl->getValue('debug_info')['user_email'] ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>

                    <?php if ($_smarty_tpl->getValue('debug_info')['is_admin']) {?> ★<?php }?>
                </span>
            <?php }?>
            <?php if ($_smarty_tpl->getValue('debug_info')['memcached_usage'] !== null) {?>
                <span class="dbg-badge dbg-badge--blue">
                    <span class="dbg-badge__dot"></span>
                    MC <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['memcached_usage']), ENT_QUOTES, 'UTF-8');?>
/<?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('debug_info')['memcached_total'] ?? null)===null||$tmp==='' ? '?' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
 MB
                </span>
            <?php }?>
            <?php if ($_smarty_tpl->getValue('debug_info')['response_status'] !== null) {?>
                <span class="dbg-badge dbg-badge--<?php if ($_smarty_tpl->getValue('debug_info')['response_status'] < 300) {?>green<?php } elseif ($_smarty_tpl->getValue('debug_info')['response_status'] < 400) {?>yellow<?php } else { ?>red<?php }?>">
                    <span class="dbg-badge__dot"></span>
                    HTTP <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['response_status']), ENT_QUOTES, 'UTF-8');?>

                </span>
            <?php }?>
            <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['translation_missing']) > 0) {?>
                <span class="dbg-badge dbg-badge--yellow">
                    <span class="dbg-badge__dot"></span>
                    <?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['translation_missing'])), ENT_QUOTES, 'UTF-8');?>
 missing i18n
                </span>
            <?php }?>
        </div>

        <span class="dbg-toggle__arrow">▲</span>
    </div>

        <div class="dbg-body" id="dbgBody">

                <div class="dbg-metrics">
            <div class="dbg-metric dbg-metric--<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('_dbg_time_cls')), ENT_QUOTES, 'UTF-8');?>
">
                <span class="dbg-metric__label">Время выполнения</span>
                <span class="dbg-metric__value"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['execution_time']), ENT_QUOTES, 'UTF-8');?>
 <span class="dbg-metric__unit">мс</span></span>
            </div>
            <div class="dbg-metric dbg-metric--<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('_dbg_mem_cls')), ENT_QUOTES, 'UTF-8');?>
">
                <span class="dbg-metric__label">Пиковая память</span>
                <span class="dbg-metric__value"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['memory_peak']), ENT_QUOTES, 'UTF-8');?>
 <span class="dbg-metric__unit">МБ</span></span>
            </div>
            <div class="dbg-metric">
                <span class="dbg-metric__label">Текущая память</span>
                <span class="dbg-metric__value"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['memory_usage']), ENT_QUOTES, 'UTF-8');?>
 <span class="dbg-metric__unit">МБ</span></span>
            </div>
            <div class="dbg-metric dbg-metric--<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('_dbg_sql_cls')), ENT_QUOTES, 'UTF-8');?>
">
                <span class="dbg-metric__label">Время SQL</span>
                <span class="dbg-metric__value"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['sql_total_time']), ENT_QUOTES, 'UTF-8');?>
 <span class="dbg-metric__unit">мс</span></span>
            </div>
            <div class="dbg-metric">
                <span class="dbg-metric__label">PHP-файлов</span>
                <span class="dbg-metric__value"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['loaded_files']), ENT_QUOTES, 'UTF-8');?>
</span>
            </div>
            <?php if ($_smarty_tpl->getValue('debug_info')['memcached_usage'] !== null) {?>
                <div class="dbg-metric dbg-metric--blue">
                    <span class="dbg-metric__label">Memcached</span>
                    <span class="dbg-metric__value"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['memcached_usage']), ENT_QUOTES, 'UTF-8');?>
 <span class="dbg-metric__unit">/ <?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('debug_info')['memcached_total'] ?? null)===null||$tmp==='' ? '?' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
 MB</span></span>
                </div>
            <?php }?>
            <?php $_smarty_tpl->assign('_dbg_cache_total', $_smarty_tpl->getValue('debug_info')['cache_stats']['hits']+$_smarty_tpl->getValue('debug_info')['cache_stats']['misses'], false, NULL);?>
            <?php if ($_smarty_tpl->getValue('_dbg_cache_total') > 0) {?>
                <?php if ($_smarty_tpl->getValue('debug_info')['cache_stats']['misses'] === 0) {?>
                    <?php $_smarty_tpl->assign('_dbg_cache_cls', "green", false, NULL);?>
                <?php } elseif ($_smarty_tpl->getValue('debug_info')['cache_stats']['hits'] > $_smarty_tpl->getValue('debug_info')['cache_stats']['misses']) {?>
                    <?php $_smarty_tpl->assign('_dbg_cache_cls', "yellow", false, NULL);?>
                <?php } else { ?>
                    <?php $_smarty_tpl->assign('_dbg_cache_cls', "red", false, NULL);?>
                <?php }?>
                <div class="dbg-metric dbg-metric--<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('_dbg_cache_cls')), ENT_QUOTES, 'UTF-8');?>
">
                    <span class="dbg-metric__label">Cache hit/miss</span>
                    <span class="dbg-metric__value"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['cache_stats']['hits']), ENT_QUOTES, 'UTF-8');?>
<span class="dbg-metric__unit"> / <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['cache_stats']['misses']), ENT_QUOTES, 'UTF-8');?>
</span></span>
                </div>
            <?php }?>
            <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['php_errors']) > 0) {?>
                <div class="dbg-metric dbg-metric--red">
                    <span class="dbg-metric__label">PHP-ошибки</span>
                    <span class="dbg-metric__value"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['php_errors'])), ENT_QUOTES, 'UTF-8');?>
</span>
                </div>
            <?php }?>
            <?php if ($_smarty_tpl->getValue('debug_info')['response_status'] !== null) {?>
                <?php if ($_smarty_tpl->getValue('debug_info')['response_status'] < 300) {?>
                    <?php $_smarty_tpl->assign('_dbg_resp_cls', "green", false, NULL);?>
                <?php } elseif ($_smarty_tpl->getValue('debug_info')['response_status'] < 400) {?>
                    <?php $_smarty_tpl->assign('_dbg_resp_cls', "yellow", false, NULL);?>
                <?php } else { ?>
                    <?php $_smarty_tpl->assign('_dbg_resp_cls', "red", false, NULL);?>
                <?php }?>
                <div class="dbg-metric dbg-metric--<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('_dbg_resp_cls')), ENT_QUOTES, 'UTF-8');?>
">
                    <span class="dbg-metric__label">HTTP-статус</span>
                    <span class="dbg-metric__value"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['response_status']), ENT_QUOTES, 'UTF-8');?>
</span>
                </div>
                <div class="dbg-metric">
                    <span class="dbg-metric__label">Размер ответа</span>
                    <span class="dbg-metric__value">
                        <?php if ($_smarty_tpl->getValue('debug_info')['response_size'] >= 1024) {?>
                            <?php echo $_smarty_tpl->getSmarty()->getFunctionHandler('math')->handle(array('equation'=>"round(size/1024,1)",'size'=>$_smarty_tpl->getValue('debug_info')['response_size']), $_smarty_tpl);?>
 <span class="dbg-metric__unit">КБ</span>
                        <?php } else { ?>
                            <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['response_size']), ENT_QUOTES, 'UTF-8');?>
 <span class="dbg-metric__unit">Б</span>
                        <?php }?>
                    </span>
                </div>
            <?php }?>
            <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['translation_missing']) > 0) {?>
                <div class="dbg-metric dbg-metric--yellow">
                    <span class="dbg-metric__label">Пропущ. перев.</span>
                    <span class="dbg-metric__value"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['translation_missing'])), ENT_QUOTES, 'UTF-8');?>
</span>
                </div>
            <?php }?>
        </div>

                <div class="dbg-tabs">
            <button class="dbg-tab dbg-tab--active" data-tab="request">Запрос</button>
            <button class="dbg-tab" data-tab="route">Маршрут</button>
            <button class="dbg-tab" data-tab="session">Сессия</button>
            <?php if ($_smarty_tpl->getValue('_dbg_cache_total') > 0) {?>
                <button class="dbg-tab" data-tab="cache">Cache</button>
            <?php }?>
            <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['php_errors']) > 0) {?>
                <button class="dbg-tab dbg-tab--error" data-tab="errors">
                    Ошибки <span class="dbg-tab__count"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['php_errors'])), ENT_QUOTES, 'UTF-8');?>
</span>
                </button>
            <?php }?>
            <button class="dbg-tab" data-tab="i18n">
                Переводы
                <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['translation_missing']) > 0) {?>
                    <span class="dbg-tab__count dbg-tab__count--warn"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['translation_missing'])), ENT_QUOTES, 'UTF-8');?>
</span>
                <?php }?>
            </button>
            <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['timeline']) > 0) {?>
                <button class="dbg-tab" data-tab="timeline">Timeline</button>
            <?php }?>
            <button class="dbg-tab" data-tab="sql">SQL</button>
            <button class="dbg-tab" data-tab="env">Env</button>
        </div>

                <div class="dbg-tabpanel" id="dbgTab-request">

                        <div class="dbg-section">
                <div class="dbg-section__head">HTTP-запрос</div>
                <div class="dbg-kv-grid">
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Метод</span>
                        <span class="dbg-kv-val">
                            <span class="dbg-method dbg-method--<?php echo htmlspecialchars((string) (mb_strtolower((string) $_smarty_tpl->getValue('debug_info')['method'], 'UTF-8')), ENT_QUOTES, 'UTF-8');?>
"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['method']), ENT_QUOTES, 'UTF-8');?>
</span>
                        </span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">URI</span>
                        <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['full_uri']), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">IP</span>
                        <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['ip']), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <?php if ($_smarty_tpl->getValue('debug_info')['referer']) {?>
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Referer</span>
                            <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['referer']), ENT_QUOTES, 'UTF-8');?>
</span>
                        </div>
                    <?php }?>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">User-Agent</span>
                        <span class="dbg-kv-val dbg-kv-val--mono dbg-kv-val--wrap"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['user_agent']), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Время</span>
                        <span class="dbg-kv-val"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['timestamp']), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Окружение</span>
                        <span class="dbg-kv-val">
                            <span class="dbg-env-badge dbg-env-badge--<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['app_env']), ENT_QUOTES, 'UTF-8');?>
"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['app_env']), ENT_QUOTES, 'UTF-8');?>
</span>
                        </span>
                    </div>
                </div>
            </div>

                        <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['get_params']) > 0) {?>
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        GET-параметры
                        <span class="dbg-section__head-count"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['get_params'])), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-kv-grid">
                        <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('debug_info')['get_params'], 'v', false, 'k');
$foreach2DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('k')->value => $_smarty_tpl->getVariable('v')->value) {
$foreach2DoElse = false;
?>
                            <div class="dbg-kv-row">
                                <span class="dbg-kv-key dbg-kv-key--param"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('k')), ENT_QUOTES, 'UTF-8');?>
</span>
                                <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('v')), ENT_QUOTES, 'UTF-8');?>
</span>
                            </div>
                        <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                    </div>
                </div>
            <?php }?>

                        <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['post_params']) > 0) {?>
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        POST-параметры
                        <span class="dbg-section__head-count"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['post_params'])), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-kv-grid">
                        <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('debug_info')['post_params'], 'v', false, 'k');
$foreach3DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('k')->value => $_smarty_tpl->getVariable('v')->value) {
$foreach3DoElse = false;
?>
                            <div class="dbg-kv-row">
                                <span class="dbg-kv-key dbg-kv-key--param"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('k')), ENT_QUOTES, 'UTF-8');?>
</span>
                                <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('v')), ENT_QUOTES, 'UTF-8');?>
</span>
                            </div>
                        <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                    </div>
                </div>
            <?php }?>

                        <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['headers']) > 0) {?>
                <div class="dbg-section">
                    <div class="dbg-section__head dbg-section__head--collapsible" data-collapsible>
                        Заголовки запроса
                        <span class="dbg-section__head-count"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['headers'])), ENT_QUOTES, 'UTF-8');?>
</span>
                        <span class="dbg-section__arrow">▾</span>
                    </div>
                    <div class="dbg-section__body dbg-section__body--collapsed">
                        <div class="dbg-kv-grid">
                            <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('debug_info')['headers'], 'v', false, 'k');
$foreach4DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('k')->value => $_smarty_tpl->getVariable('v')->value) {
$foreach4DoElse = false;
?>
                                <div class="dbg-kv-row">
                                    <span class="dbg-kv-key"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('k')), ENT_QUOTES, 'UTF-8');?>
</span>
                                    <span class="dbg-kv-val dbg-kv-val--mono dbg-kv-val--wrap"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('v')), ENT_QUOTES, 'UTF-8');?>
</span>
                                </div>
                            <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                        </div>
                    </div>
                </div>
            <?php }?>

        </div>
                <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-route">

            <div class="dbg-section">
                <div class="dbg-section__head">Совпавший маршрут</div>
                <div class="dbg-kv-grid">
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Паттерн</span>
                        <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('debug_info')['route_pattern'] ?? null)===null||$tmp==='' ? '—' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Контроллер</span>
                        <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('debug_info')['route_controller'] ?? null)===null||$tmp==='' ? '—' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Метод</span>
                        <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('debug_info')['route_action'] ?? null)===null||$tmp==='' ? '—' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                </div>
            </div>

            <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['route_middleware']) > 0) {?>
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        Middleware маршрута
                        <span class="dbg-section__head-count"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['route_middleware'])), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-steps">
                        <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('debug_info')['route_middleware'], 'mw');
$foreach5DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('mw')->value) {
$foreach5DoElse = false;
?>
                            <div class="dbg-step">
                                <span class="dbg-step__name"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('mw')), ENT_QUOTES, 'UTF-8');?>
</span>
                            </div>
                        <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                    </div>
                </div>
            <?php }?>

        </div>
                <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-session">

            <div class="dbg-section">
                <div class="dbg-section__head">Сессия</div>
                <div class="dbg-kv-grid">
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Session ID</span>
                        <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('debug_info')['session_id'] ?? null)===null||$tmp==='' ? '—' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">CSRF</span>
                        <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('debug_info')['session_data']['csrf'] ?? null)===null||$tmp==='' ? '—' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-kv-row">
                        <span class="dbg-kv-key">Пользователь</span>
                        <span class="dbg-kv-val">
                            <?php if ($_smarty_tpl->getValue('debug_info')['is_auth']) {?>
                                <span class="dbg-badge dbg-badge--purple">
                                    #<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['user_id']), ENT_QUOTES, 'UTF-8');?>
 <?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('debug_info')['user_name'] ?? null)===null||$tmp==='' ? '' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>

                                    <?php if ($_smarty_tpl->getValue('debug_info')['is_admin']) {?> ★ admin<?php }?>
                                </span>
                            <?php } else { ?>
                                <span class="dbg-kv-val--dim">гость</span>
                            <?php }?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['session_data']['data']) > 0) {?>
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        Данные сессии
                        <span class="dbg-section__head-count"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['session_data']['data'])), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-kv-grid">
                        <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('debug_info')['session_data']['data'], 'v', false, 'k');
$foreach6DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('k')->value => $_smarty_tpl->getVariable('v')->value) {
$foreach6DoElse = false;
?>
                            <div class="dbg-kv-row">
                                <span class="dbg-kv-key dbg-kv-key--param"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('k')), ENT_QUOTES, 'UTF-8');?>
</span>
                                <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) (json_encode($_smarty_tpl->getValue('v'))), ENT_QUOTES, 'UTF-8');?>
</span>
                            </div>
                        <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                    </div>
                </div>
            <?php }?>

            <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['session_data']['flash']) > 0) {?>
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        Flash-сообщения
                        <span class="dbg-section__head-count"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['session_data']['flash'])), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-kv-grid">
                        <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('debug_info')['session_data']['flash'], 'v', false, 'k');
$foreach7DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('k')->value => $_smarty_tpl->getVariable('v')->value) {
$foreach7DoElse = false;
?>
                            <div class="dbg-kv-row">
                                <span class="dbg-kv-key dbg-kv-key--param"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('k')), ENT_QUOTES, 'UTF-8');?>
</span>
                                <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) (json_encode($_smarty_tpl->getValue('v'))), ENT_QUOTES, 'UTF-8');?>
</span>
                            </div>
                        <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                    </div>
                </div>
            <?php }?>

        </div>
                <?php if ($_smarty_tpl->getValue('_dbg_cache_total') > 0) {?>
            <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-cache">
                <div class="dbg-section">
                    <div class="dbg-section__head">Статистика кеша</div>
                    <div class="dbg-kv-grid">
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Hits</span>
                            <span class="dbg-kv-val">
                                <span class="dbg-cache-num dbg-cache-num--hit"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['cache_stats']['hits']), ENT_QUOTES, 'UTF-8');?>
</span>
                            </span>
                        </div>
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Misses</span>
                            <span class="dbg-kv-val">
                                <span class="dbg-cache-num dbg-cache-num--miss"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['cache_stats']['misses']), ENT_QUOTES, 'UTF-8');?>
</span>
                            </span>
                        </div>
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Ratio</span>
                            <span class="dbg-kv-val dbg-kv-val--mono">
                                <?php if ($_smarty_tpl->getValue('_dbg_cache_total') > 0) {?>
                                    <?php echo $_smarty_tpl->getSmarty()->getFunctionHandler('math')->handle(array('equation'=>"round(hits / total * 100, 1)",'hits'=>$_smarty_tpl->getValue('debug_info')['cache_stats']['hits'],'total'=>$_smarty_tpl->getValue('_dbg_cache_total')), $_smarty_tpl);?>
%
                                <?php } else { ?>
                                    —
                                <?php }?>
                            </span>
                        </div>
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Драйвер</span>
                            <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['cache_driver']), ENT_QUOTES, 'UTF-8');?>
</span>
                        </div>
                    </div>
                </div>
                <div class="dbg-section">
                    <div class="dbg-section__head">Hit ratio</div>
                    <div class="dbg-cache-bar-wrap">
                        <?php if ($_smarty_tpl->getValue('_dbg_cache_total') > 0) {?>
                            <?php $_smarty_tpl->assign('_dbg_hit_pct', $_smarty_tpl->getValue('debug_info')['cache_stats']['hits']/$_smarty_tpl->getValue('_dbg_cache_total')*100, false, NULL);?>
                        <?php } else { ?>
                            <?php $_smarty_tpl->assign('_dbg_hit_pct', 0, false, NULL);?>
                        <?php }?>
                        <div class="dbg-cache-bar">
                            <div class="dbg-cache-bar__fill" style="width:<?php echo htmlspecialchars((string) (sprintf('%.1f',$_smarty_tpl->getValue('_dbg_hit_pct'))), ENT_QUOTES, 'UTF-8');?>
%"></div>
                        </div>
                        <span class="dbg-cache-bar__label">
                            <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['cache_stats']['hits']), ENT_QUOTES, 'UTF-8');?>
 hit / <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['cache_stats']['misses']), ENT_QUOTES, 'UTF-8');?>
 miss
                        </span>
                    </div>
                </div>
            </div>
        <?php }?>

                <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['php_errors']) > 0) {?>
            <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-errors">
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        PHP-ошибки
                        <span class="dbg-section__head-count"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['php_errors'])), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-errors">
                        <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('debug_info')['php_errors'], 'err');
$foreach8DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('err')->value) {
$foreach8DoElse = false;
?>
                            <div class="dbg-error">
                                <div class="dbg-error__head">
                                    <span class="dbg-error__level dbg-error__level--<?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('replace')(mb_strtolower((string) $_smarty_tpl->getValue('err')['level'], 'UTF-8'),' ','-')), ENT_QUOTES, 'UTF-8');?>
"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('err')['level']), ENT_QUOTES, 'UTF-8');?>
</span>
                                    <span class="dbg-error__location"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('err')['file']), ENT_QUOTES, 'UTF-8');?>
:<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('err')['line']), ENT_QUOTES, 'UTF-8');?>
</span>
                                </div>
                                <div class="dbg-error__message"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('err')['message']), ENT_QUOTES, 'UTF-8');?>
</div>
                            </div>
                        <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                    </div>
                </div>
            </div>
        <?php }?>

                <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['timeline']) > 0) {?>
            <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-timeline">
                <div class="dbg-section">
                    <div class="dbg-section__head">
                        Timeline запроса
                        <span class="dbg-section__head-count"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['timeline'])), ENT_QUOTES, 'UTF-8');?>
 spans · <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['execution_time']), ENT_QUOTES, 'UTF-8');?>
 мс всего</span>
                    </div>

                                        <div class="dbg-tl-legend">
                        <span class="dbg-tl-legend-item dbg-tl-legend-item--middleware">middleware</span>
                        <span class="dbg-tl-legend-item dbg-tl-legend-item--controller">controller</span>
                        <span class="dbg-tl-legend-item dbg-tl-legend-item--sql">sql</span>
                        <span class="dbg-tl-legend-item dbg-tl-legend-item--app">app</span>
                    </div>

                    <div class="dbg-trace">
                        <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('debug_info')['timeline'], 'span');
$foreach9DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('span')->value) {
$foreach9DoElse = false;
?>
                            <div class="dbg-trace-row">
                                <span class="dbg-trace-name" title="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('span')['name']), ENT_QUOTES, 'UTF-8');?>
"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('span')['name']), ENT_QUOTES, 'UTF-8');?>
</span>
                                <div class="dbg-trace-track">
                                    <div class="dbg-trace-bar dbg-trace-bar--<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('span')['category']), ENT_QUOTES, 'UTF-8');?>
"
                                         style="margin-left:<?php echo htmlspecialchars((string) (sprintf('%.2f',$_smarty_tpl->getValue('span')['pct_offset'])), ENT_QUOTES, 'UTF-8');?>
%; width:max(<?php echo htmlspecialchars((string) (sprintf('%.2f',$_smarty_tpl->getValue('span')['pct_width'])), ENT_QUOTES, 'UTF-8');?>
%, 2px)"
                                         title="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('span')['name']), ENT_QUOTES, 'UTF-8');?>
: <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('span')['duration_ms']), ENT_QUOTES, 'UTF-8');?>
мс (offset <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('span')['offset_ms']), ENT_QUOTES, 'UTF-8');?>
мс)">
                                    </div>
                                </div>
                                <span class="dbg-trace-dur"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('span')['duration_ms']), ENT_QUOTES, 'UTF-8');?>
 мс</span>
                            </div>
                            <?php if ($_smarty_tpl->getValue('span')['detail']) {?>
                                <div class="dbg-trace-detail"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('span')['detail']), ENT_QUOTES, 'UTF-8');?>
</div>
                            <?php }?>
                        <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                    </div>
                </div>
            </div>
        <?php }?>

                <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-i18n">

            <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['translation_missing']) > 0) {?>
                <div class="dbg-section">
                    <div class="dbg-section__head dbg-section__head--warn">
                        Ненайденные ключи
                        <span class="dbg-section__head-count"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['translation_missing'])), ENT_QUOTES, 'UTF-8');?>
</span>
                    </div>
                    <div class="dbg-missing-keys">
                        <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('debug_info')['translation_missing'], 'mk');
$foreach10DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('mk')->value) {
$foreach10DoElse = false;
?>
                            <span class="dbg-missing-key"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('mk')), ENT_QUOTES, 'UTF-8');?>
</span>
                        <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                    </div>
                </div>
            <?php }?>

            <div class="dbg-section">
                <div class="dbg-section__head dbg-section__head--collapsible" data-collapsible>
                    Использованные ключи
                    <span class="dbg-section__head-count"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['translation_used'])), ENT_QUOTES, 'UTF-8');?>
</span>
                    <span class="dbg-section__arrow">▾</span>
                </div>
                <div class="dbg-section__body">
                    <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('debug_info')['translation_used']) > 0) {?>
                        <div class="dbg-kv-grid">
                            <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('debug_info')['translation_used'], 'tv', false, 'tk');
$foreach11DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('tk')->value => $_smarty_tpl->getVariable('tv')->value) {
$foreach11DoElse = false;
?>
                                <div class="dbg-kv-row">
                                    <span class="dbg-kv-key dbg-kv-key--param"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('tk')), ENT_QUOTES, 'UTF-8');?>
</span>
                                    <span class="dbg-kv-val dbg-kv-val--mono"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('tv')), ENT_QUOTES, 'UTF-8');?>
</span>
                                </div>
                            <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                        </div>
                    <?php } else { ?>
                        <div class="dbg-empty">Нет использованных ключей</div>
                    <?php }?>
                </div>
            </div>

        </div>
                <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-env">
            <div class="dbg-section">
                <div class="dbg-section__head">Окружение</div>
                <div class="dbg-kv-grid">
                    <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('debug_info')['env_snapshot'], 'ev', false, 'ek');
$foreach12DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('ek')->value => $_smarty_tpl->getVariable('ev')->value) {
$foreach12DoElse = false;
?>
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('ek')), ENT_QUOTES, 'UTF-8');?>
</span>
                            <span class="dbg-kv-val dbg-kv-val--mono">
                                <?php if ($_smarty_tpl->getValue('ek') === 'APP_ENV') {?>
                                    <span class="dbg-env-badge dbg-env-badge--<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('ev')), ENT_QUOTES, 'UTF-8');?>
"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('ev')), ENT_QUOTES, 'UTF-8');?>
</span>
                                <?php } else { ?>
                                    <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('ev')), ENT_QUOTES, 'UTF-8');?>

                                <?php }?>
                            </span>
                        </div>
                    <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                </div>
            </div>
            <?php if ($_smarty_tpl->getValue('debug_info')['response_status'] !== null) {?>
                <div class="dbg-section">
                    <div class="dbg-section__head">HTTP-ответ</div>
                    <div class="dbg-kv-grid">
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Статус</span>
                            <span class="dbg-kv-val">
                                <span class="dbg-method dbg-method--<?php if ($_smarty_tpl->getValue('debug_info')['response_status'] < 300) {?>get<?php } elseif ($_smarty_tpl->getValue('debug_info')['response_status'] < 400) {?>patch<?php } else { ?>delete<?php }?>">
                                    <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['response_status']), ENT_QUOTES, 'UTF-8');?>

                                </span>
                            </span>
                        </div>
                        <div class="dbg-kv-row">
                            <span class="dbg-kv-key">Размер</span>
                            <span class="dbg-kv-val dbg-kv-val--mono">
                                <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['response_size']), ENT_QUOTES, 'UTF-8');?>
 Б
                                <?php if ($_smarty_tpl->getValue('debug_info')['response_size'] >= 1024) {?>
                                    (<?php echo $_smarty_tpl->getSmarty()->getFunctionHandler('math')->handle(array('equation'=>"round(size/1024,1)",'size'=>$_smarty_tpl->getValue('debug_info')['response_size']), $_smarty_tpl);?>
 КБ)
                                <?php }?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php }?>
        </div>
                <div class="dbg-tabpanel dbg-tabpanel--hidden" id="dbgTab-sql">
            <div class="dbg-section">
                <div class="dbg-section__head">SQL-запросы</div>
                <div class="dbg-sql">
                    <?php if ($_smarty_tpl->getValue('debug_info')['trace']) {?>
                        <pre class="dbg-pre"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('debug_info')['trace']), ENT_QUOTES, 'UTF-8');?>
</pre>
                    <?php } else { ?>
                        <pre class="dbg-pre dbg-pre--empty">(нет SQL-запросов)</pre>
                    <?php }?>
                </div>
            </div>
        </div>

    </div></div>

<?php echo '<script'; ?>
 nonce="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('csp_nonce')), ENT_QUOTES, 'UTF-8');?>
">
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
<?php echo '</script'; ?>
>
<?php }
}
