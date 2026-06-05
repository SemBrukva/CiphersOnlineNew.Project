<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">{$title}</h1>
</div>

{if $error}
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {$error}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
{/if}

<div class="card border-0 shadow-sm" style="max-width:900px">
    <div class="card-body p-4">
        {if $translation}
            {assign var=action value="{$admin_path}/cipher-category-translations/{$translation.id}"}
        {else}
            {assign var=action value="{$admin_path}/cipher-category-translations"}
        {/if}

        <form method="POST" action="{$action}">
            <input type="hidden" name="_csrf_token" value="{$csrf_token}">

            <div class="mb-3">
                <label for="category_id" class="form-label fw-medium">Категория <span class="text-danger">*</span></label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Выберите категорию</option>
                    {foreach $categories as $category}
                    <option value="{$category.id}" {if $translation && $translation.category_id == $category.id}selected{/if}>
                        {$category.alias} (#{$category.id})
                    </option>
                    {/foreach}
                </select>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="language" class="form-label fw-medium">Язык <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" id="language" name="language"
                           value="{$translation.language|default:''}" placeholder="en" required>
                </div>
                <div class="col-md-9">
                    <label for="name" class="form-label fw-medium">Название <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="{$translation.name|default:''}" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label fw-medium">Описание</label>
                <textarea class="form-control" id="description" name="description" rows="4">{$translation.description|default:''}</textarea>
            </div>

            <div class="mb-3">
                <label for="meta_title" class="form-label fw-medium">Meta title</label>
                <input type="text" class="form-control" id="meta_title" name="meta_title"
                       value="{$translation.meta_title|default:''}">
            </div>

            <div class="mb-4">
                <label for="meta_description" class="form-label fw-medium">Meta description</label>
                <textarea class="form-control" id="meta_description" name="meta_description" rows="3">{$translation.meta_description|default:''}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{$admin_path}/cipher-category-translations" class="btn btn-outline-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>
