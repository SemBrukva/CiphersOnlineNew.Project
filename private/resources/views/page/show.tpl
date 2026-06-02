<section class="ciphers-category-hub-hero">
    <div class="ciphers-category-hub-hero__inner">
        <h1 class="ciphers-category-hub-hero__title">
            {if $page.alias == 'cookie-policy'}
                <i class="bi bi-cookie me-2" style="color:var(--co-accent);font-size:0.85em;vertical-align:0.05em"></i>
            {elseif $page.alias == 'privacy-policy'}
                <i class="bi bi-shield-lock me-2" style="color:var(--co-accent);font-size:0.85em;vertical-align:0.05em"></i>
            {elseif $page.alias == 'terms' || $page.alias == 'terms-of-service'}
                <i class="bi bi-file-earmark-check me-2" style="color:var(--co-accent);font-size:0.85em;vertical-align:0.05em"></i>
            {else}
                <i class="bi bi-file-earmark-text me-2" style="color:var(--co-accent);font-size:0.85em;vertical-align:0.05em"></i>
            {/if}
            {$page.name}
        </h1>
    </div>
</section>

<section class="panel">
    <div class="panel-content system-page-content">
        {$page.text nofilter}
    </div>
</section>
