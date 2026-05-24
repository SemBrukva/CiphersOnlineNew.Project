<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Шифры</h1>
    <a href="{$admin_path}/ciphers/create" class="btn btn-primary">
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
        {if $ciphers}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width:60px">ID</th>
                        <th>Название</th>
                        <th style="width:330px">Переводы</th>
                        <th style="width:110px">Опубликован</th>
                        <th style="width:120px">Сортировка</th>
                        <th style="width:110px"></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $ciphers as $cipher}
                    <tr>
                        <td class="ps-4 text-muted small">{$cipher.id}</td>
                        <td>
                            <div class="fw-medium">{$cipher.title|default:'—'}</div>
                            <div class="font-monospace small text-muted">{$cipher.alias}</div>
                        </td>
                        <td>
                            {foreach $available_languages as $language}
                                {if isset($cipher_languages[$cipher.id]) && isset($cipher_languages[$cipher.id][$language])}
                                    <a href="{$admin_path}/ciphers/{$cipher.id}/edit?language={$language|escape:'url'}"
                                       class="badge bg-success me-1 text-decoration-none">{$language|upper}</a>
                                {else}
                                    <a href="{$admin_path}/ciphers/{$cipher.id}/edit?language={$language|escape:'url'}"
                                       class="badge bg-secondary me-1 text-decoration-none">{$language|upper}</a>
                                {/if}
                            {/foreach}
                        </td>
                        <td>
                            {if $cipher.published}
                                <span class="badge bg-success">Да</span>
                            {else}
                                <span class="badge bg-secondary">Нет</span>
                            {/if}
                        </td>
                        <td>{$cipher.sort_order}</td>
                        <td class="text-end pe-3">
                            <a href="{$admin_path}/ciphers/{$cipher.id}/edit"
                               class="btn btn-sm btn-outline-secondary me-1" title="Редактировать">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{$admin_path}/ciphers/{$cipher.id}/delete"
                                  class="d-inline"
                                  onsubmit="return confirm('Удалить шифр? Это удалит его переводы, FAQ, блоки и примеры.')">
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
            <i class="bi bi-shield-lock fs-2 d-block mb-2"></i>
            Шифров пока нет.
            <a href="{$admin_path}/ciphers/create">Добавить первый</a>
        </div>
        {/if}
    </div>
</div>
