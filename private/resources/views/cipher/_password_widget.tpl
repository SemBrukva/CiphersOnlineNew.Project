{* Виджет генератора паролей: режимы «пароль» / «парольная фраза», наборы символов, *}
{* число слов, индикатор надёжности (энтропия + zxcvbn). Полностью клиентский      *}
{* (see pages/password-generator.js). Параметры: $cipher, $tool_slug, $tool_ui, $tool_ui_json. *}
<section class="ciphers-page pwd-page"
         data-page="password-generator"
         data-cipher-tool="{$tool_slug}"
         data-cipher-ui="{$tool_ui_json|escape:'html'}">
    <div class="ciphers-unified pwd">
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

            <div class="ciphers-unified__controls-row pwd__toolbar">
                <div class="pwd__tabs" role="tablist">
                    <button class="pwd__tab is-active" type="button" id="ciphers-pwd-tab-password" data-mode="password" role="tab" aria-selected="true">{$tool_ui.pwdModePassword}</button>
                    <button class="pwd__tab" type="button" id="ciphers-pwd-tab-passphrase" data-mode="passphrase" role="tab" aria-selected="false">{$tool_ui.pwdModePassphrase}</button>
                </div>

                {* --- Панель пароля --- *}
                <div class="pwd__panel" id="ciphers-pwd-panel-password">
                    <div class="ciphers-settings">
                        <div class="ciphers-settings-item pwd__count-item">
                            <label class="ciphers-settings-label" for="ciphers-pwd-length">{$tool_ui.pwdLengthLabel}</label>
                            <div class="ciphers-settings-shift-group">
                                <button class="ciphers-settings-shift-btn" id="ciphers-pwd-length-dec" type="button" aria-label="−">−</button>
                                <input id="ciphers-pwd-length" type="number" class="ciphers-settings-shift-input"
                                       min="4" max="128" step="1" value="16" inputmode="numeric">
                                <button class="ciphers-settings-shift-btn" id="ciphers-pwd-length-inc" type="button" aria-label="+">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="pwd__checks">
                        <span class="pwd__checks-label">{$tool_ui.pwdSetsLabel}</span>
                        <label class="pwd__check"><input type="checkbox" id="ciphers-pwd-lower" checked><span>{$tool_ui.pwdSetLower}</span></label>
                        <label class="pwd__check"><input type="checkbox" id="ciphers-pwd-upper" checked><span>{$tool_ui.pwdSetUpper}</span></label>
                        <label class="pwd__check"><input type="checkbox" id="ciphers-pwd-digits" checked><span>{$tool_ui.pwdSetDigits}</span></label>
                        <label class="pwd__check"><input type="checkbox" id="ciphers-pwd-symbols"><span>{$tool_ui.pwdSetSymbols}</span></label>
                    </div>

                    <div class="pwd__checks">
                        <label class="pwd__check"><input type="checkbox" id="ciphers-pwd-exclude-similar"><span>{$tool_ui.pwdOptExcludeSimilar}</span></label>
                        <label class="pwd__check"><input type="checkbox" id="ciphers-pwd-exclude-ambiguous"><span>{$tool_ui.pwdOptExcludeAmbiguous}</span></label>
                        <label class="pwd__check"><input type="checkbox" id="ciphers-pwd-no-repeats"><span>{$tool_ui.pwdOptNoRepeats}</span></label>
                    </div>
                </div>

                {* --- Панель парольной фразы --- *}
                <div class="pwd__panel" id="ciphers-pwd-panel-passphrase" hidden>
                    <div class="ciphers-settings">
                        <div class="ciphers-settings-item pwd__count-item">
                            <label class="ciphers-settings-label" for="ciphers-pwd-words">{$tool_ui.pwdWordsLabel}</label>
                            <div class="ciphers-settings-shift-group">
                                <button class="ciphers-settings-shift-btn" id="ciphers-pwd-words-dec" type="button" aria-label="−">−</button>
                                <input id="ciphers-pwd-words" type="number" class="ciphers-settings-shift-input"
                                       min="3" max="20" step="1" value="6" inputmode="numeric">
                                <button class="ciphers-settings-shift-btn" id="ciphers-pwd-words-inc" type="button" aria-label="+">+</button>
                            </div>
                        </div>
                        <div class="ciphers-settings-item">
                            <label class="ciphers-settings-label" for="ciphers-pwd-separator">{$tool_ui.pwdSeparatorLabel}</label>
                            <select id="ciphers-pwd-separator" class="ciphers-settings-select">
                                <option value="-" selected>{$tool_ui.pwdSepHyphen}</option>
                                <option value=".">{$tool_ui.pwdSepDot}</option>
                                <option value=" ">{$tool_ui.pwdSepSpace}</option>
                                <option value="_">{$tool_ui.pwdSepUnderscore}</option>
                            </select>
                        </div>
                        <div class="ciphers-settings-item">
                            <label class="ciphers-settings-label" for="ciphers-pwd-case">{$tool_ui.pwdCaseLabel}</label>
                            <select id="ciphers-pwd-case" class="ciphers-settings-select">
                                <option value="lower" selected>{$tool_ui.pwdCaseLower}</option>
                                <option value="capitalize">{$tool_ui.pwdCaseCapitalize}</option>
                                <option value="upper">{$tool_ui.pwdCaseUpper}</option>
                            </select>
                        </div>
                    </div>

                    <div class="pwd__checks">
                        <label class="pwd__check"><input type="checkbox" id="ciphers-pwd-add-number"><span>{$tool_ui.pwdOptAddNumber}</span></label>
                    </div>
                </div>

                {* --- Общий счётчик количества --- *}
                <div class="ciphers-settings pwd__common">
                    <div class="ciphers-settings-item pwd__count-item">
                        <label class="ciphers-settings-label" for="ciphers-pwd-count">{$tool_ui.pwdCountLabel}</label>
                        <div class="ciphers-settings-shift-group">
                            <button class="ciphers-settings-shift-btn" id="ciphers-pwd-count-dec" type="button" aria-label="−">−</button>
                            <input id="ciphers-pwd-count" type="number" class="ciphers-settings-shift-input"
                                   min="1" max="100" step="1" value="1" inputmode="numeric">
                            <button class="ciphers-settings-shift-btn" id="ciphers-pwd-count-inc" type="button" aria-label="+">+</button>
                        </div>
                    </div>
                </div>
            </div>

            {* --- Индикатор надёжности --- *}
            <div class="pwd__strength" id="ciphers-pwd-strength">
                <div class="pwd__strength-head">
                    <span class="pwd__strength-label">{$tool_ui.pwdStrengthLabel}</span>
                    <span class="pwd__strength-verdict" id="ciphers-pwd-strength-verdict"></span>
                </div>
                <div class="pwd__meter" aria-hidden="true">
                    <span class="pwd__meter-seg"></span>
                    <span class="pwd__meter-seg"></span>
                    <span class="pwd__meter-seg"></span>
                    <span class="pwd__meter-seg"></span>
                </div>
                <div class="pwd__strength-meta">
                    <span id="ciphers-pwd-entropy"></span>
                    <span id="ciphers-pwd-crack"></span>
                </div>
            </div>
        </div>

        <div class="ciphers-unified__body">
            <div class="pwd__actions">
                <button class="ciphers-unified__run-btn" type="button" id="ciphers-pwd-generate"><i class="bi bi-shuffle"></i>{$tool_ui.pwdGenerateLabel}</button>
            </div>

            <div class="ciphers-unified__output-wrap">
                <div class="ciphers-unified__field-header">
                    <span class="ciphers-unified__field-label ciphers-unified__field-label--result">{$tool_ui.resultLabel}</span>
                    <div class="ciphers-unified__output-actions">
                        <span class="ciphers-unified__counter" id="ciphers-pwd-counter">0 {$tool_ui.charsLabel} · 0 {$tool_ui.bytesLabel}</span>
                        <button class="btn ciphers-unified__btn-ghost ciphers-unified__btn-clear" type="button" id="ciphers-pwd-clear"><i class="bi bi-x-lg"></i>{$tool_ui.clearLabel|default:'Clear'}</button>
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-pwd-copy"><i class="bi bi-clipboard"></i>{$tool_ui.copyLabel}</button>
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-share"><i class="bi bi-share"></i>{$tool_ui.shareLabel}</button>
                    </div>
                </div>
                <textarea class="form-control ciphers-textarea ciphers-unified__textarea pwd__output"
                          id="ciphers-pwd-output" rows="8" readonly spellcheck="false"></textarea>

                <div class="ciphers-unified__examples-row pwd__examples-row">
                    <span class="ciphers-unified__examples-label">{$tool_ui.tryLabel}</span>
                    <div class="ciphers-example-chips">
                        <button class="ciphers-example-chip pwd-example" type="button" data-pwd-mode="password" data-pwd-length="16" data-pwd-upper="1" data-pwd-digits="1" data-pwd-symbols="1">Strong 16</button>
                        <button class="ciphers-example-chip pwd-example" type="button" data-pwd-mode="password" data-pwd-length="24" data-pwd-upper="1" data-pwd-digits="1" data-pwd-symbols="1" data-pwd-exclude-similar="1">24 · no lookalikes</button>
                        <button class="ciphers-example-chip pwd-example" type="button" data-pwd-mode="password" data-pwd-length="6" data-pwd-lower="0" data-pwd-upper="0" data-pwd-digits="1" data-pwd-symbols="0">Numeric PIN 6</button>
                        <button class="ciphers-example-chip pwd-example" type="button" data-pwd-mode="passphrase" data-pwd-words="6" data-pwd-case="capitalize">Passphrase ×6</button>
                        <button class="ciphers-example-chip pwd-example" type="button" data-pwd-mode="passphrase" data-pwd-words="4" data-pwd-separator="." data-pwd-add-number="1">4 words · dotted</button>
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
