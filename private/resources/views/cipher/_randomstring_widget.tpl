{* Виджет генератора случайных строк: наборы символов + произвольный алфавит,   *}
{* длина, число результатов, формат вывода списком. Полностью клиентский         *}
{* (see pages/randomstring-generator.js). Параметры: $cipher, $tool_slug, $tool_ui, $tool_ui_json. *}
<section class="ciphers-page rs-page"
         data-page="random-string"
         data-cipher-tool="{$tool_slug}"
         data-cipher-ui="{$tool_ui_json|escape:'html'}">
    <div class="ciphers-unified rs">
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

            <div class="ciphers-unified__controls-row rs__toolbar">
                <div class="ciphers-settings">
                    <div class="ciphers-settings-item rs__count-item">
                        <label class="ciphers-settings-label" for="ciphers-rs-length">{$tool_ui.rsLengthLabel}</label>
                        <div class="ciphers-settings-shift-group">
                            <button class="ciphers-settings-shift-btn" id="ciphers-rs-length-dec" type="button" aria-label="−">−</button>
                            <input id="ciphers-rs-length" type="number" class="ciphers-settings-shift-input"
                                   min="1" max="512" step="1" value="32" inputmode="numeric">
                            <button class="ciphers-settings-shift-btn" id="ciphers-rs-length-inc" type="button" aria-label="+">+</button>
                        </div>
                    </div>
                    <div class="ciphers-settings-item rs__count-item">
                        <label class="ciphers-settings-label" for="ciphers-rs-count">{$tool_ui.rsCountLabel}</label>
                        <div class="ciphers-settings-shift-group">
                            <button class="ciphers-settings-shift-btn" id="ciphers-rs-count-dec" type="button" aria-label="−">−</button>
                            <input id="ciphers-rs-count" type="number" class="ciphers-settings-shift-input"
                                   min="1" max="100" step="1" value="1" inputmode="numeric">
                            <button class="ciphers-settings-shift-btn" id="ciphers-rs-count-inc" type="button" aria-label="+">+</button>
                        </div>
                    </div>
                    <div class="ciphers-settings-item">
                        <label class="ciphers-settings-label" for="ciphers-rs-format">{$tool_ui.rsFormatLabel}</label>
                        <select id="ciphers-rs-format" class="ciphers-settings-select">
                            <option value="newline" selected>{$tool_ui.rsFormatNewline}</option>
                            <option value="comma">{$tool_ui.rsFormatComma}</option>
                            <option value="space">{$tool_ui.rsFormatSpace}</option>
                            <option value="quoted">{$tool_ui.rsFormatQuoted}</option>
                        </select>
                    </div>
                    <div class="ciphers-settings-item">
                        <label class="ciphers-settings-label" for="ciphers-rs-alphabet">{$tool_ui.rsAlphabetLabel}</label>
                        <select id="ciphers-rs-alphabet" class="ciphers-settings-select">
                            {foreach $tool_ui.rsAlphabetLabels as $code => $label}
                            <option value="{$code|escape:'html'}"{if $code == 'en'} selected{/if}>{$label|escape:'html'}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>

                <div class="rs__checks">
                    <span class="rs__checks-label">{$tool_ui.rsSetsLabel}</span>
                    <label class="rs__check"><input type="checkbox" id="ciphers-rs-lower" checked><span>{$tool_ui.rsSetLower}</span></label>
                    <label class="rs__check"><input type="checkbox" id="ciphers-rs-upper" checked><span>{$tool_ui.rsSetUpper}</span></label>
                    <label class="rs__check"><input type="checkbox" id="ciphers-rs-digits" checked><span>{$tool_ui.rsSetDigits}</span></label>
                    <label class="rs__check"><input type="checkbox" id="ciphers-rs-symbols"><span>{$tool_ui.rsSetSymbols}</span></label>
                </div>

                <div class="ciphers-settings rs__custom-row">
                    <div class="ciphers-settings-item rs__custom-item">
                        <label class="ciphers-settings-label" for="ciphers-rs-custom">{$tool_ui.rsCustomLabel}</label>
                        <input id="ciphers-rs-custom" type="text" class="form-control ciphers-settings-input rs__custom-input"
                               placeholder="{$tool_ui.rsCustomPlaceholder|escape:'html'}" spellcheck="false" autocomplete="off">
                    </div>
                </div>

                <div class="rs__checks">
                    <label class="rs__check"><input type="checkbox" id="ciphers-rs-exclude-similar"><span>{$tool_ui.rsOptExcludeSimilar} <em class="rs__hint" id="ciphers-rs-similar-hint">(i, l, 1, O, 0)</em></span></label>
                    <label class="rs__check"><input type="checkbox" id="ciphers-rs-no-repeats"><span>{$tool_ui.rsOptNoRepeats}</span></label>
                </div>
            </div>
        </div>

        <div class="ciphers-unified__body">
            <div class="rs__actions">
                <button class="ciphers-unified__run-btn" type="button" id="ciphers-rs-generate"><i class="bi bi-shuffle"></i>{$tool_ui.rsGenerateLabel}</button>
            </div>

            <div class="ciphers-unified__output-wrap">
                <div class="ciphers-unified__field-header">
                    <span class="ciphers-unified__field-label ciphers-unified__field-label--result">{$tool_ui.resultLabel}</span>
                    <div class="ciphers-unified__output-actions">
                        <span class="ciphers-unified__counter" id="ciphers-rs-counter">0 {$tool_ui.charsLabel} · 0 {$tool_ui.bytesLabel}</span>
                        <button class="btn ciphers-unified__btn-ghost ciphers-unified__btn-clear" type="button" id="ciphers-rs-clear"><i class="bi bi-x-lg"></i>{$tool_ui.clearLabel|default:'Clear'}</button>
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-rs-download"><i class="bi bi-download"></i>{$tool_ui.rsDownloadLabel}</button>
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-rs-copy"><i class="bi bi-clipboard"></i>{$tool_ui.copyLabel}</button>
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-share"><i class="bi bi-share"></i>{$tool_ui.shareLabel}</button>
                    </div>
                </div>
                <textarea class="form-control ciphers-textarea ciphers-unified__textarea rs__output"
                          id="ciphers-rs-output" rows="8" readonly spellcheck="false"></textarea>

                <div class="ciphers-unified__examples-row rs__examples-row">
                    <span class="ciphers-unified__examples-label">{$tool_ui.tryLabel}</span>
                    <div class="ciphers-example-chips">
                        <button class="ciphers-example-chip rs-example" type="button" data-rs-length="32" data-rs-lower="1" data-rs-upper="1" data-rs-digits="1" data-rs-symbols="0" data-rs-custom="">Alphanumeric 32</button>
                        <button class="ciphers-example-chip rs-example" type="button" data-rs-length="64" data-rs-lower="1" data-rs-upper="0" data-rs-digits="1" data-rs-symbols="0" data-rs-custom="">Token 64 · lower+digits</button>
                        <button class="ciphers-example-chip rs-example" type="button" data-rs-length="32" data-rs-lower="0" data-rs-upper="0" data-rs-digits="0" data-rs-symbols="0" data-rs-custom="0123456789abcdef">Hex 32</button>
                        <button class="ciphers-example-chip rs-example" type="button" data-rs-length="6" data-rs-lower="0" data-rs-upper="0" data-rs-digits="1" data-rs-symbols="0" data-rs-custom="">Numeric PIN 6</button>
                        <button class="ciphers-example-chip rs-example" type="button" data-rs-length="20" data-rs-lower="1" data-rs-upper="1" data-rs-digits="1" data-rs-symbols="1" data-rs-custom="">Strong 20 · symbols</button>
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
