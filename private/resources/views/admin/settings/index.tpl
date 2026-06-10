<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Системные настройки</h1>
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

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0"><i class="bi bi-database me-2"></i>Кеш</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-1">
            Драйвер: <strong>{$cache_driver}</strong>
        </p>
        <p class="text-muted mb-4">
            Сброс очищает кеш редиректов, навигации, шифров и главной страницы.
            Сессии пользователей не затрагиваются.
        </p>
        <form method="POST" action="{$admin_path}/settings/flush-cache"
              onsubmit="return confirm('Сбросить кеш приложения?')">
            <input type="hidden" name="_csrf_token" value="{$csrf_token}">
            <button type="submit" class="btn btn-warning">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Сбросить кеш
            </button>
        </form>
    </div>
</div>
