{* Виджет калькулятора инструмента: header (заголовок, табы, settings) + body (textarea, output) + trust. *}
{* Используется на странице инструмента и в hero-блоке категории. *}
{* Параметры: $cipher, $tool_slug, $tool_ui, $tool_ui_json, $widget_heading_tag (по умолчанию "h1"). *}
{* Опционально: $category_intro = ['name', 'description', 'alias', 'chips' => [{alias, name_short}, ...]] — *}
{* при передаче рендерит шапку категории (название, описание, чипы инструментов) над калькулятором *}
{* и скрывает кнопку «в избранное», чтобы получился единый hero. *}
{$widget_heading_tag = $widget_heading_tag|default:'h1'}
{$has_category_intro = isset($category_intro) && $category_intro}
<section class="ciphers-page{if $has_category_intro} ciphers-page--with-category-intro{/if}"
         data-page="cipher-tool"
         data-cipher-tool="{$tool_slug}"
         data-cipher-ui="{$tool_ui_json|escape:'html'}">
    <div class="ciphers-unified" id="ciphers-tool-shell">
        <div class="ciphers-unified__header">
            {if $has_category_intro}
            <div class="ciphers-unified__category-intro">
                <h1 class="ciphers-unified__category-title">{$category_intro.name}</h1>
                {if $category_intro.description|default:''}
                <p class="ciphers-unified__category-desc">{$category_intro.description}</p>
                {/if}
                {if $category_intro.chips|default:[]}
                <div class="ciphers-unified__category-chips">
                    {foreach $category_intro.chips as $chip}
                        <a class="ciphers-unified__category-chip" href="{$locale_prefix}/{$category_intro.alias}/{$chip.alias}">{$chip.name_short}</a>
                    {/foreach}
                </div>
                {/if}
            </div>
            {/if}
            <div class="ciphers-unified__title-row">
                <{$widget_heading_tag} class="ciphers-unified__title">{$cipher.name}</{$widget_heading_tag}>
                {if !$has_category_intro}
                <button class="btn ciphers-unified__btn-ghost ciphers-unified__btn-favorite"
                        id="ciphers-favorite"
                        type="button"
                        data-slug="{$tool_slug|escape:'html'}"
                        data-name="{$cipher.name|escape:'html'}"
                        title="{$tool_ui.favoriteAddLabel|default:'Add to favorites'}">
                    <i class="bi bi-star" id="ciphers-favorite-icon"></i>
                </button>
                {/if}
            </div>
            <p class="ciphers-unified__desc">{$cipher.description}</p>

            <div class="ciphers-unified__controls-row">

                <div class="ciphers-tabs" role="tablist">
                    <button class="ciphers-tab ciphers-tab--active" type="button" id="tab-encode" role="tab" aria-selected="true">{$tool_ui.tabEncode}</button>
                    <button class="ciphers-tab{if $tool_ui.oneWayMode|default:false} d-none{/if}" type="button" id="tab-decode" role="tab" aria-selected="false">{$tool_ui.tabDecode}</button>
                </div>

                <div class="ciphers-settings">
                    {foreach $tool_ui.settings|default:[] as $setting}
                        {if $setting.type != 'textarea' && $setting.type != 'matrix'}
                        <div class="ciphers-settings-item">
                            {if $setting.type == 'select'}
                                <label class="ciphers-settings-label" for="{$setting.id|escape}">{$setting.label}</label>
                                <select id="{$setting.id|escape}" class="{$setting.class|default:'ciphers-settings-select'|escape}">
                                    {foreach $setting.options|default:[] as $opt}
                                        <option value="{$opt.value|escape}"
                                            {foreach $opt.attrs|default:[] as $attrName => $attrValue}
                                                {$attrName|escape}="{$attrValue|escape}"
                                            {/foreach}
                                            {if $opt.selected|default:false}selected{/if}
                                        >
                                            {$opt.label}
                                        </option>
                                    {/foreach}
                                </select>
                            {elseif $setting.type == 'number_stepper'}
                                <label class="ciphers-settings-label" for="{$setting.id|escape}">{$setting.label}</label>
                                <div class="ciphers-settings-shift-group">
                                    <button class="ciphers-settings-shift-btn" id="{$setting.decrementId|default:''|escape}" type="button">−</button>
                                    <input id="{$setting.id|escape}"
                                           type="number"
                                           class="{$setting.class|default:'ciphers-settings-shift-input'|escape}"
                                           min="{$setting.min|default:0}"
                                           step="{$setting.step|default:1}"
                                           value="{$setting.value|default:0}"
                                           max="{$setting.max|default:39}">
                                    <button class="ciphers-settings-shift-btn" id="{$setting.incrementId|default:''|escape}" type="button">+</button>
                                </div>
                            {elseif $setting.type == 'text'}
                                <label class="ciphers-settings-label" for="{$setting.id|escape}">{$setting.label}</label>
                                {if $setting.shuffleKey|default:false}
                                <div class="ciphers-settings-key-group">
                                    <input id="{$setting.id|escape}"
                                           type="text"
                                           class="{$setting.class|default:'ciphers-settings-input'|escape}"
                                           placeholder="{$setting.placeholder|default:''|escape}"
                                           value="{$setting.value|default:''|escape}">
                                    <button class="ciphers-settings-shuffle-btn" id="ciphers-key-shuffle" type="button" title="{$setting.shuffleLabel|default:'Shuffle'|escape}">
                                        <i class="bi bi-shuffle"></i>
                                    </button>
                                </div>
                                {else}
                                <input id="{$setting.id|escape}"
                                       type="text"
                                       class="{$setting.class|default:'ciphers-settings-input'|escape}"
                                       placeholder="{$setting.placeholder|default:''|escape}"
                                       value="{$setting.value|default:''|escape}">
                                {/if}
                            {/if}
                        </div>
                        {/if}
                    {/foreach}
                </div>

                {if $tool_ui.relatedToolUrl|default:''}
                <a class="ciphers-hint-chip" href="{$tool_ui.relatedToolUrl|escape}">
                    <i class="bi bi-search"></i>{$tool_ui.relatedToolLabel|default:''|escape}
                </a>
                {/if}

            </div>

        </div>

        <div class="ciphers-unified__body">

            {foreach $tool_ui.settings|default:[] as $setting}
                {if $setting.type == 'textarea'}
                <div class="ciphers-unified__key-area"{if $setting.encodeOnly|default:false} data-encode-only="1"{/if}>
                    <div class="ciphers-unified__field-header">
                        <span class="ciphers-unified__field-label">{$setting.label|escape}</span>
                        {if $setting.showCapacity|default:false}
                        <span id="{$setting.id|escape}-capacity" class="ciphers-cover-capacity"></span>
                        {/if}
                    </div>
                    <textarea id="{$setting.id|escape}"
                              class="{$setting.class|default:'ciphers-settings-textarea'|escape}"
                              placeholder="{$setting.placeholder|default:''|escape}"
                              rows="3">{$setting.value|default:''|escape}</textarea>
                    <div class="ciphers-unified__key-area-footer">
                        {if $setting.generateKey|default:false}
                            <button class="ciphers-settings-generate-btn" type="button" id="ciphers-generate-key">
                                <i class="bi bi-shuffle"></i> {$setting.generateKeyLabel|default:''|escape}
                            </button>
                        {/if}
                        {if $setting.hint|default:''}
                            <p class="ciphers-settings-hint">{$setting.hint|escape}</p>
                        {/if}
                    </div>
                </div>
                {/if}
            {/foreach}

            {foreach $tool_ui.settings|default:[] as $setting}
                {if $setting.type == 'matrix'}
                <div class="ciphers-unified__matrix-area">
                    <div class="ciphers-settings-matrix"
                         data-matrix-control
                         data-matrix-input="{$setting.id|escape}"
                         data-matrix-valid-label="{$setting.validLabel|default:'Valid key matrix'|escape}"
                         data-matrix-invalid-label="{$setting.invalidLabel|default:'Matrix is not invertible for this alphabet'|escape}"
                         data-matrix-determinant-label="{$setting.determinantLabel|default:'det'|escape}">
                        <input id="{$setting.id|escape}"
                               type="hidden"
                               value="{$setting.value|default:''|escape}">
                        <div class="ciphers-unified__field-header">
                            <div class="ciphers-settings-matrix__head-left">
                                <span class="ciphers-unified__field-label">{$setting.label|escape}</span>
                                <div class="ciphers-settings-matrix__sizes" role="group" aria-label="{$setting.sizeLabel|default:'Matrix size'|escape}">
                                    {foreach $setting.sizes|default:[] as $size}
                                        <button type="button"
                                                class="ciphers-settings-matrix__size{if $size == ($setting.size|default:2)} ciphers-settings-matrix__size--active{/if}"
                                                data-matrix-size="{$size|escape}">
                                            {$size|escape}×{$size|escape}
                                        </button>
                                    {/foreach}
                                </div>
                            </div>
                            <div class="ciphers-settings-matrix__head-right">
                                <span class="ciphers-settings-label">{$setting.statusLabel|default:'Matrix status'|escape}</span>
                                <span class="ciphers-settings-matrix__status" data-matrix-status></span>
                            </div>
                        </div>
                        <div class="ciphers-settings-matrix__grid"
                             data-matrix-grid
                             style="--matrix-size: {$setting.size|default:2};"></div>
                    </div>
                </div>
                {/if}
            {/foreach}

            <div class="ciphers-unified__input-wrap">
                <div class="ciphers-unified__field-header">
                    <span class="ciphers-unified__field-label" id="ciphers-input-label">{$tool_ui.inputLabelEncode}</span>
                    <div class="ciphers-unified__counter-group">
                        <span class="ciphers-unified__counter" id="ciphers-counter">0 {$tool_ui.charsLabel} · 0 {$tool_ui.bytesLabel}</span>
                        <button class="btn ciphers-unified__btn-ghost ciphers-unified__btn-clear" type="button" id="ciphers-clear">
                            <i class="bi bi-x-lg"></i>{$tool_ui.clearLabel|default:'Clear'}
                        </button>
                    </div>
                </div>
                <textarea class="form-control ciphers-textarea ciphers-unified__textarea"
                          id="ciphers-input"
                          rows="7"
                          {if isset($tool_ui.inputMaxLength)}maxlength="{$tool_ui.inputMaxLength|escape}"{/if}
                          placeholder="{$tool_ui.placeholderEncode}"></textarea>
            </div>

            {if $tool_ui.kdfVerifyMode|default:false}
            <div class="ciphers-unified__key-area" data-decode-only="1">
                <div class="ciphers-unified__field-header">
                    <span class="ciphers-unified__field-label">{$tool_ui.kdfVerifyHashLabel|default:'Hash to verify against'}</span>
                </div>
                <textarea id="ciphers-kdf-verify-hash"
                          class="ciphers-settings-textarea"
                          placeholder="{$tool_ui.kdfVerifyHashPlaceholder|default:''|escape}"
                          rows="3"></textarea>
            </div>
            {/if}

            <div class="ciphers-unified__examples-row">
                <span class="ciphers-unified__examples-label">{$tool_ui.tryLabel}</span>
                <div class="ciphers-example-chips">
                    {foreach $tool_ui.exampleChips as $chip}
                    <button class="ciphers-example-chip" type="button" data-example="{$chip.value|escape:'html'}" data-key-input="{$tool_ui.exampleKeyInputId|default:'ciphers-key'|escape:'html'}"{if isset($chip.alphabet)} data-alphabet="{$chip.alphabet|escape:'html'}"{/if}{if isset($chip.key)} data-key="{$chip.key|escape:'html'}"{/if}{if isset($chip.shift)} data-shift="{$chip.shift|escape:'html'}"{/if}{if isset($chip.direction)} data-direction="{$chip.direction|escape:'html'}"{/if}{if isset($chip.delimiter)} data-delimiter="{$chip.delimiter|escape:'html'}"{/if}{if isset($chip.encoding)} data-encoding="{$chip.encoding|escape:'html'}"{/if}{if isset($chip.key_format)} data-key-format="{$chip.key_format|escape:'html'}"{/if}{if isset($chip.alberti_index)} data-alberti-index="{$chip.alberti_index|escape:'html'}"{/if}{if isset($chip.enigma_reflector)} data-enigma-reflector="{$chip.enigma_reflector|escape:'html'}"{/if}{if isset($chip.enigma_rotor_left)} data-enigma-rotor-left="{$chip.enigma_rotor_left|escape:'html'}"{/if}{if isset($chip.enigma_rotor_middle)} data-enigma-rotor-middle="{$chip.enigma_rotor_middle|escape:'html'}"{/if}{if isset($chip.enigma_rotor_right)} data-enigma-rotor-right="{$chip.enigma_rotor_right|escape:'html'}"{/if}{if isset($chip.enigma_ring_left)} data-enigma-ring-left="{$chip.enigma_ring_left|escape:'html'}"{/if}{if isset($chip.enigma_ring_middle)} data-enigma-ring-middle="{$chip.enigma_ring_middle|escape:'html'}"{/if}{if isset($chip.enigma_ring_right)} data-enigma-ring-right="{$chip.enigma_ring_right|escape:'html'}"{/if}{if isset($chip.enigma_pos_left)} data-enigma-pos-left="{$chip.enigma_pos_left|escape:'html'}"{/if}{if isset($chip.enigma_pos_middle)} data-enigma-pos-middle="{$chip.enigma_pos_middle|escape:'html'}"{/if}{if isset($chip.enigma_pos_right)} data-enigma-pos-right="{$chip.enigma_pos_right|escape:'html'}"{/if}{if isset($chip.enigma_plugboard)} data-enigma-plugboard="{$chip.enigma_plugboard|escape:'html'}"{/if}{if isset($chip.anagram_mode)} data-anagram-mode="{$chip.anagram_mode|escape:'html'}"{/if}{if isset($chip.settings_json)} data-settings="{$chip.settings_json|escape:'html'}"{/if}>{$chip.label}</button>
                    {/foreach}
                    {if isset($tool_ui.timestampConverterMode) && $tool_ui.timestampConverterMode}
                    <button class="ciphers-example-chip" type="button" id="ciphers-ts-now"><i class="bi bi-clock"></i> {$tool_ui.tsNowLabel|default:'Now'}</button>
                    {/if}
                </div>
            </div>

            {if ($tool_ui.calculationMode|default:'client') == 'api' || $tool_ui.manualRun|default:false}
                <div class="ciphers-unified__actions">
                    <button class="ciphers-unified__run-btn" type="button" id="ciphers-primary">
                        <span class="run-btn-content">{$tool_ui.runLabel}</span>
                        <span class="run-btn-spinner" aria-hidden="true"></span>
                    </button>
                    {if ($tool_ui.calculationMode|default:'client') == 'api' && !($tool_ui.disableLiveMode|default:false)}
                    <label class="ciphers-unified__toggle-wrap" for="ciphers-live-mode">
                        <input type="checkbox" id="ciphers-live-mode" class="ciphers-unified__toggle-input">
                        <span class="ciphers-unified__toggle-track">
                            <span class="ciphers-unified__toggle-thumb"></span>
                        </span>
                        <span class="ciphers-unified__toggle-label">Live Mode</span>
                    </label>
                    {/if}
                </div>
            {/if}

            <div class="ciphers-unified__output-wrap">
                <div class="ciphers-unified__field-header">
                    <span class="ciphers-unified__field-label ciphers-unified__field-label--result">{$tool_ui.resultLabel}</span>
                    <div class="ciphers-unified__output-actions">
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-copy"><i class="bi bi-clipboard"></i>{$tool_ui.copyLabel}</button>
                        {if isset($tool_ui.jsonFormatterMode) && $tool_ui.jsonFormatterMode}
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-json-sort" data-encode-only><i class="bi bi-sort-alpha-down"></i>{$tool_ui.jsonFormatterSortLabel}</button>
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-json-download"><i class="bi bi-download"></i>{$tool_ui.jsonFormatterDownloadLabel}</button>
                        {/if}
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-share"><i class="bi bi-share"></i>{$tool_ui.shareLabel}</button>
                    </div>
                </div>

                <div class="ciphers-result-card" id="ciphers-result-card">
                    <textarea class="form-control ciphers-textarea ciphers-unified__textarea ciphers-unified__output"
                              id="ciphers-output"
                              rows="6"
                              readonly
                              placeholder="{$tool_ui.placeholderOutput}"></textarea>
                    <div class="ciphers-visual-output" id="ciphers-visual-output"></div>
                    <div class="ciphers-feedback" id="ciphers-feedback" aria-live="polite"></div>
                </div>
            </div>
        </div>

        <div class="ciphers-trust">
            {foreach $tool_ui.trustItems as $item}
            <span class="ciphers-trust__item">✓ {$item}</span>
            {/foreach}
        </div>
    </div>
</section>
