<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Добавить шифр</h1>
</div>

{if $error}
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {$error}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
{/if}

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{$admin_path}/ciphers">
            <input type="hidden" name="_csrf_token" value="{$csrf_token}">

            <div class="mb-3">
                <label for="alias" class="form-label fw-medium">Alias <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace" id="alias" name="alias"
                       value="{$cipher.alias|default:''}" placeholder="caesar" required>
                <div class="form-text">Латиница в нижнем регистре, цифры и дефис.</div>
            </div>

            <div class="mb-3">
                <label for="category_id" class="form-label fw-medium">Категория <span class="text-danger">*</span></label>
                <select class="form-select" id="category_id" name="category_id" required>
                    {foreach $categories as $category}
                        <option value="{$category.id}" {if $cipher && $cipher.category_id == $category.id}selected{/if}>
                            #{$category.id} · {$category.alias}
                        </option>
                    {/foreach}
                </select>
            </div>

            <div class="mb-3">
                <label for="sort_order" class="form-label fw-medium">Порядок сортировки</label>
                <input type="number" class="form-control" id="sort_order" name="sort_order"
                       value="{$cipher.sort_order|default:0}" min="0" max="999999" required>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="published" name="published" value="1" {if !$cipher || $cipher.published}checked{/if}>
                    <label class="form-check-label" for="published">Опубликован</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{$admin_path}/ciphers" class="btn btn-outline-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>
