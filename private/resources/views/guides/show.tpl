<article class="glossary-term guide-article">
    <header class="glossary-hero">
        <div class="glossary-hero__inner">
            <span class="glossary-hero__eyebrow">{$category_label}</span>
            <h1 class="glossary-hero__title">{$guide.title}</h1>
            <div class="guide-hero__meta">
                {if $guide.reading_time}<span class="guide-hero__metaitem"><i class="bi bi-clock"></i><span>{$guide.reading_time} {$t.GUIDES_READING_MIN}</span></span>{/if}
                {if $published_at}<span class="guide-hero__metaitem"><i class="bi bi-calendar3"></i><time datetime="{$published_at}">{$published_at_display|default:$published_at}</time></span>{/if}
            </div>
            {if $guide.excerpt}<p class="glossary-hero__lead">{$guide.excerpt}</p>{/if}
        </div>
    </header>

    {include file="partials/ad_block.tpl" position="after_hero"}

    {if $body}
    <section class="panel ciphers-hub-panel glossary-article">
        <div class="panel-content glossary-term__body guide-article__body">
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
            <div class="panel-title">{$t.GUIDES_FAQ_TITLE}</div>
        </div>
        <div class="panel-content">
            <div class="accordion" id="guide-faq">
                {foreach $faq as $item}
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#gdfaq-{$item@iteration}">
                            <span class="accordion-button-text">{$item.question}</span>
                        </button>
                    </h2>
                    <div id="gdfaq-{$item@iteration}" class="accordion-collapse collapse" data-bs-parent="#guide-faq">
                        <div class="accordion-body">{$item.answer nofilter}</div>
                    </div>
                </div>
                {/foreach}
            </div>
        </div>
    </section>
    {/if}

    {include file="partials/ad_block.tpl" position="after_faq"}

    {if $related_tools || $related_guides || $related_terms}
    <section class="panel ciphers-hub-panel">
        <div class="panel-heading">
            <div class="panel-title">{$t.GUIDES_SEE_ALSO}</div>
        </div>
        <div class="panel-content glossary-seealso">
            {if $related_tools}
            <div class="glossary-seealso__group">
                <span class="glossary-seealso__label">{$t.GUIDES_RELATED_TOOLS}</span>
                <div class="glossary-related-links">
                    {foreach $related_tools as $tool}
                    <a class="glossary-related-link" href="{$tool.url}"><i class="bi bi-tools"></i>{$tool.label}</a>
                    {/foreach}
                </div>
            </div>
            {/if}
            {if $related_guides}
            <div class="glossary-seealso__group">
                <span class="glossary-seealso__label">{$t.GUIDES_RELATED_GUIDES}</span>
                <div class="glossary-related-links">
                    {foreach $related_guides as $rel}
                    <a class="glossary-related-link" href="{$rel.url}"><i class="bi bi-journal-text"></i>{$rel.title}</a>
                    {/foreach}
                </div>
            </div>
            {/if}
            {if $related_terms}
            <div class="glossary-seealso__group">
                <span class="glossary-seealso__label">{$t.GUIDES_RELATED_TERMS}</span>
                <div class="glossary-related-links">
                    {foreach $related_terms as $rel}
                    <a class="glossary-related-link" href="{$rel.url}"><i class="bi bi-book"></i>{$rel.label}</a>
                    {/foreach}
                </div>
            </div>
            {/if}
        </div>
    </section>
    {/if}

    <div class="glossary-term__back">
        <a href="{$locale_prefix|default:''}/guides"><i class="bi bi-arrow-left"></i> {$t.GUIDES_BACK_TO_INDEX}</a>
    </div>
</article>
