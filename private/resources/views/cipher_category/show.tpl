<section class="ciphers-category-hub-hero">
    <div class="ciphers-category-hub-hero__inner">
        <h1 class="ciphers-category-hub-hero__title">{$category.name}</h1>
        <p class="ciphers-category-hub-hero__desc">{$category.description|default:''}</p>
        {if $tools}
        <div class="ciphers-category-hub-hero__chips">
            {foreach $tools as $tool}
                <a class="ciphers-category-hub-hero__chip" href="/{$category.alias}/{$tool.alias}">{$tool.name_short}</a>
            {/foreach}
        </div>
        {/if}
    </div>
</section>

{if $tools}
<section class="panel ciphers-category-hub-panel" id="category-tools">
    <div class="panel-heading">
        <div class="panel-title">
            <i class="bi bi-tools"></i> {$category.name}
        </div>
    </div>
    <div class="panel-content">
        <div class="ciphers-category-hub-grid">
            {foreach $tools as $tool}
            <article class="ciphers-category-hub-card">
                <h2 class="ciphers-category-hub-card__title">
                    <a href="/{$category.alias}/{$tool.alias}">{$tool.name}</a>
                </h2>
                {if $tool.description}
                <p class="ciphers-category-hub-card__desc">{$tool.description_short}</p>
                {/if}
                {if $tool.tags}
                <div class="ciphers-category-hub-card__badges">
                    {foreach $tool.tags as $tag}
                    <span class="ciphers-category-hub-badge">{$tag}</span>
                    {/foreach}
                </div>
                {/if}
                <span class="ciphers-category-hub-card__arrow" aria-hidden="true">→</span>
            </article>
            {/foreach}
        </div>
    </div>
</section>
{/if}

{if $blocks}
    {foreach $blocks as $block}
    <section class="panel">
        <div class="panel-heading">
            <div class="panel-title">{$block.title|default:$category.name}</div>
        </div>
        <div class="panel-content">
            {$block.text nofilter}
        </div>
    </section>
    {/foreach}
{/if}
