{if $tool_ui.diffMode|default:false}
{include file="cipher/_diff_widget.tpl" cipher=$cipher tool_slug=$tool_slug tool_ui=$tool_ui tool_ui_json=$tool_ui_json}
{elseif $tool_ui.uuidMode|default:false}
{include file="cipher/_uuid_widget.tpl" cipher=$cipher tool_slug=$tool_slug tool_ui=$tool_ui tool_ui_json=$tool_ui_json}
{elseif $tool_ui.passwordMode|default:false}
{include file="cipher/_password_widget.tpl" cipher=$cipher tool_slug=$tool_slug tool_ui=$tool_ui tool_ui_json=$tool_ui_json}
{elseif $tool_ui.randomStringMode|default:false}
{include file="cipher/_randomstring_widget.tpl" cipher=$cipher tool_slug=$tool_slug tool_ui=$tool_ui tool_ui_json=$tool_ui_json}
{else}
{include file="cipher/_tool_widget.tpl" cipher=$cipher tool_slug=$tool_slug tool_ui=$tool_ui tool_ui_json=$tool_ui_json}
{/if}

{include file="partials/ad_block.tpl" position="after_hero"}

{if $examples}
<section class="panel ciphers-hub-panel">
    <div class="panel-heading">
        <div class="panel-title">{$tool_ui.examplesTitle}</div>
    </div>
    <div class="panel-content">
        {if $tool_ui.uuidMode|default:false}
        <div class="b64-examples-grid">
            {foreach $examples as $example}
                <article class="b64-example-card">
                    {if $example.label}<span class="b64-example-card__label">{$example.label}</span>{/if}
                    <div class="b64-example-card__badges">
                        {if $example.settings.version|default:''}<span class="b64-example-card__key-badge">{$tool_ui.uuidVersionLabel}: <code>{$example.settings.version|escape}</code></span>{/if}
                        {if $example.settings.count|default:''}<span class="b64-example-card__key-badge">{$tool_ui.uuidCountLabel}: <code>{$example.settings.count|escape}</code></span>{/if}
                        {if $example.settings.name|default:''}<span class="b64-example-card__key-badge">{$tool_ui.uuidNameLabel}: <code>{$example.settings.name|escape}</code></span>{/if}
                    </div>
                    {if $example.output}
                    <div class="b64-example-card__row">
                        <div class="b64-example-card__slot b64-example-card__slot--output">
                            <span class="b64-example-card__slot-tag">{$tool_ui.outputTag}</span>
                            <code class="b64-example-card__code b64-example-card__code--output">{$example.output|escape}</code>
                        </div>
                    </div>
                    {/if}
                    {if $example.desc}<p class="b64-example-card__desc">{$example.desc|escape}</p>{/if}
                    <button class="b64-example-card__use uuid-example" type="button"
                            {if isset($example.settings.version)}data-uuid-version="{$example.settings.version|escape:'html'}"{/if}
                            {if isset($example.settings.count)}data-uuid-count="{$example.settings.count|escape:'html'}"{/if}
                            {if isset($example.settings.name)}data-uuid-name="{$example.settings.name|escape:'html'}"{/if}
                            {if isset($example.settings.namespace)}data-uuid-namespace="{$example.settings.namespace|escape:'html'}"{/if}
                            {if isset($example.settings.uppercase)}data-uuid-uppercase="{if $example.settings.uppercase}1{else}0{/if}"{/if}
                            {if isset($example.settings.hyphens)}data-uuid-hyphens="{if $example.settings.hyphens}1{else}0{/if}"{/if}
                            {if isset($example.settings.braces)}data-uuid-braces="{if $example.settings.braces}1{else}0{/if}"{/if}
                            {if isset($example.settings.urn)}data-uuid-urn="{if $example.settings.urn}1{else}0{/if}"{/if}>{$tool_ui.useExampleLabel}</button>
                </article>
            {/foreach}
        </div>
        {elseif $tool_ui.passwordMode|default:false}
        <div class="b64-examples-grid">
            {foreach $examples as $example}
                <article class="b64-example-card">
                    {if $example.label}<span class="b64-example-card__label">{$example.label}</span>{/if}
                    <div class="b64-example-card__badges">
                        {if $example.settings.mode|default:''}<span class="b64-example-card__key-badge">{if $example.settings.mode == 'passphrase'}{$tool_ui.pwdModePassphrase}{else}{$tool_ui.pwdModePassword}{/if}</span>{/if}
                        {if $example.settings.length|default:''}<span class="b64-example-card__key-badge">{$tool_ui.pwdLengthLabel}: <code>{$example.settings.length|escape}</code></span>{/if}
                        {if $example.settings.words|default:''}<span class="b64-example-card__key-badge">{$tool_ui.pwdWordsLabel}: <code>{$example.settings.words|escape}</code></span>{/if}
                    </div>
                    {if $example.desc}<p class="b64-example-card__desc">{$example.desc|escape}</p>{/if}
                    <button class="b64-example-card__use pwd-example" type="button"
                            {if isset($example.settings.mode)}data-pwd-mode="{$example.settings.mode|escape:'html'}"{/if}
                            {if isset($example.settings.length)}data-pwd-length="{$example.settings.length|escape:'html'}"{/if}
                            {if isset($example.settings.words)}data-pwd-words="{$example.settings.words|escape:'html'}"{/if}
                            {if isset($example.settings.separator)}data-pwd-separator="{$example.settings.separator|escape:'html'}"{/if}
                            {if isset($example.settings.wordCase)}data-pwd-case="{$example.settings.wordCase|escape:'html'}"{/if}
                            {if isset($example.settings.lower)}data-pwd-lower="{if $example.settings.lower}1{else}0{/if}"{/if}
                            {if isset($example.settings.upper)}data-pwd-upper="{if $example.settings.upper}1{else}0{/if}"{/if}
                            {if isset($example.settings.digits)}data-pwd-digits="{if $example.settings.digits}1{else}0{/if}"{/if}
                            {if isset($example.settings.symbols)}data-pwd-symbols="{if $example.settings.symbols}1{else}0{/if}"{/if}
                            {if isset($example.settings.excludeSimilar)}data-pwd-exclude-similar="{if $example.settings.excludeSimilar}1{else}0{/if}"{/if}
                            {if isset($example.settings.addNumber)}data-pwd-add-number="{if $example.settings.addNumber}1{else}0{/if}"{/if}>{$tool_ui.useExampleLabel}</button>
                </article>
            {/foreach}
        </div>
        {elseif $tool_ui.randomStringMode|default:false}
        <div class="b64-examples-grid">
            {foreach $examples as $example}
                <article class="b64-example-card">
                    {if $example.label}<span class="b64-example-card__label">{$example.label}</span>{/if}
                    <div class="b64-example-card__badges">
                        {if $example.settings.length|default:''}<span class="b64-example-card__key-badge">{$tool_ui.rsLengthLabel}: <code>{$example.settings.length|escape}</code></span>{/if}
                        {if $example.settings.custom|default:''}<span class="b64-example-card__key-badge">{$tool_ui.rsCustomLabel}: <code>{$example.settings.custom|escape}</code></span>{/if}
                    </div>
                    {if $example.desc}<p class="b64-example-card__desc">{$example.desc|escape}</p>{/if}
                    <button class="b64-example-card__use rs-example" type="button"
                            {if isset($example.settings.length)}data-rs-length="{$example.settings.length|escape:'html'}"{/if}
                            {if isset($example.settings.lower)}data-rs-lower="{if $example.settings.lower}1{else}0{/if}"{/if}
                            {if isset($example.settings.upper)}data-rs-upper="{if $example.settings.upper}1{else}0{/if}"{/if}
                            {if isset($example.settings.digits)}data-rs-digits="{if $example.settings.digits}1{else}0{/if}"{/if}
                            {if isset($example.settings.symbols)}data-rs-symbols="{if $example.settings.symbols}1{else}0{/if}"{/if}
                            {if isset($example.settings.custom)}data-rs-custom="{$example.settings.custom|escape:'html'}"{/if}
                            {if isset($example.settings.excludeSimilar)}data-rs-exclude-similar="{if $example.settings.excludeSimilar}1{else}0{/if}"{/if}
                            {if isset($example.settings.noRepeats)}data-rs-no-repeats="{if $example.settings.noRepeats}1{else}0{/if}"{/if}>{$tool_ui.useExampleLabel}</button>
                </article>
            {/foreach}
        </div>
        {elseif $tool_ui.diffMode|default:false}
        <div class="diff-ex-grid">
            {foreach $examples as $example}
                <article class="diff-ex-card">
                    <div class="diff-ex-card__head">
                        {if $example.label}<span class="diff-ex-card__label">{$example.label}</span>{/if}
                        <button class="b64-example-card__use diff-example" type="button"
                                data-diff-a="{$example.input|escape:'html'}"
                                data-diff-b="{$example.output|escape:'html'}">{$tool_ui.useExampleLabel}</button>
                    </div>
                    <div class="diff-ex-card__cols">
                        <div class="diff-ex-col diff-ex-col--a">
                            <span class="diff-ex-col__tag">{$tool_ui.diffOriginalLabel}</span>
                            <pre class="diff-ex-pre">{$example.input|escape}</pre>
                        </div>
                        <div class="diff-ex-col diff-ex-col--b">
                            <span class="diff-ex-col__tag">{$tool_ui.diffChangedLabel}</span>
                            <pre class="diff-ex-pre">{$example.output|escape}</pre>
                        </div>
                    </div>
                    {if $example.desc}<p class="diff-ex-card__desc">{$example.desc|escape}</p>{/if}
                </article>
            {/foreach}
        </div>
        {else}
        <div class="b64-examples-grid">
            {foreach $examples as $example}
                <article class="b64-example-card">
                    {if $example.label}<span class="b64-example-card__label">{$example.label}</span>{/if}
                    {if $example.matrix_key|default:false}
                    <div class="b64-example-card__matrix-key">
                        <span class="b64-example-card__matrix-key-label">{$tool_ui.exampleKeyLabel|default:'Key'}</span>
                        <div class="b64-example-card__matrix" style="--mc: {$example.matrix_key|count};">
                            {foreach $example.matrix_key as $row}
                                {foreach $row as $cell}
                                    <span>{$cell}</span>
                                {/foreach}
                            {/foreach}
                        </div>
                    </div>
                    {elseif $example.key|default:'' || $example.shift|default:0 || $example.alberti_index|default:'' || $example.settings_badges|default:false}
                    <div class="b64-example-card__badges">
                        {if $example.key|default:''}<span class="b64-example-card__key-badge">{$tool_ui.exampleKeyLabel|default:'Key'}: <code>{$example.key|escape}</code>{if isset($example.key_format) && $example.key_format} ({$example.key_format|upper|escape}){/if}</span>{/if}
                        {if $example.shift|default:0}<span class="b64-example-card__key-badge">Shift: <code>{$example.shift|escape}</code></span>{/if}
                        {if $example.alberti_index|default:''}<span class="b64-example-card__key-badge">Index: <code>{$example.alberti_index|escape}</code></span>{/if}
                        {if $example.settings_badges|default:false}
                            {foreach $example.settings_badges as $badge}
                                <span class="b64-example-card__key-badge">{$badge.label|escape}: <code>{$badge.value|escape}</code></span>
                            {/foreach}
                        {/if}
                    </div>
                    {/if}
                    <div class="b64-example-card__row">
                        <div class="b64-example-card__slot">
                            <span class="b64-example-card__slot-tag">{$tool_ui.inputTag}</span>
                            {if !$example.input}
                                <code class="b64-example-card__code b64-example-card__code--empty">{$tool_ui.exampleEmptyInputLabel|default:'(empty)'|escape}</code>
                            {else}
                                <code class="b64-example-card__code">{$example.input|escape}</code>
                            {/if}
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
                            data-key-input="{$tool_ui.exampleKeyInputId|default:'ciphers-key'|escape:'html'}"
                            {if $example.shift|default:0}data-shift="{$example.shift|escape:'html'}"{/if}
                            data-alphabet="{$example.alphabet|default:$example.language|escape:'html'}"
                            {if $example.delimiter}data-delimiter="{$example.delimiter|escape:'html'}"{/if}
                            {if $example.direction}data-direction="{$example.direction|escape:'html'}"{/if}
                            {if $example.encoding}data-encoding="{$example.encoding|escape:'html'}"{/if}
                            {if isset($example.key_format) && $example.key_format}data-key-format="{$example.key_format|escape:'html'}"{/if}
                            {if $example.alberti_index|default:''}data-alberti-index="{$example.alberti_index|escape:'html'}"{/if}
                            {if isset($example.enigma_reflector)}data-enigma-reflector="{$example.enigma_reflector|escape:'html'}"{/if}
                            {if isset($example.enigma_rotor_left)}data-enigma-rotor-left="{$example.enigma_rotor_left|escape:'html'}"{/if}
                            {if isset($example.enigma_rotor_middle)}data-enigma-rotor-middle="{$example.enigma_rotor_middle|escape:'html'}"{/if}
                            {if isset($example.enigma_rotor_right)}data-enigma-rotor-right="{$example.enigma_rotor_right|escape:'html'}"{/if}
                            {if isset($example.enigma_ring_left)}data-enigma-ring-left="{$example.enigma_ring_left|escape:'html'}"{/if}
                            {if isset($example.enigma_ring_middle)}data-enigma-ring-middle="{$example.enigma_ring_middle|escape:'html'}"{/if}
                            {if isset($example.enigma_ring_right)}data-enigma-ring-right="{$example.enigma_ring_right|escape:'html'}"{/if}
                            {if isset($example.enigma_pos_left)}data-enigma-pos-left="{$example.enigma_pos_left|escape:'html'}"{/if}
                            {if isset($example.enigma_pos_middle)}data-enigma-pos-middle="{$example.enigma_pos_middle|escape:'html'}"{/if}
                            {if isset($example.enigma_pos_right)}data-enigma-pos-right="{$example.enigma_pos_right|escape:'html'}"{/if}
                            {if isset($example.enigma_plugboard)}data-enigma-plugboard="{$example.enigma_plugboard|escape:'html'}"{/if}
                            {if isset($example.anagram_mode)}data-anagram-mode="{$example.anagram_mode|escape:'html'}"{/if}
                            {if isset($example.settings_json)}data-settings="{$example.settings_json|escape:'html'}"{/if}>{$tool_ui.useExampleLabel}</button>
                </article>
            {/foreach}
        </div>
        {/if}
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
    {if $block@first}
        {include file="partials/ad_block.tpl" position="after_first_block"}
    {/if}
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
                        <span class="accordion-button-text">{$item.question}</span>
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
{include file="partials/ad_block.tpl" position="after_faq"}
{/if}

