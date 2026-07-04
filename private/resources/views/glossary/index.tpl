<section class="ciphers-category-hub-hero">
    <div class="ciphers-category-hub-hero__inner">
        <h1 class="ciphers-category-hub-hero__title">{$t.GLOSSARY_INDEX_HEADING}</h1>
        <p class="ciphers-category-hub-hero__desc">{$t.GLOSSARY_INDEX_INTRO}</p>
    </div>
</section>

{if $sections}
<div class="glossary-search-wrap mb-3">
    <input type="search" id="glossarySearch" class="form-control"
           placeholder="{$t.GLOSSARY_SEARCH_PLACEHOLDER}" autocomplete="off"
           aria-label="{$t.GLOSSARY_SEARCH_PLACEHOLDER}">
</div>

{foreach $sections as $section}
<section class="panel ciphers-category-hub-panel glossary-section" data-glossary-section>
    <div class="panel-heading">
        <div class="panel-title">{$section.label}</div>
    </div>
    <div class="panel-content">
        <div class="ciphers-category-hub-grid">
            {foreach $section.terms as $term}
            <article class="ciphers-category-hub-card glossary-term-card"
                     data-glossary-name="{$term.name|lower|escape}">
                <h2 class="ciphers-category-hub-card__title">
                    <a href="{$term.url}">{$term.name}</a>
                </h2>
                {if $term.short}
                <p class="ciphers-category-hub-card__desc">{$term.short}</p>
                {/if}
                <span class="ciphers-category-hub-card__arrow" aria-hidden="true">→</span>
            </article>
            {/foreach}
        </div>
    </div>
</section>
{/foreach}

<p class="glossary-empty text-muted d-none" id="glossaryEmpty">{$t.GLOSSARY_SEARCH_EMPTY}</p>

<script nonce="{$csp_nonce}">
(function () {
    var input = document.getElementById('glossarySearch');
    if (!input) return;
    var cards = Array.prototype.slice.call(document.querySelectorAll('[data-glossary-name]'));
    var sections = Array.prototype.slice.call(document.querySelectorAll('[data-glossary-section]'));
    var empty = document.getElementById('glossaryEmpty');
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        cards.forEach(function (card) {
            var match = q === '' || card.getAttribute('data-glossary-name').indexOf(q) !== -1;
            card.classList.toggle('d-none', !match);
        });
        var anyVisible = false;
        sections.forEach(function (section) {
            var visible = section.querySelectorAll('[data-glossary-name]:not(.d-none)').length > 0;
            section.classList.toggle('d-none', !visible);
            if (visible) anyVisible = true;
        });
        if (empty) empty.classList.toggle('d-none', anyVisible);
    });
})();
</script>
{/if}
