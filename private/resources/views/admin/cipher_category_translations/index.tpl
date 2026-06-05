<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Переводы категорий</h1>
    <a href="{$admin_path}/cipher-category-translations/create" class="btn btn-primary">
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
        {if $translations}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width:60px">ID</th>
                        <th style="width:200px">Категория</th>
                        <th style="width:90px">Язык</th>
                        <th>Название</th>
                        <th style="width:160px">Изменён</th>
                        <th style="width:110px"></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $translations as $translation}
                    <tr>
                        <td class="ps-4 text-muted small">{$translation.id}</td>
                        <td class="font-monospace small">{$translation.category_alias}</td>
                        <td><span class="badge bg-light text-dark border">{$translation.language}</span></td>
                        <td>{$translation.name}</td>
                        <td class="text-muted small">{$translation.updated_at}</td>
                        <td class="text-end pe-3">
                            <a href="{$admin_path}/cipher-category-translations/{$translation.id}/edit"
                               class="btn btn-sm btn-outline-secondary me-1" title="Редактировать">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{$admin_path}/cipher-category-translations/{$translation.id}/delete"
                                  class="d-inline"
                                  onsubmit="return confirm('Удалить перевод категории?')">
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
            <i class="bi bi-translate fs-2 d-block mb-2"></i>
            Переводов пока нет.
            <a href="{$admin_path}/cipher-category-translations/create">Добавить первый</a>
        </div>
        {/if}
    </div>
</div>
