<article class="glossary-term">
    <header class="glossary-hero">
        <div class="glossary-hero__inner">
            <span class="glossary-hero__eyebrow">{$t.GLOSSARY_BREADCRUMB}</span>
            <h1 class="glossary-hero__title">{$term.name}</h1>
            {if $term.aliases}
            <div class="glossary-hero__aliases">
                {foreach $term.aliases as $alias}<span class="glossary-hero__alias">{$alias}</span>{/foreach}
            </div>
            {/if}
            {if $term.short}<p class="glossary-hero__lead">{$term.short}</p>{/if}
        </div>
    </header>

    {include file="partials/ad_block.tpl" position="after_hero"}

    {if $body}
    <section class="panel ciphers-hub-panel glossary-article">
        <div class="panel-content glossary-term__body">
            {foreach $body as $block}
                {if $block.title}<h2 class="glossary-article__h">{$block.title}</h2>{/if}
                {$block.html nofilter}
            {/foreach}
        </div>
    </section>
    {/if}

    {if $faq}
    <section class="panel ciphers-hub-panel">
        <div class="panel-heading">
            <div class="panel-title">{$t.GLOSSARY_FAQ_TITLE}</div>
        </div>
        <div class="panel-content">
            <div class="accordion" id="glossary-faq">
                {foreach $faq as $item}
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#gfaq-{$item@iteration}">
                            <span class="accordion-button-text">{$item.question}</span>
                        </button>
                    </h2>
                    <div id="gfaq-{$item@iteration}" class="accordion-collapse collapse" data-bs-parent="#glossary-faq">
                        <div class="accordion-body">{$item.answer nofilter}</div>
                    </div>
                </div>
                {/foreach}
            </div>
        </div>
    </section>
    {/if}

    {include file="partials/ad_block.tpl" position="after_faq"}

    {if $related_tools || $related_terms}
    <section class="panel ciphers-hub-panel">
        <div class="panel-heading">
            <div class="panel-title">{$t.GLOSSARY_SEE_ALSO}</div>
        </div>
        <div class="panel-content glossary-seealso">
            {if $related_tools}
            <div class="glossary-seealso__group">
                <span class="glossary-seealso__label">{$t.GLOSSARY_RELATED_TOOLS}</span>
                <div class="glossary-related-links">
                    {foreach $related_tools as $tool}
                    <a class="glossary-related-link" href="{$tool.url}"><i class="bi bi-tools"></i>{$tool.label}</a>
                    {/foreach}
                </div>
            </div>
            {/if}
            {if $related_terms}
            <div class="glossary-seealso__group">
                <span class="glossary-seealso__label">{$t.GLOSSARY_RELATED_TERMS}</span>
                <div class="glossary-related-links">
                    {foreach $related_terms as $rel}
                    <a class="glossary-related-link" href="{$rel.url}"><i class="bi bi-book"></i>{$rel.name}</a>
                    {/foreach}
                </div>
            </div>
            {/if}
        </div>
    </section>
    {/if}

    <div class="glossary-term__back">
        <a href="{$locale_prefix|default:''}/glossary"><i class="bi bi-arrow-left"></i> {$t.GLOSSARY_BACK_TO_INDEX}</a>
    </div>
</article>
