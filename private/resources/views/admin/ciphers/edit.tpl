<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Редактировать шифр</h1>
    <a href="{$admin_path}/ciphers" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>К списку
    </a>
</div>

{if $error}
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {$error}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
{/if}

<div class="d-none" data-role="save-alert"></div>

<div data-page="admin-cipher-edit"
     data-cipher-id="{$active_cipher.cipher.id}"
     data-csrf-token="{$csrf_token}">
    <form>

        {* ── Основные настройки ─────────────────────────────────────────── *}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h2 class="h6 mb-0 fw-semibold text-uppercase text-secondary ls-1">Основные настройки</h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-medium" for="alias">Alias</label>
                        <input type="text" class="form-control font-monospace" id="alias" name="alias"
                               value="{$active_cipher.cipher.alias|default:''}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium" for="sort_order">Сортировка</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order"
                               value="{$active_cipher.cipher.sort_order|default:0}" min="0" max="999999" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium" for="category_id">Категория</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            {foreach $categories as $category}
                                <option value="{$category.id}" {if $active_cipher.cipher.category_id == $category.id}selected{/if}>
                                    #{$category.id} · {$category.alias}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="published" name="published" value="1"
                                   {if $active_cipher.cipher.published}checked{/if}>
                            <label class="form-check-label fw-medium" for="published">Опубликован</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {* ── Локализации ────────────────────────────────────────────────── *}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h2 class="h6 mb-0 fw-semibold text-uppercase text-secondary">Локализации</h2>
            </div>
            <div class="card-body p-0">

                <ul class="nav nav-tabs px-4 pt-3" role="tablist">
                    {foreach $available_languages as $language}
                        {assign var="translation" value=$active_cipher.translations_by_language[$language]|default:null}
                        {assign var="tab_is_active" value=($active_language && $active_language == $language) || (!$active_language && $language@first)}
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {if $tab_is_active}active{/if}" id="lang-tab-{$language}"
                                    data-bs-toggle="tab" data-bs-target="#lang-pane-{$language}"
                                    type="button" role="tab"
                                    aria-controls="lang-pane-{$language}"
                                    aria-selected="{if $tab_is_active}true{else}false{/if}">
                                {$language|upper}
                                {if $translation && $translation.name|default:''}
                                    <span class="badge bg-success-subtle text-success ms-1 small">✓</span>
                                {else}
                                    <span class="badge bg-secondary-subtle text-secondary ms-1 small">—</span>
                                {/if}
                            </button>
                        </li>
                    {/foreach}
                </ul>

                <div class="tab-content">
                    {foreach $available_languages as $language}
                        {assign var="translation" value=$active_cipher.translations_by_language[$language]|default:null}
                        {assign var="tab_is_active" value=($active_language && $active_language == $language) || (!$active_language && $language@first)}
                        <div class="tab-pane fade {if $tab_is_active}show active{/if} p-4"
                             id="lang-pane-{$language}" role="tabpanel"
                             aria-labelledby="lang-tab-{$language}"
                             data-language="{$language}">

                            {* Перевод шифра *}
                            <div class="mb-5">
                                <h3 class="h6 fw-semibold text-uppercase text-secondary mb-3">Перевод шифра</h3>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label fw-medium" for="cipher-name-{$language}">Название</label>
                                        <input type="text" class="form-control" id="cipher-name-{$language}"
                                               data-cipher-field="name"
                                               value="{$translation.name|default:''}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium" for="cipher-name-short-{$language}">Короткое название</label>
                                        <input type="text" class="form-control" id="cipher-name-short-{$language}"
                                               data-cipher-field="name_short"
                                               value="{$translation.name_short|default:''}" maxlength="100">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-medium" for="cipher-description-{$language}">Описание</label>
                                        <textarea class="form-control" id="cipher-description-{$language}" rows="3"
                                                  data-cipher-field="description">{$translation.description|default:''}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium" for="cipher-meta-title-{$language}">Meta title</label>
                                        <input type="text" class="form-control" id="cipher-meta-title-{$language}"
                                               data-cipher-field="meta_title"
                                               value="{$translation.meta_title|default:''}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium" for="cipher-meta-description-{$language}">Meta description</label>
                                        <textarea class="form-control" id="cipher-meta-description-{$language}" rows="2"
                                                  data-cipher-field="meta_description">{$translation.meta_description|default:''}</textarea>
                                    </div>
                                </div>
                            </div>

                            {* Info Blocks *}
                            <div class="mb-4">
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
                                    {foreach $active_cipher.blocks as $block}
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
                                </div>{* /collapse-blocks *}
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="add-block">
                                        <i class="bi bi-plus-circle me-1"></i>Добавить блок
                                    </button>
                                </div>
                            </div>

                            {* FAQ *}
                            <div class="mb-4">
                                <div class="d-flex align-items-center gap-2 py-2 border-bottom entity-section-toggle"
                                     role="button"
                                     data-bs-toggle="collapse"
                                     data-bs-target="#collapse-faq-{$language}"
                                     aria-expanded="false">
                                    <h3 class="h6 fw-semibold text-uppercase text-secondary mb-0 flex-grow-1">FAQ</h3>
                                    <i class="bi bi-chevron-down text-secondary collapse-chevron"></i>
                                </div>
                                <div class="collapse" id="collapse-faq-{$language}">
                                <div class="vstack gap-2 pt-3 pb-1" data-entity-list="faqs">
                                    {foreach $active_cipher.faq as $faq_item}
                                        {assign var="faq_translation" value=$faq_item.translations_by_language[$language]|default:null}
                                        <div class="cipher-entity border rounded" data-entity="faq" data-id="{$faq_item.id}">
                                            <div class="cipher-entity-head d-flex align-items-center gap-3 px-3 py-2 bg-light rounded-top border-bottom">
                                                <span class="badge bg-secondary-subtle text-secondary font-monospace">#<span data-role="entity-id">{$faq_item.id}</span></span>
                                                <div class="d-flex align-items-center gap-1 ms-auto">
                                                    <span class="text-muted small me-1">Сорт.</span>
                                                    <input type="number" class="form-control form-control-sm entity-sort-input"
                                                           min="0" max="999999" data-meta-field="sort_order"
                                                           value="{$faq_item.sort_order|default:0}">
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           data-meta-field="published"
                                                           {if $faq_item.published}checked{/if}>
                                                    <label class="form-check-label small text-muted">Вкл.</label>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1"
                                                        data-action="delete-item" title="Удалить">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </div>
                                            <div class="p-3">
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Вопрос</label>
                                                    <input type="text" class="form-control"
                                                           data-translation-field="question"
                                                           value="{$faq_translation.question|default:''}">
                                                </div>
                                                <div class="mb-0">
                                                    <label class="form-label fw-medium">Ответ</label>
                                                    <textarea class="form-control" rows="4"
                                                              data-translation-field="answer">{$faq_translation.answer|default:''}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>
                                </div>{* /collapse-faq *}
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="add-faq">
                                        <i class="bi bi-plus-circle me-1"></i>Добавить FAQ
                                    </button>
                                </div>
                            </div>

                            {* Examples *}
                            <div class="mb-4">
                                <div class="d-flex align-items-center gap-2 py-2 border-bottom entity-section-toggle"
                                     role="button"
                                     data-bs-toggle="collapse"
                                     data-bs-target="#collapse-examples-{$language}"
                                     aria-expanded="false">
                                    <h3 class="h6 fw-semibold text-uppercase text-secondary mb-0 flex-grow-1">Examples</h3>
                                    <i class="bi bi-chevron-down text-secondary collapse-chevron"></i>
                                </div>
                                <div class="collapse" id="collapse-examples-{$language}">
                                <div class="vstack gap-2 pt-3 pb-1" data-entity-list="examples">
                                    {foreach $active_cipher.examples as $example}
                                        {assign var="example_translation" value=$example.translations_by_language[$language]|default:null}
                                        <div class="cipher-entity border rounded" data-entity="example" data-id="{$example.id}">
                                            <div class="cipher-entity-head d-flex align-items-center gap-3 px-3 py-2 bg-light rounded-top border-bottom">
                                                <span class="badge bg-secondary-subtle text-secondary font-monospace">#<span data-role="entity-id">{$example.id}</span></span>
                                                <div class="d-flex align-items-center gap-1 ms-auto">
                                                    <span class="text-muted small me-1">Сорт.</span>
                                                    <input type="number" class="form-control form-control-sm entity-sort-input"
                                                           min="0" max="999999" data-meta-field="sort_order"
                                                           value="{$example.sort_order|default:0}">
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           data-meta-field="published"
                                                           {if $example.published}checked{/if}>
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
                                                           value="{$example_translation.title|default:''}">
                                                </div>
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-medium">Input</label>
                                                        <textarea class="form-control" rows="3"
                                                                  data-translation-field="input">{$example_translation.input|default:''}</textarea>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-medium">Output</label>
                                                        <textarea class="form-control" rows="3"
                                                                  data-translation-field="output">{$example_translation.output|default:''}</textarea>
                                                    </div>
                                                </div>
                                                <div class="mb-0">
                                                    <label class="form-label fw-medium">Описание</label>
                                                    <textarea class="form-control" rows="3"
                                                              data-translation-field="description">{$example_translation.description|default:''}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>
                                </div>{* /collapse-examples *}
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="add-example">
                                        <i class="bi bi-plus-circle me-1"></i>Добавить пример
                                    </button>
                                </div>
                            </div>

                            {* Tags *}
                            <div class="mb-2">
                                <div class="d-flex align-items-center gap-2 py-2 border-bottom entity-section-toggle"
                                     role="button"
                                     data-bs-toggle="collapse"
                                     data-bs-target="#collapse-tags-{$language}"
                                     aria-expanded="false">
                                    <h3 class="h6 fw-semibold text-uppercase text-secondary mb-0 flex-grow-1">Tags</h3>
                                    <i class="bi bi-chevron-down text-secondary collapse-chevron"></i>
                                </div>
                                <div class="collapse" id="collapse-tags-{$language}">
                                <div class="vstack gap-2 pt-3" data-entity-list="tags">
                                    {foreach $active_cipher.tags as $tag_item}
                                        {assign var="tag_translation" value=$tag_item.translations_by_language[$language]|default:null}
                                        <div class="cipher-entity border rounded" data-entity="tag" data-id="{$tag_item.id}">
                                            <div class="cipher-entity-head d-flex align-items-center gap-3 px-3 py-2 bg-light rounded-top border-bottom">
                                                <span class="badge bg-secondary-subtle text-secondary font-monospace">#<span data-role="entity-id">{$tag_item.id}</span></span>
                                                <div class="d-flex align-items-center gap-1 ms-auto">
                                                    <span class="text-muted small me-1">Сорт.</span>
                                                    <input type="number" class="form-control form-control-sm entity-sort-input"
                                                           min="0" max="999999" data-meta-field="sort_order"
                                                           value="{$tag_item.sort_order|default:0}">
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           data-meta-field="published"
                                                           {if $tag_item.published}checked{/if}>
                                                    <label class="form-check-label small text-muted">Вкл.</label>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1"
                                                        data-action="delete-item" title="Удалить">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </div>
                                            <div class="p-3">
                                                <label class="form-label fw-medium">Tag ({$language|upper})</label>
                                                <input type="text" class="form-control" maxlength="100"
                                                       data-translation-field="tag"
                                                       value="{$tag_translation.tag|default:''}">
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>
                                </div>{* /collapse-tags *}
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="add-tag">
                                        <i class="bi bi-plus-circle me-1"></i>Добавить тег
                                    </button>
                                </div>
                            </div>

                        </div>
                    {/foreach}
                </div>
            </div>
        </div>

        {* ── Кнопки действий ────────────────────────────────────────────── *}
        <div class="d-flex gap-2 mb-5">
            <button type="submit" class="btn btn-primary px-4" data-role="save-cipher">
                <i class="bi bi-floppy me-1"></i>Сохранить
            </button>
            <a href="{$admin_path}/ciphers" class="btn btn-outline-secondary">К списку</a>
        </div>

    </form>
</div>
