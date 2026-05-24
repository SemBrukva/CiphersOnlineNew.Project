<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Редактировать категорию</h1>
</div>

{if $error}
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {$error}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
{/if}

<div class="d-none" data-role="save-alert"></div>

<div class="card border-0 shadow-sm"
     data-page="admin-cipher-category-edit"
     data-category-id="{$category.id}"
     data-csrf-token="{$csrf_token}">
    <div class="card-body p-4">
        <form>
            <h2 class="h5 mb-3">ОСНОВНЫЕ НАСТРОЙКИ</h2>

            <div class="mb-3">
                <label for="alias" class="form-label fw-medium">Alias <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace" id="alias" name="alias"
                       value="{$category.alias|default:''}"
                       placeholder="classical-ciphers" required>
                <div class="form-text">Латиница в нижнем регистре, цифры и дефис.</div>
            </div>

            <div class="mb-3">
                <label for="sort_order" class="form-label fw-medium">Порядок сортировки</label>
                <input type="number" class="form-control" id="sort_order" name="sort_order"
                       value="{$category.sort_order|default:0}" min="0" max="999999" required>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="published" name="published" value="1"
                           {if $category.published}checked{/if}>
                    <label class="form-check-label" for="published">Опубликована</label>
                </div>
            </div>

            <hr class="my-4">

            <h2 class="h5 mb-3">ПЕРЕВОДЫ</h2>

            <ul class="nav nav-tabs" role="tablist">
                {foreach $available_languages as $language}
                    {assign var="translation" value=$translations_by_language[$language]|default:null}
                    {assign var="tab_is_active" value=($active_language && $active_language == $language) || (!$active_language && $language@first)}
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {if $tab_is_active}active{/if}" id="lang-tab-{$language}" data-bs-toggle="tab"
                                data-bs-target="#lang-pane-{$language}" type="button" role="tab"
                                aria-controls="lang-pane-{$language}" aria-selected="{if $tab_is_active}true{else}false{/if}">
                            {$language|upper}
                            {if $translation && $translation.name|default:''}
                                <span class="badge bg-success ms-1">OK</span>
                            {else}
                                <span class="badge bg-secondary ms-1">Пусто</span>
                            {/if}
                        </button>
                    </li>
                {/foreach}
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom p-3 mb-4">
                {foreach $available_languages as $language}
                    {assign var="translation" value=$translations_by_language[$language]|default:null}
                    {assign var="tab_is_active" value=($active_language && $active_language == $language) || (!$active_language && $language@first)}
                    <div class="tab-pane fade {if $tab_is_active}show active{/if}"
                         id="lang-pane-{$language}" role="tabpanel" aria-labelledby="lang-tab-{$language}"
                         data-language="{$language}">
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="name-{$language}">Название ({$language|upper})</label>
                            <input type="text" class="form-control" id="name-{$language}" data-field="name"
                                   value="{$translation.name|default:''}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium" for="description-{$language}">Описание</label>
                            <textarea class="form-control" id="description-{$language}" rows="4" data-field="description">{$translation.description|default:''}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium" for="meta-title-{$language}">Meta title</label>
                            <input type="text" class="form-control" id="meta-title-{$language}" data-field="meta_title"
                                   value="{$translation.meta_title|default:''}">
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-medium" for="meta-description-{$language}">Meta description</label>
                            <textarea class="form-control" id="meta-description-{$language}" rows="3" data-field="meta_description">{$translation.meta_description|default:''}</textarea>
                        </div>
                    </div>
                {/foreach}
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" data-role="save-category">Сохранить</button>
                <a href="{$admin_path}/cipher-categories" class="btn btn-outline-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>
