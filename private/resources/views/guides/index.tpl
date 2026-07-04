<section class="ciphers-category-hub-hero">
    <div class="ciphers-category-hub-hero__inner">
        <h1 class="ciphers-category-hub-hero__title">{$t.GUIDES_INDEX_HEADING}</h1>
        <p class="ciphers-category-hub-hero__desc">{$t.GUIDES_INDEX_INTRO}</p>
    </div>
</section>

{if $sections}
<div class="glossary-search-wrap mb-3">
    <input type="search" id="guidesSearch" class="form-control"
           placeholder="{$t.GUIDES_SEARCH_PLACEHOLDER}" autocomplete="off"
           aria-label="{$t.GUIDES_SEARCH_PLACEHOLDER}">
</div>

{foreach $sections as $section}
<section class="panel ciphers-category-hub-panel guides-section" data-guides-section>
    <div class="panel-heading">
        <div class="panel-title">{$section.label}</div>
    </div>
    <div class="panel-content">
        <div class="ciphers-category-hub-grid">
            {foreach $section.guides as $guide}
            <article class="ciphers-category-hub-card guide-card"
                     data-guide-title="{$guide.title|lower|escape}">
                <span class="guide-card__badge">{$section.label}</span>
                <h2 class="ciphers-category-hub-card__title">
                    <a href="{$guide.url}">{$guide.title}</a>
                </h2>
                {if $guide.excerpt}
                <p class="ciphers-category-hub-card__desc">{$guide.excerpt}</p>
                {/if}
                {if $guide.reading_time}
                <span class="guide-card__meta"><i class="bi bi-clock"></i> {$guide.reading_time} {$t.GUIDES_READING_MIN}</span>
                {/if}
                <span class="ciphers-category-hub-card__arrow" aria-hidden="true">→</span>
            </article>
            {/foreach}
        </div>
    </div>
</section>
{/foreach}

<p class="glossary-empty text-muted d-none" id="guidesEmpty">{$t.GUIDES_SEARCH_EMPTY}</p>

<script nonce="{$csp_nonce}">
(function () {
    var input = document.getElementById('guidesSearch');
    if (!input) return;
    var cards = Array.prototype.slice.call(document.querySelectorAll('[data-guide-title]'));
    var sections = Array.prototype.slice.call(document.querySelectorAll('[data-guides-section]'));
    var empty = document.getElementById('guidesEmpty');
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        cards.forEach(function (card) {
            var match = q === '' || card.getAttribute('data-guide-title').indexOf(q) !== -1;
            card.classList.toggle('d-none', !match);
        });
        var anyVisible = false;
        sections.forEach(function (section) {
            var visible = section.querySelectorAll('[data-guide-title]:not(.d-none)').length > 0;
            section.classList.toggle('d-none', !visible);
            if (visible) anyVisible = true;
        });
        if (empty) empty.classList.toggle('d-none', anyVisible);
    });
})();
</script>
{/if}
