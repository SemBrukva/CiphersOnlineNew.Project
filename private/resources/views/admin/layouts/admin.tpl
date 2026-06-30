<!doctype html>
<html lang="{$current_locale}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title|default:'Admin'} — Admin</title>
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="manifest" href="/site.webmanifest" />
    {vite entry="private/resources/js/admin.js" type="css"}
</head>
<body class="d-flex min-vh-100 bg-light">

<nav id="admin-sidebar" class="d-flex flex-column flex-shrink-0 p-3 bg-dark text-white">
    <a href="{$admin_path}/" class="d-flex align-items-center mb-3 mb-md-0 text-white text-decoration-none">
        <i class="bi bi-shield-lock fs-4 me-2"></i>
        <span class="fs-5 fw-bold">Admin</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="{$admin_path}/" class="nav-link text-white{if $current_path === $admin_path || $current_path === $admin_path|cat:'/'} active{/if}">
                <i class="bi bi-speedometer2 me-2"></i>Дашборд
            </a>
        </li>
        <li class="nav-item">
            {assign var="redirects_prefix" value=$admin_path|cat:'/redirects'}
            <a href="{$admin_path}/redirects" class="nav-link text-white{if $current_path|starts_with:$redirects_prefix} active{/if}">
                <i class="bi bi-signpost-split me-2"></i>Редиректы
            </a>
        </li>
        <li class="nav-item">
            {assign var="cipher_categories_prefix" value=$admin_path|cat:'/cipher-categories'}
            <a href="{$admin_path}/cipher-categories" class="nav-link text-white{if $current_path|starts_with:$cipher_categories_prefix} active{/if}">
                <i class="bi bi-diagram-3 me-2"></i>Категории шифров
            </a>
        </li>
        <li class="nav-item">
            {assign var="ciphers_prefix" value=$admin_path|cat:'/ciphers'}
            <a href="{$admin_path}/ciphers" class="nav-link text-white{if $current_path|starts_with:$ciphers_prefix} active{/if}">
                <i class="bi bi-shield-lock me-2"></i>Шифры
            </a>
        </li>
        <li class="nav-item">
            {assign var="tools_overview_prefix" value=$admin_path|cat:'/tools-overview'}
            <a href="{$admin_path}/tools-overview" class="nav-link text-white{if $current_path|starts_with:$tools_overview_prefix} active{/if}">
                <i class="bi bi-bar-chart-line me-2"></i>Обзор инструментов
            </a>
        </li>
        <li class="nav-item">
            {assign var="semantic_core_prefix" value=$admin_path|cat:'/semantic-core'}
            <a href="{$admin_path}/semantic-core" class="nav-link text-white{if $current_path|starts_with:$semantic_core_prefix} active{/if}">
                <i class="bi bi-search me-2"></i>Семантика
            </a>
        </li>
        <li class="nav-item mt-auto">
            {assign var="settings_prefix" value=$admin_path|cat:'/settings'}
            <a href="{$admin_path}/settings" class="nav-link text-white{if $current_path|starts_with:$settings_prefix} active{/if}">
                <i class="bi bi-gear me-2"></i>Настройки
            </a>
        </li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
           data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2"></i>
            <span class="text-truncate">{$auth_user.name}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
            <li><a class="dropdown-item" href="/cabinet"><i class="bi bi-person me-1"></i>Кабинет</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{$locale_prefix}/logout" class="m-0">
                    <input type="hidden" name="_csrf_token" value="{$csrf_token}">
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="bi bi-box-arrow-right me-1"></i>Выйти
                    </button>
                </form>
            </li>
        </ul>
    </div>
</nav>

<div class="d-flex flex-column flex-grow-1 overflow-hidden">
    <header class="admin-topbar d-flex align-items-center px-4 py-2 bg-white border-bottom shadow-sm">
        <button id="sidebar-toggle" class="btn btn-sm btn-outline-secondary me-3" type="button">
            <i class="bi bi-list fs-5"></i>
        </button>
        <nav aria-label="breadcrumb" class="mb-0">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{$admin_path}/">Admin</a></li>
                {foreach $breadcrumbs as $crumb}
                    {if $crumb@last}
                        <li class="breadcrumb-item active" aria-current="page">{$crumb.label}</li>
                    {else}
                        <li class="breadcrumb-item"><a href="{$crumb.url}">{$crumb.label}</a></li>
                    {/if}
                {/foreach}
            </ol>
        </nav>
    </header>

    <main class="flex-grow-1 p-4 overflow-auto">
        {$content nofilter}
    </main>
</div>

{vite entry="private/resources/js/admin.js" type="js"}
</body>
</html>
