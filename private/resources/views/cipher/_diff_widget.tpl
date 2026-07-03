{* Виджет инструмента сравнения текстов (Text Diff): две панели ввода рядом, тулбар опций, *}
{* блок аналитики и результат сравнения. Полностью клиентский (see pages/text-diff.js). *}
{* Параметры: $cipher, $tool_slug, $tool_ui, $tool_ui_json. *}
<section class="ciphers-page diff-page"
         data-page="text-diff"
         data-cipher-tool="{$tool_slug}"
         data-cipher-ui="{$tool_ui_json|escape:'html'}">
    <div class="ciphers-unified diff">
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

            <div class="ciphers-unified__controls-row diff__toolbar">
                <div class="ciphers-settings">
                    <div class="ciphers-settings-item">
                        <label class="ciphers-settings-label" for="diff-granularity">{$tool_ui.diffGranularityLabel}</label>
                        <select id="diff-granularity" class="ciphers-settings-select">
                            <option value="word" selected>{$tool_ui.diffGranularityWord}</option>
                            <option value="char">{$tool_ui.diffGranularityChar}</option>
                            <option value="line">{$tool_ui.diffGranularityLine}</option>
                        </select>
                    </div>
                    <div class="ciphers-settings-item">
                        <label class="ciphers-settings-label" for="diff-view">{$tool_ui.diffViewLabel}</label>
                        <select id="diff-view" class="ciphers-settings-select">
                            <option value="split" selected>{$tool_ui.diffViewSplit}</option>
                            <option value="inline">{$tool_ui.diffViewInline}</option>
                        </select>
                    </div>
                </div>

                <div class="diff__options">
                    <label class="diff__check"><input type="checkbox" id="diff-ignore-case"><span>{$tool_ui.diffIgnoreCase}</span></label>
                    <label class="diff__check"><input type="checkbox" id="diff-ignore-ws"><span>{$tool_ui.diffIgnoreWhitespace}</span></label>
                    <label class="diff__check"><input type="checkbox" id="diff-trim"><span>{$tool_ui.diffTrim}</span></label>
                    <label class="diff__check"><input type="checkbox" id="diff-ignore-empty"><span>{$tool_ui.diffIgnoreEmpty}</span></label>
                    <label class="diff__check"><input type="checkbox" id="diff-sort"><span>{$tool_ui.diffSortLines}</span></label>
                    <label class="diff__check"><input type="checkbox" id="diff-only-changes"><span>{$tool_ui.diffOnlyChanges}</span></label>
                </div>
            </div>

            <div class="ciphers-unified__examples-row diff__examples-row">
                <span class="ciphers-unified__examples-label">{$tool_ui.tryLabel}</span>
                <div class="ciphers-example-chips">
                    <button class="ciphers-example-chip diff-example" type="button"
                            data-diff-a="The quick brown fox jumps over the lazy dog.&#10;Second line stays the same.&#10;Third line will be removed."
                            data-diff-b="The quick brown cat jumps over the lazy dog.&#10;Second line stays the same.">Text edit</button>
                    <button class="ciphers-example-chip diff-example" type="button"
                            data-diff-a="function sum(a, b) {ldelim}&#10;  return a + b&#10;{rdelim}"
                            data-diff-b="function sum(a, b, c) {ldelim}&#10;  return a + b + c&#10;{rdelim}">Code</button>
                    <button class="ciphers-example-chip diff-example" type="button"
                            data-diff-a="apple&#10;banana&#10;cherry&#10;date"
                            data-diff-b="apple&#10;blueberry&#10;cherry&#10;elderberry&#10;date">List</button>
                </div>
            </div>
        </div>

        <div class="ciphers-unified__body">
            <div class="ciphers-split">
                <div class="ciphers-split__col">
                    <div class="ciphers-unified__field-header">
                        <span class="ciphers-unified__field-label">{$tool_ui.diffOriginalLabel}</span>
                        <span class="ciphers-unified__counter" id="diff-count-a">0 {$tool_ui.diffLinesLabel}</span>
                    </div>
                    <textarea class="form-control ciphers-textarea ciphers-unified__textarea diff__textarea"
                              id="diff-input-a" rows="10" spellcheck="false"
                              placeholder="{$tool_ui.diffPlaceholderOriginal}"></textarea>
                </div>
                <div class="ciphers-split__col">
                    <div class="ciphers-unified__field-header">
                        <span class="ciphers-unified__field-label">{$tool_ui.diffChangedLabel}</span>
                        <span class="ciphers-unified__counter" id="diff-count-b">0 {$tool_ui.diffLinesLabel}</span>
                    </div>
                    <textarea class="form-control ciphers-textarea ciphers-unified__textarea diff__textarea"
                              id="diff-input-b" rows="10" spellcheck="false"
                              placeholder="{$tool_ui.diffPlaceholderChanged}"></textarea>
                </div>
            </div>

            <div class="diff__actions">
                <button class="btn ciphers-unified__btn-ghost" type="button" id="diff-swap"><i class="bi bi-arrow-left-right"></i>{$tool_ui.diffSwapLabel}</button>
                <button class="btn ciphers-unified__btn-ghost" type="button" id="diff-clear"><i class="bi bi-x-lg"></i>{$tool_ui.clearLabel}</button>
                <button class="btn ciphers-unified__btn-ghost" type="button" id="diff-copy"><i class="bi bi-clipboard"></i>{$tool_ui.diffCopyLabel}</button>
                <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-share"><i class="bi bi-share"></i>{$tool_ui.shareLabel}</button>
            </div>

            <div class="ciphers-unified__output-wrap diff__result-wrap">
                <div class="ciphers-unified__field-header">
                    <span class="ciphers-unified__field-label ciphers-unified__field-label--result">{$tool_ui.resultLabel}</span>
                    <div class="diff__nav" id="diff-nav" hidden>
                        <span class="diff__hunk-pos" id="diff-hunk-pos"></span>
                        <button class="btn ciphers-unified__btn-ghost diff__nav-btn" type="button" id="diff-prev" title="{$tool_ui.diffPrevLabel}"><i class="bi bi-chevron-up"></i></button>
                        <button class="btn ciphers-unified__btn-ghost diff__nav-btn" type="button" id="diff-next" title="{$tool_ui.diffNextLabel}"><i class="bi bi-chevron-down"></i></button>
                    </div>
                </div>

                <div class="ciphers-result-card diff__result-card">
                    <div class="diff__statusbar" id="diff-statusbar" hidden>
                        <div class="diff__stats" id="diff-stats"></div>
                    </div>
                    <div class="diff__result" id="diff-result">
                        <p class="diff__empty" id="diff-empty">{$tool_ui.diffEmptyLabel}</p>
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
