{* Виджет генератора UUID / GUID: выбор версии, количества, формата и (для v3/v5) *}
{* пространства имён + имени. Полностью клиентский (see pages/uuid-generator.js). *}
{* Параметры: $cipher, $tool_slug, $tool_ui, $tool_ui_json. *}
<section class="ciphers-page uuid-page"
         data-page="uuid-generator"
         data-cipher-tool="{$tool_slug}"
         data-cipher-ui="{$tool_ui_json|escape:'html'}">
    <div class="ciphers-unified uuid">
        <div class="ciphers-unified__header">
            <div class="ciphers-unified__title-row">
                <h1 class="ciphers-unified__title">{$cipher.name}</h1>
                <button class="btn ciphers-unified__btn-ghost ciphers-unified__btn-favorite"
                        id="ciphers-favorite"
                        type="button"
                        data-slug="{$tool_slug|escape:'html'}"
                        data-name="{$cipher.name|escape:'html'}"
                        title="{$tool_ui.favoriteAddLabel|default:'Add to favorites'}">
                    <i class="bi bi-star" id="ciphers-favorite-icon"></i>
                </button>
            </div>
            <p class="ciphers-unified__desc">{$cipher.description}</p>

            <div class="ciphers-unified__controls-row uuid__toolbar">
                <div class="ciphers-settings">
                    <div class="ciphers-settings-item">
                        <label class="ciphers-settings-label" for="ciphers-uuid-version">{$tool_ui.uuidVersionLabel}</label>
                        <select id="ciphers-uuid-version" class="ciphers-settings-select">
                            <option value="v4" selected>{$tool_ui.uuidVersionV4}</option>
                            <option value="v7">{$tool_ui.uuidVersionV7}</option>
                            <option value="v1">{$tool_ui.uuidVersionV1}</option>
                            <option value="v3">{$tool_ui.uuidVersionV3}</option>
                            <option value="v5">{$tool_ui.uuidVersionV5}</option>
                            <option value="nil">{$tool_ui.uuidVersionNil}</option>
                            <option value="max">{$tool_ui.uuidVersionMax}</option>
                        </select>
                    </div>
                    <div class="ciphers-settings-item uuid__count-item">
                        <label class="ciphers-settings-label" for="ciphers-uuid-count">{$tool_ui.uuidCountLabel}</label>
                        <div class="ciphers-settings-shift-group">
                            <button class="ciphers-settings-shift-btn" id="ciphers-uuid-count-dec" type="button" aria-label="−">−</button>
                            <input id="ciphers-uuid-count" type="number" class="ciphers-settings-shift-input"
                                   min="1" max="100" step="1" value="1" inputmode="numeric">
                            <button class="ciphers-settings-shift-btn" id="ciphers-uuid-count-inc" type="button" aria-label="+">+</button>
                        </div>
                    </div>
                </div>

                <div class="uuid__namebased" id="ciphers-uuid-namebased" hidden>
                    <div class="ciphers-settings-item">
                        <label class="ciphers-settings-label" for="ciphers-uuid-namespace">{$tool_ui.uuidNamespaceLabel}</label>
                        <select id="ciphers-uuid-namespace" class="ciphers-settings-select">
                            <option value="dns" selected>{$tool_ui.uuidNamespaceDns}</option>
                            <option value="url">{$tool_ui.uuidNamespaceUrl}</option>
                            <option value="oid">{$tool_ui.uuidNamespaceOid}</option>
                            <option value="x500">{$tool_ui.uuidNamespaceX500}</option>
                            <option value="custom">{$tool_ui.uuidNamespaceCustom}</option>
                        </select>
                    </div>
                    <div class="ciphers-settings-item uuid__field" id="ciphers-uuid-namespace-custom-wrap" hidden>
                        <label class="ciphers-settings-label" for="ciphers-uuid-namespace-custom">{$tool_ui.uuidNamespaceCustom}</label>
                        <input type="text" id="ciphers-uuid-namespace-custom" class="ciphers-settings-input"
                               placeholder="{$tool_ui.uuidNamespacePlaceholder}" spellcheck="false" autocomplete="off">
                    </div>
                    <div class="ciphers-settings-item uuid__field uuid__field--name">
                        <label class="ciphers-settings-label" for="ciphers-uuid-name">{$tool_ui.uuidNameLabel}</label>
                        <input type="text" id="ciphers-uuid-name" class="ciphers-settings-input"
                               placeholder="{$tool_ui.uuidNamePlaceholder}" spellcheck="false" autocomplete="off">
                    </div>
                </div>

                <div class="uuid__format">
                    <span class="uuid__format-label">{$tool_ui.uuidFormatLabel}</span>
                    <label class="uuid__check"><input type="checkbox" id="ciphers-uuid-hyphens" checked><span>{$tool_ui.uuidFormatHyphens}</span></label>
                    <label class="uuid__check"><input type="checkbox" id="ciphers-uuid-uppercase"><span>{$tool_ui.uuidFormatUppercase}</span></label>
                    <label class="uuid__check"><input type="checkbox" id="ciphers-uuid-braces"><span>{$tool_ui.uuidFormatBraces}</span></label>
                    <label class="uuid__check"><input type="checkbox" id="ciphers-uuid-urn"><span>{$tool_ui.uuidFormatUrn}</span></label>
                </div>
            </div>
        </div>

        <div class="ciphers-unified__body">
            <div class="uuid__actions">
                <button class="ciphers-unified__run-btn" type="button" id="ciphers-uuid-generate"><i class="bi bi-shuffle"></i>{$tool_ui.uuidGenerateLabel}</button>
            </div>

            <div class="ciphers-unified__output-wrap">
                <div class="ciphers-unified__field-header">
                    <span class="ciphers-unified__field-label ciphers-unified__field-label--result">{$tool_ui.resultLabel}</span>
                    <div class="ciphers-unified__output-actions">
                        <span class="ciphers-unified__counter" id="ciphers-uuid-counter">0 {$tool_ui.charsLabel} · 0 {$tool_ui.bytesLabel}</span>
                        <button class="btn ciphers-unified__btn-ghost ciphers-unified__btn-clear" type="button" id="ciphers-uuid-clear"><i class="bi bi-x-lg"></i>{$tool_ui.clearLabel|default:'Clear'}</button>
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-uuid-copy"><i class="bi bi-clipboard"></i>{$tool_ui.copyLabel}</button>
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-share"><i class="bi bi-share"></i>{$tool_ui.shareLabel}</button>
                    </div>
                </div>
                <textarea class="form-control ciphers-textarea ciphers-unified__textarea uuid__output"
                          id="ciphers-uuid-output" rows="8" readonly spellcheck="false"></textarea>

                <div class="ciphers-unified__examples-row uuid__examples-row">
                    <span class="ciphers-unified__examples-label">{$tool_ui.tryLabel}</span>
                    <div class="ciphers-example-chips">
                        <button class="ciphers-example-chip uuid-example" type="button" data-uuid-version="v4" data-uuid-count="1">Random v4</button>
                        <button class="ciphers-example-chip uuid-example" type="button" data-uuid-version="v4" data-uuid-count="10">Bulk ×10</button>
                        <button class="ciphers-example-chip uuid-example" type="button" data-uuid-version="v4" data-uuid-uppercase="1" data-uuid-hyphens="0">GUID (no hyphens)</button>
                        <button class="ciphers-example-chip uuid-example" type="button" data-uuid-version="v7" data-uuid-count="5">v7 sortable</button>
                        <button class="ciphers-example-chip uuid-example" type="button" data-uuid-version="v5" data-uuid-name="example.com" data-uuid-namespace="dns">v5 (DNS)</button>
                        <button class="ciphers-example-chip uuid-example" type="button" data-uuid-version="nil">Nil</button>
                    </div>
                </div>

                <div class="ciphers-feedback" id="ciphers-feedback" aria-live="polite"></div>
            </div>
        </div>

        <div class="ciphers-trust">
            {foreach $tool_ui.trustItems as $item}
            <span class="ciphers-trust__item">✓ {$item}</span>
            {/foreach}
        </div>
    </div>
</section>
