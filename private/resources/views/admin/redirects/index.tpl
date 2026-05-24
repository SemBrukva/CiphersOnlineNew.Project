<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Редиректы</h1>
    <a href="{$admin_path}/redirects/create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Добавить
    </a>
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

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        {if $redirects}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width:60px">ID</th>
                        <th>Откуда</th>
                        <th>Куда</th>
                        <th style="width:80px">Код</th>
                        <th style="width:90px">Активен</th>
                        <th style="width:100px">Переходов</th>
                        <th style="width:160px">Изменён</th>
                        <th style="width:110px"></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $redirects as $r}
                    <tr>
                        <td class="ps-4 text-muted small">{$r.id}</td>
                        <td class="font-monospace small text-break">{$r.from_path}</td>
                        <td class="font-monospace small text-break">{$r.to_path}</td>
                        <td>
                            <span class="badge {if $r.status_code == 301}bg-secondary{else}bg-info text-dark{/if}">
                                {$r.status_code}
                            </span>
                        </td>
                        <td>
                            {if $r.is_active}
                                <span class="badge bg-success">Да</span>
                            {else}
                                <span class="badge bg-secondary">Нет</span>
                            {/if}
                        </td>
                        <td class="text-muted">{$r.hit_count}</td>
                        <td class="text-muted small">{$r.updated_at}</td>
                        <td class="text-end pe-3">
                            <a href="{$admin_path}/redirects/{$r.id}/edit"
                               class="btn btn-sm btn-outline-secondary me-1" title="Редактировать">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{$admin_path}/redirects/{$r.id}/delete"
                                  class="d-inline"
                                  onsubmit="return confirm('Удалить редирект?')">
                                <input type="hidden" name="_csrf_token" value="{$csrf_token}">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {else}
        <div class="p-5 text-center text-muted">
            <i class="bi bi-signpost-split fs-2 d-block mb-2"></i>
            Редиректов пока нет.
            <a href="{$admin_path}/redirects/create">Добавить первый</a>
        </div>
        {/if}
    </div>
</div>