{if $related}
<section class="panel ciphers-hub-panel">
    <div class="panel-heading">
        <div class="panel-title">{$tool_ui.relatedTitle}</div>
    </div>
    <div class="panel-content">
        <div class="ciphers-category-hub-grid">
            {foreach $related as $tool}
            <article class="ciphers-category-hub-card">
                <h2 class="ciphers-category-hub-card__title">
                    <a href="{$locale_prefix}/{$tool.category_alias|default:$category.alias}/{$tool.alias}">{$tool.name}</a>
                </h2>
                {if $tool.description_short}
                <p class="ciphers-category-hub-card__desc">{$tool.description_short}</p>
                {/if}
                <span class="ciphers-category-hub-card__arrow" aria-hidden="true">→</span>
            </article>
            {/foreach}
        </div>
        <div class="ciphers-related-all-wrap">
            <a class="ciphers-related-all-link" href="{$locale_prefix}/{$category.alias}">{$all_in_category_label} →</a>
        </div>
    </div>
</section>
{/if}

{if $glossary_links}
<section class="panel ciphers-hub-panel">
    <div class="panel-heading">
        <div class="panel-title">{$t.GLOSSARY_FROM_TITLE}</div>
    </div>
    <div class="panel-content">
        <div class="glossary-related-links">
            {foreach $glossary_links as $link}
            <a class="glossary-related-link" href="{$link.url}"><i class="bi bi-book"></i>{$link.name}</a>
            {/foreach}
        </div>
    </div>
</section>
{/if}

{if $guide_links}
<section class="panel ciphers-hub-panel">
    <div class="panel-heading">
        <div class="panel-title">{$t.GUIDES_FROM_TITLE}</div>
    </div>
    <div class="panel-content">
        <div class="glossary-related-links">
            {foreach $guide_links as $link}
            <a class="glossary-related-link" href="{$link.url}"><i class="bi bi-journal-text"></i>{$link.title}</a>
            {/foreach}
        </div>
    </div>
</section>
{/if}
