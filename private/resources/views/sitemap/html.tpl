<section class="ciphers-category-hub-hero">
    <div class="ciphers-category-hub-hero__inner">
        <h1 class="ciphers-category-hub-hero__title">
            <i class="bi bi-map me-2" style="color:var(--co-accent);font-size:0.85em;vertical-align:0.05em"></i>{$t.SITEMAP_TITLE}
        </h1>
        <p class="ciphers-category-hub-hero__desc">{$t.SITEMAP_DESC}</p>
    </div>
</section>

<section class="panel">
    <div class="panel-content">
        <div class="sitemap-grid">
            {foreach $categories as $category}
            <div class="sitemap-category">
                <div class="sitemap-category__header">
                    {if $category.category == 'encoding'}
                        <i class="bi bi-braces-asterisk sitemap-category__icon"></i>
                    {else}
                        <i class="bi bi-shield-lock sitemap-category__icon"></i>
                    {/if}
                    <div class="sitemap-category__name">
                        <a href="{$locale_prefix}/{$category.alias}">{$category.name}</a>
                    </div>
                    <span class="sitemap-category__count">{$category.tools|count}</span>
                </div>
                <div class="sitemap-category__tools">
                    {foreach $category.tools as $tool}
                    <a class="sitemap-tool-link" href="{$locale_prefix}/{$category.alias}/{$tool.alias}">{$tool.name_short}</a>
                    {/foreach}
                </div>
            </div>
            {/foreach}
        </div>
    </div>
</section>

<div class="text-center mb-4">
    <a href="/sitemap.xml" class="text-muted small">
        <i class="bi bi-filetype-xml me-1"></i>XML Sitemap
    </a>
</div>
