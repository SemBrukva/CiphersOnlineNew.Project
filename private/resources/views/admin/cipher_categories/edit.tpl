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
     data-category-id="{$active_category.category.id}"
     data-csrf-token="{$csrf_token}">
    <div class="card-body p-4">
        <div class="d-none">
            {foreach $active_category.category_ciphers as $cipher}
                <span data-role="task-cipher-option"
                      data-id="{$cipher.id}"
                      data-label="#{$cipher.id} · {$cipher.alias|escape}"></span>
            {/foreach}
        </div>
        <form>
            <h2 class="h5 mb-3">ОСНОВНЫЕ НАСТРОЙКИ</h2>

            <div class="mb-3">
                <label for="alias" class="form-label fw-medium">Alias <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace" id="alias" name="alias"
                       value="{$active_category.category.alias|default:''}"
                       placeholder="classical-ciphers" required>
                <div class="form-text">Латиница в нижнем регистре, цифры и дефис.</div>
            </div>

            <div class="mb-3">
                <label for="sort_order" class="form-label fw-medium">Порядок сортировки</label>
                <input type="number" class="form-control" id="sort_order" name="sort_order"
                       value="{$active_category.category.sort_order|default:0}" min="0" max="999999" required>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="published" name="published" value="1"
                           {if $active_category.category.published}checked{/if}>
                    <label class="form-check-label" for="published">Опубликована</label>
                </div>
            </div>

            <hr class="my-4">

            <h2 class="h5 mb-3">ПЕРЕВОДЫ</h2>

            <ul class="nav nav-tabs" role="tablist">
                {foreach $available_languages as $language}
                    {assign var="translation" value=$active_category.translations_by_language[$language]|default:null}
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
                    {assign var="translation" value=$active_category.translations_by_language[$language]|default:null}
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

                        <div class="mt-4 mb-1">
                            <div class="d-flex align-items-center gap-2 py-2 border-bottom entity-section-toggle"
                                 role="button"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#collapse-blocks-{$language}"
                                 aria-expanded="false">
                                <h3 class="h6 fw-semibold text-uppercase text-secondary mb-0 flex-grow-1">Info Blocks</h3>
                                <i class="bi bi-chevron-down text-secondary collapse-chevron"></i>
                            </div>
                            <div class="collapse" id="collapse-blocks-{$language}">
                                <div class="vstack gap-2 pt-3 pb-1" data-entity-list="blocks">
                                    {foreach $active_category.blocks as $block}
                                        {assign var="block_translation" value=$block.translations_by_language[$language]|default:null}
                                        <div class="cipher-entity border rounded" data-entity="block" data-id="{$block.id}">
                                            <div class="cipher-entity-head d-flex align-items-center gap-3 px-3 py-2 bg-light rounded-top border-bottom">
                                                <span class="badge bg-secondary-subtle text-secondary font-monospace">#<span data-role="entity-id">{$block.id}</span></span>
                                                <div class="d-flex align-items-center gap-1 ms-auto">
                                                    <span class="text-muted small me-1">Сорт.</span>
                                                    <input type="number" class="form-control form-control-sm entity-sort-input"
                                                           min="0" max="999999" data-meta-field="sort_order"
                                                           value="{$block.sort_order|default:0}">
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           data-meta-field="published"
                                                           {if $block.published}checked{/if}>
                                                    <label class="form-check-label small text-muted">Вкл.</label>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1"
                                                        data-action="delete-item" title="Удалить">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </div>
                                            <div class="p-3">
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Заголовок</label>
                                                    <input type="text" class="form-control"
                                                           data-translation-field="title"
                                                           value="{$block_translation.title|default:''}">
                                                </div>
                                                <div class="mb-0">
                                                    <label class="form-label fw-medium">Текст</label>
                                                    <textarea class="form-control" rows="4"
                                                              data-translation-field="text">{$block_translation.text|default:''}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-action="add-block">
                                    <i class="bi bi-plus-circle me-1"></i>Добавить блок
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 mb-1">
                            <div class="d-flex align-items-center gap-2 py-2 border-bottom entity-section-toggle"
                                 role="button"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#collapse-tasks-{$language}"
                                 aria-expanded="false">
                                <h3 class="h6 fw-semibold text-uppercase text-secondary mb-0 flex-grow-1">Popular Tasks</h3>
                                <i class="bi bi-chevron-down text-secondary collapse-chevron"></i>
                            </div>
                            <div class="collapse" id="collapse-tasks-{$language}">
                                <div class="vstack gap-2 pt-3 pb-1" data-entity-list="tasks">
                                    {foreach $active_category.tasks as $task}
                                        {assign var="task_translation" value=$task.translations_by_language[$language]|default:null}
                                        <div class="cipher-entity border rounded" data-entity="task" data-id="{$task.id}">
                                            <div class="cipher-entity-head d-flex align-items-center gap-3 px-3 py-2 bg-light rounded-top border-bottom">
                                                <span class="badge bg-secondary-subtle text-secondary font-monospace">#<span data-role="entity-id">{$task.id}</span></span>
                                                <div class="d-flex align-items-center gap-1 ms-auto">
                                                    <span class="text-muted small me-1">Сорт.</span>
                                                    <input type="number" class="form-control form-control-sm entity-sort-input"
                                                           min="0" max="999999" data-meta-field="sort_order"
                                                           value="{$task.sort_order|default:0}">
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           data-meta-field="published"
                                                           {if $task.published}checked{/if}>
                                                    <label class="form-check-label small text-muted">Вкл.</label>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1"
                                                        data-action="delete-item" title="Удалить">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </div>
                                            <div class="p-3">
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Связанный шифр</label>
                                                    <select class="form-select" data-meta-field="relation_cipher_id">
                                                        <option value="0">Не выбран</option>
                                                        {foreach $active_category.category_ciphers as $cipher}
                                                            <option value="{$cipher.id}" {if $task.relation_cipher_id == $cipher.id}selected{/if}>
                                                                #{$cipher.id} · {$cipher.alias}
                                                            </option>
                                                        {/foreach}
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Title</label>
                                                    <input type="text" class="form-control"
                                                           data-translation-field="title"
                                                           value="{$task_translation.title|default:''}">
                                                </div>
                                                <div class="mb-0">
                                                    <label class="form-label fw-medium">Description</label>
                                                    <textarea class="form-control" rows="3"
                                                              data-translation-field="description">{$task_translation.description|default:''}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-action="add-task">
                                    <i class="bi bi-plus-circle me-1"></i>Добавить задачу
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 mb-1">
                            <div class="d-flex align-items-center gap-2 py-2 border-bottom entity-section-toggle"
                                 role="button"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#collapse-used-together-{$language}"
                                 aria-expanded="false">
                                <h3 class="h6 fw-semibold text-uppercase text-secondary mb-0 flex-grow-1">Often Used Together</h3>
                                <i class="bi bi-chevron-down text-secondary collapse-chevron"></i>
                            </div>
                            <div class="collapse" id="collapse-used-together-{$language}">
                                <div class="vstack gap-2 pt-3 pb-1" data-entity-list="used_together">
                                    {foreach $active_category.used_together as $used_together_item}
                                        {assign var="used_together_translation" value=$used_together_item.translations_by_language[$language]|default:null}
                                        <div class="cipher-entity border rounded" data-entity="used_together" data-id="{$used_together_item.id}">
                                            <div class="cipher-entity-head d-flex align-items-center gap-3 px-3 py-2 bg-light rounded-top border-bottom">
                                                <span class="badge bg-secondary-subtle text-secondary font-monospace">#<span data-role="entity-id">{$used_together_item.id}</span></span>
                                                <div class="d-flex align-items-center gap-1 ms-auto">
                                                    <span class="text-muted small me-1">Сорт.</span>
                                                    <input type="number" class="form-control form-control-sm entity-sort-input"
                                                           min="0" max="999999" data-meta-field="sort_order"
                                                           value="{$used_together_item.sort_order|default:0}">
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           data-meta-field="published"
                                                           {if $used_together_item.published}checked{/if}>
                                                    <label class="form-check-label small text-muted">Вкл.</label>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1"
                                                        data-action="delete-item" title="Удалить">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </div>
                                            <div class="p-3">
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-medium">Первый шифр</label>
                                                        <select class="form-select" data-meta-field="relation_cipher_first_id">
                                                            <option value="0">Не выбран</option>
                                                            {foreach $active_category.category_ciphers as $cipher}
                                                                <option value="{$cipher.id}" {if $used_together_item.relation_cipher_first_id == $cipher.id}selected{/if}>
                                                                    #{$cipher.id} · {$cipher.alias}
                                                                </option>
                                                            {/foreach}
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-medium">Второй шифр</label>
                                                        <select class="form-select" data-meta-field="relation_cipher_second_id">
                                                            <option value="0">Не выбран</option>
                                                            {foreach $active_category.category_ciphers as $cipher}
                                                                <option value="{$cipher.id}" {if $used_together_item.relation_cipher_second_id == $cipher.id}selected{/if}>
                                                                    #{$cipher.id} · {$cipher.alias}
                                                                </option>
                                                            {/foreach}
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-0">
                                                    <label class="form-label fw-medium">Title</label>
                                                    <textarea class="form-control" rows="3"
                                                              data-translation-field="title">{$used_together_translation.title|default:''}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-action="add-used-together">
                                    <i class="bi bi-plus-circle me-1"></i>Добавить связку
                                </button>
                            </div>
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
