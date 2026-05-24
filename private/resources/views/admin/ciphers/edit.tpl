<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Редактировать шифр</h1>
</div>

{if $error}
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {$error}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
{/if}

<div class="d-none" data-role="save-alert"></div>

<div class="card border-0 shadow-sm"
     data-page="admin-cipher-edit"
     data-cipher-id="{$active_cipher.cipher.id}"
     data-csrf-token="{$csrf_token}">
    <div class="card-body p-4">
        <form>
            <h2 class="h5 mb-3">Основные настройки</h2>

            <div class="row g-3 mb-2">
                <div class="col-md-4">
                    <label class="form-label fw-medium" for="alias">Alias</label>
                    <input type="text" class="form-control font-monospace" id="alias" name="alias"
                           value="{$active_cipher.cipher.alias|default:''}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium" for="sort_order">Сортировка</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order"
                           value="{$active_cipher.cipher.sort_order|default:0}" min="0" max="999999" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium" for="category_id">Категория</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        {foreach $categories as $category}
                            <option value="{$category.id}" {if $active_cipher.cipher.category_id == $category.id}selected{/if}>
                                #{$category.id} · {$category.alias}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-4 mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="published" name="published" value="1" {if $active_cipher.cipher.published}checked{/if}>
                    <label class="form-check-label" for="published">Опубликован</label>
                </div>
            </div>

            <hr class="my-4">

            <h2 class="h5 mb-3">Локализации</h2>
            <ul class="nav nav-tabs" role="tablist">
                {foreach $available_languages as $language}
                    {assign var="translation" value=$active_cipher.translations_by_language[$language]|default:null}
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
                    {assign var="translation" value=$active_cipher.translations_by_language[$language]|default:null}
                    {assign var="tab_is_active" value=($active_language && $active_language == $language) || (!$active_language && $language@first)}
                    <div class="tab-pane fade {if $tab_is_active}show active{/if}"
                         id="lang-pane-{$language}" role="tabpanel" aria-labelledby="lang-tab-{$language}"
                         data-language="{$language}">

                        <h3 class="h6 mb-3">Перевод шифра</h3>
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="cipher-name-{$language}">Название</label>
                            <input type="text" class="form-control" id="cipher-name-{$language}" data-cipher-field="name"
                                   value="{$translation.name|default:''}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="cipher-name-short-{$language}">Короткое название</label>
                            <input type="text" class="form-control" id="cipher-name-short-{$language}" data-cipher-field="name_short"
                                   value="{$translation.name_short|default:''}" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="cipher-description-{$language}">Описание</label>
                            <textarea class="form-control" id="cipher-description-{$language}" rows="3" data-cipher-field="description">{$translation.description|default:''}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="cipher-meta-title-{$language}">Meta title</label>
                            <input type="text" class="form-control" id="cipher-meta-title-{$language}" data-cipher-field="meta_title"
                                   value="{$translation.meta_title|default:''}">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-medium" for="cipher-meta-description-{$language}">Meta description</label>
                            <textarea class="form-control" id="cipher-meta-description-{$language}" rows="3" data-cipher-field="meta_description">{$translation.meta_description|default:''}</textarea>
                        </div>

                        <h3 class="h6 mb-3">Info Blocks</h3>
                        <div class="vstack gap-3 mb-4">
                            {foreach $active_cipher.blocks as $block}
                                {assign var="block_translation" value=$block.translations_by_language[$language]|default:null}
                                <div class="border rounded p-3" data-entity="block" data-id="{$block.id}">
                                    <div class="small text-muted mb-2">ID: {$block.id} · sort: {$block.sort_order} · published: {$block.published}</div>
                                    <div class="mb-2">
                                        <label class="form-label fw-medium">Заголовок</label>
                                        <input type="text" class="form-control" data-translation-field="title"
                                               value="{$block_translation.title|default:''}">
                                    </div>
                                    <div>
                                        <label class="form-label fw-medium">Текст</label>
                                        <textarea class="form-control" rows="4" data-translation-field="text">{$block_translation.text|default:''}</textarea>
                                    </div>
                                </div>
                            {/foreach}
                        </div>

                        <h3 class="h6 mb-3">FAQ</h3>
                        <div class="vstack gap-3 mb-4">
                            {foreach $active_cipher.faq as $faq_item}
                                {assign var="faq_translation" value=$faq_item.translations_by_language[$language]|default:null}
                                <div class="border rounded p-3" data-entity="faq" data-id="{$faq_item.id}">
                                    <div class="small text-muted mb-2">ID: {$faq_item.id} · sort: {$faq_item.sort_order} · show_in_category: {$faq_item.show_in_category} · published: {$faq_item.published}</div>
                                    <div class="mb-2">
                                        <label class="form-label fw-medium">Вопрос</label>
                                        <input type="text" class="form-control" data-translation-field="question"
                                               value="{$faq_translation.question|default:''}">
                                    </div>
                                    <div>
                                        <label class="form-label fw-medium">Ответ</label>
                                        <textarea class="form-control" rows="4" data-translation-field="answer">{$faq_translation.answer|default:''}</textarea>
                                    </div>
                                </div>
                            {/foreach}
                        </div>

                        <h3 class="h6 mb-3">Examples</h3>
                        <div class="vstack gap-3 mb-2">
                            {foreach $active_cipher.examples as $example}
                                {assign var="example_translation" value=$example.translations_by_language[$language]|default:null}
                                <div class="border rounded p-3" data-entity="example" data-id="{$example.id}">
                                    <div class="small text-muted mb-2">ID: {$example.id} · sort: {$example.sort_order} · published: {$example.published}</div>
                                    <div class="mb-2">
                                        <label class="form-label fw-medium">Заголовок</label>
                                        <input type="text" class="form-control" data-translation-field="title"
                                               value="{$example_translation.title|default:''}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fw-medium">Input</label>
                                        <textarea class="form-control" rows="2" data-translation-field="input">{$example_translation.input|default:''}</textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fw-medium">Output</label>
                                        <textarea class="form-control" rows="2" data-translation-field="output">{$example_translation.output|default:''}</textarea>
                                    </div>
                                    <div>
                                        <label class="form-label fw-medium">Описание</label>
                                        <textarea class="form-control" rows="3" data-translation-field="description">{$example_translation.description|default:''}</textarea>
                                    </div>
                                </div>
                            {/foreach}
                        </div>
                    </div>
                {/foreach}
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" data-role="save-cipher">Сохранить</button>
                <a href="{$admin_path}/ciphers" class="btn btn-outline-secondary">К списку</a>
            </div>
        </form>
    </div>
</div>
