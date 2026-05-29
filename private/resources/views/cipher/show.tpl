<section class="ciphers-page"
         data-page="cipher-tool"
         data-cipher-tool="{$tool_slug}"
         data-cipher-ui="{$tool_ui_json|escape:'html'}">
    <div class="ciphers-unified" id="ciphers-tool-shell">
        <div class="ciphers-unified__header">
            <h1 class="ciphers-unified__title">{$cipher.name}</h1>
            <p class="ciphers-unified__desc">{$cipher.description}</p>

            <div class="ciphers-unified__controls-row">

                <div class="ciphers-tabs" role="tablist">
                    <button class="ciphers-tab ciphers-tab--active" type="button" id="tab-encode" role="tab" aria-selected="true">{$tool_ui.tabEncode}</button>
                    <button class="ciphers-tab" type="button" id="tab-decode" role="tab" aria-selected="false">{$tool_ui.tabDecode}</button>
                </div>

                <div class="ciphers-settings">
                    {foreach $tool_ui.settings|default:[] as $setting}
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
                                <input id="{$setting.id|escape}"
                                       type="text"
                                       class="{$setting.class|default:'ciphers-settings-input'|escape}"
                                       placeholder="{$setting.placeholder|default:''|escape}"
                                       value="{$setting.value|default:''|escape}">
                            {/if}
                        </div>
                    {/foreach}
                </div>
            </div>

        </div>

        <div class="ciphers-unified__body">
            
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
                          placeholder="{$tool_ui.placeholderEncode}"></textarea>
            </div>

            <div class="ciphers-unified__examples-row">
                <span class="ciphers-unified__examples-label">{$tool_ui.tryLabel}</span>
                <div class="ciphers-example-chips">
                    {foreach $tool_ui.exampleChips as $chip}
                    <button class="ciphers-example-chip" type="button" data-example="{$chip.value|escape:'html'}"{if isset($chip.alphabet)} data-alphabet="{$chip.alphabet|escape:'html'}"{/if}{if isset($chip.key)} data-key="{$chip.key|escape:'html'}"{/if}>{$chip.label}</button>
                    {/foreach}
                </div>
            </div>

            {if ($tool_ui.calculationMode|default:'client') == 'api'}
                <div class="ciphers-unified__actions">
                    <button class="ciphers-unified__run-btn" type="button" id="ciphers-primary">{$tool_ui.runLabel}</button>
                    <label class="ciphers-unified__toggle-wrap" for="ciphers-live-mode">
                        <input type="checkbox" id="ciphers-live-mode" class="ciphers-unified__toggle-input">
                        <span class="ciphers-unified__toggle-track">
                            <span class="ciphers-unified__toggle-thumb"></span>
                        </span>
                        <span class="ciphers-unified__toggle-label">Live Mode</span>
                    </label>
                </div>
            {/if}

            <div class="ciphers-unified__output-wrap">
                <div class="ciphers-unified__field-header">
                    <span class="ciphers-unified__field-label ciphers-unified__field-label--result">{$tool_ui.resultLabel}</span>
                    <div class="ciphers-unified__output-actions">
                        <button class="btn ciphers-unified__btn-ghost" type="button" id="ciphers-copy"><i class="bi bi-clipboard"></i>{$tool_ui.copyLabel}</button>
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

{if $examples}
<section class="panel ciphers-hub-panel">
    <div class="panel-heading">
        <div class="panel-title">{$tool_ui.examplesTitle}</div>
    </div>
    <div class="panel-content">
        <div class="b64-examples-grid">
            {foreach $examples as $example}
                <article class="b64-example-card">
                    {if $example.label}<span class="b64-example-card__label">{$example.label}</span>{/if}
                    {if $example.key}<span class="b64-example-card__key-badge">Key: <code>{$example.key|escape}</code></span>{/if}
                    <div class="b64-example-card__row">
                        <div class="b64-example-card__slot">
                            <span class="b64-example-card__slot-tag">{$tool_ui.inputTag}</span>
                            <code class="b64-example-card__code">{$example.input|escape}</code>
                        </div>
                        {if $example.output}
                            <div class="b64-example-card__slot b64-example-card__slot--output">
                                <span class="b64-example-card__slot-tag">{$tool_ui.outputTag}</span>
                                <code class="b64-example-card__code b64-example-card__code--output">{$example.output|escape}</code>
                            </div>
                        {/if}
                    </div>
                    {if $example.desc}<p class="b64-example-card__desc">{$example.desc|escape}</p>{/if}
                    <button class="b64-example-card__use ciphers-example-use" type="button"
                            data-example-text="{$example.input|escape:'html'}"
                            {if $example.key}data-key="{$example.key|escape:'html'}"{/if}
                            data-alphabet="{$example.language|escape:'html'}"
                            {if $example.direction}data-direction="{$example.direction|escape:'html'}"{/if}>{$tool_ui.useExampleLabel}</button>
                </article>
            {/foreach}
        </div>
    </div>
</section>
{/if}

{if $blocks}
    {foreach $blocks as $block}
    <section class="panel ciphers-hub-panel">
        <div class="panel-heading">
            <div class="panel-title">{$block.title|default:$tool_ui.infoTitle}</div>
        </div>
        <div class="panel-content">
            {$block.text nofilter}
        </div>
    </section>
    {/foreach}
{/if}

{if $faq}
<section class="panel ciphers-hub-panel">
    <div class="panel-heading">
        <div class="panel-title">{$tool_ui.faqTitle}</div>
    </div>
    <div class="panel-content">
        <div class="accordion" id="cipher-faq">
            {foreach $faq as $item}
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-{$item.id}">
                        {$item.question}
                    </button>
                </h2>
                <div id="faq-{$item.id}" class="accordion-collapse collapse" data-bs-parent="#cipher-faq">
                    <div class="accordion-body">{$item.answer}</div>
                </div>
            </div>
            {/foreach}
        </div>
    </div>
</section>
{/if}

{if $related}
<section class="panel ciphers-hub-panel">
    <div class="panel-heading">
        <div class="panel-title">{$tool_ui.relatedTitle}</div>
        <a class="ciphers-related-all-link" href="/{$category.alias}">{$all_in_category_label} →</a>
    </div>
    <div class="panel-content">
        <div class="ciphers-category-hub-grid">
            {foreach $related as $tool}
            <article class="ciphers-category-hub-card">
                <h2 class="ciphers-category-hub-card__title">
                    <a href="/{$category.alias}/{$tool.alias}">{$tool.name}</a>
                </h2>
                {if $tool.description_short}
                <p class="ciphers-category-hub-card__desc">{$tool.description_short}</p>
                {/if}
                <span class="ciphers-category-hub-card__arrow" aria-hidden="true">→</span>
            </article>
            {/foreach}
        </div>
    </div>
</section>
{/if}
