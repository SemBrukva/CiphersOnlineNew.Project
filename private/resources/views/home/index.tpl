{* === HERO === *}
<section class="home-hero">
    <div class="home-hero__inner">
        <h1 class="home-hero__title">{$t.HOME_HERO_TITLE}</h1>
        <p class="home-hero__subtitle">{$t.HOME_HERO_SUBTITLE}</p>

        <form class="home-hero__search" role="search" autocomplete="off" onsubmit="return false;">
            <i class="bi bi-search home-hero__search-icon" aria-hidden="true"></i>
            <input type="search"
                   class="home-hero__search-input"
                   id="homeToolSearch"
                   placeholder="{$t.HOME_SEARCH_PLACEHOLDER}"
                   aria-label="{$t.HOME_SEARCH_PLACEHOLDER}">
            <span class="home-hero__search-kbd">/</span>
        </form>

        {if $quick_access_tools}
        <div class="home-hero__quick">
            <span class="home-hero__quick-label">{$t.HOME_QUICK_ACCESS}</span>
            <div class="home-hero__chips" id="homeQuickChips">
                {foreach $quick_access_tools as $tool}
                    <a class="home-hero__chip"
                       data-search-name="{$tool.name_short|escape:'html'} {$tool.alias}"
                       href="{$locale_prefix}/{$tool.category_alias}/{$tool.alias}">
                        {$tool.name_short}
                    </a>
                {/foreach}
            </div>
        </div>
        {/if}
    </div>
</section>

{* === MAIN CATEGORIES === *}
<section class="home-section">
    <div class="home-section__head">
        <h2 class="home-section__title">{$t.HOME_CATEGORIES_TITLE}</h2>
        <p class="home-section__lead">{$t.HOME_CATEGORIES_LEAD}</p>
    </div>

    <div class="home-categories">
        {foreach $categories_with_tools as $cat}
        <article class="home-category-card">
            <header class="home-category-card__head">
                <div class="home-category-card__icon" aria-hidden="true">
                    {if $cat.category === 'cipher'}
                        <i class="bi bi-shield-lock"></i>
                    {else}
                        <i class="bi bi-braces-asterisk"></i>
                    {/if}
                </div>
                <div>
                    <h3 class="home-category-card__title">
                        <a href="{$locale_prefix}/{$cat.alias}">{$cat.name}</a>
                    </h3>
                    {if $cat.tools_count}
                        <span class="home-category-card__count">
                            {trans_choice key='HOME_TOOLS_COUNT' count=$cat.tools_count}
                        </span>
                    {/if}
                </div>
            </header>

            <p class="home-category-card__desc">{$cat.description}</p>

            {if $cat.tools}
            <ul class="home-category-card__tools">
                {foreach $cat.tools as $tool}
                    <li>
                        <a href="{$locale_prefix}/{$cat.alias}/{$tool.alias}">
                            <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                            <span>{$tool.name_short}</span>
                        </a>
                    </li>
                {/foreach}
            </ul>
            {/if}

            <a class="home-category-card__cta" href="{$locale_prefix}/{$cat.alias}">
                {$t.HOME_OPEN_CATEGORY}
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </article>
        {/foreach}

        {foreach $planned_categories as $planned}
        <article class="home-category-card home-category-card--planned">
            <header class="home-category-card__head">
                <div class="home-category-card__icon home-category-card__icon--muted" aria-hidden="true">
                    <i class="bi {$planned.icon}"></i>
                </div>
                <div>
                    <h3 class="home-category-card__title">
                        <span>{$planned.name}</span>
                    </h3>
                    <span class="home-category-card__badge">{$t.HOME_COMING_SOON}</span>
                </div>
            </header>

            <p class="home-category-card__desc">{$planned.description}</p>
        </article>
        {/foreach}
    </div>
</section>

{* === POPULAR TOOLS === *}
{if $popular_tools}
<section class="home-section">
    <div class="home-section__head">
        <h2 class="home-section__title">{$t.HOME_POPULAR_TOOLS_TITLE}</h2>
        <p class="home-section__lead">{$t.HOME_POPULAR_TOOLS_LEAD}</p>
    </div>

    <div class="home-tools-grid">
        {foreach $popular_tools as $tool}
        <a class="home-tool-card" href="{$locale_prefix}/{$tool.category_alias}/{$tool.alias}">
            <div class="home-tool-card__top">
                <h3 class="home-tool-card__title">{$tool.name_short}</h3>
                <span class="home-tool-card__arrow" aria-hidden="true">→</span>
            </div>
            {if $tool.description_short}
                <p class="home-tool-card__desc">{$tool.description_short}</p>
            {/if}
            {if $tool.tags}
            <div class="home-tool-card__badges">
                {foreach $tool.tags as $tag}
                    <span class="home-badge">{$tag}</span>
                {/foreach}
            </div>
            {/if}
        </a>
        {/foreach}
    </div>
</section>
{/if}

{* === POPULAR TASKS / USE CASES === *}
{if $use_cases}
<section class="home-section">
    <div class="home-section__head">
        <h2 class="home-section__title">{$t.HOME_USE_CASES_TITLE}</h2>
        <p class="home-section__lead">{$t.HOME_USE_CASES_LEAD}</p>
    </div>

    <div class="home-usecase-grid">
        {foreach $use_cases as $uc}
        <a class="home-usecase-item" href="{$uc.url}">
            <span class="home-usecase-item__icon">
                <i class="bi bi-lightning-charge-fill" aria-hidden="true"></i>
            </span>
            <span class="home-usecase-item__body">
                <strong class="home-usecase-item__title">{$uc.title}</strong>
                <span class="home-usecase-item__desc">{$uc.description}</span>
            </span>
            <span class="home-usecase-item__tool">{$uc.tool_label}</span>
        </a>
        {/foreach}
    </div>
</section>
{/if}

{* === EDUCATIONAL === *}
<section class="home-section">
    <div class="home-section__head">
        <h2 class="home-section__title">{$t.HOME_EDU_TITLE}</h2>
        <p class="home-section__lead">{$t.HOME_EDU_LEAD}</p>
    </div>

    <div class="home-edu-grid">
        <article class="home-edu-card">
            <span class="home-edu-card__num">01</span>
            <h3 class="home-edu-card__title">{$t.HOME_EDU_CARD_1_TITLE}</h3>
            <p class="home-edu-card__text">{$t.HOME_EDU_CARD_1_TEXT}</p>
        </article>
        <article class="home-edu-card">
            <span class="home-edu-card__num">02</span>
            <h3 class="home-edu-card__title">{$t.HOME_EDU_CARD_2_TITLE}</h3>
            <p class="home-edu-card__text">{$t.HOME_EDU_CARD_2_TEXT}</p>
        </article>
        <article class="home-edu-card">
            <span class="home-edu-card__num">03</span>
            <h3 class="home-edu-card__title">{$t.HOME_EDU_CARD_3_TITLE}</h3>
            <p class="home-edu-card__text">{$t.HOME_EDU_CARD_3_TEXT}</p>
        </article>
    </div>
</section>

{* === GROWING COLLECTION === *}
{if $recent_tools}
<section class="home-section">
    <div class="home-section__head">
        <h2 class="home-section__title">{$t.HOME_GROWING_TITLE}</h2>
        <p class="home-section__lead">{$t.HOME_GROWING_LEAD}</p>
    </div>

    <div class="home-tools-grid">
        {foreach $recent_tools as $tool}
        <a class="home-tool-card home-tool-card--new" href="{$locale_prefix}/{$tool.category_alias}/{$tool.alias}">
            <div class="home-tool-card__top">
                <h3 class="home-tool-card__title">{$tool.name_short}</h3>
                <span class="home-tool-card__flag">{$t.HOME_BADGE_NEW}</span>
            </div>
            {if $tool.description_short}
                <p class="home-tool-card__desc">{$tool.description_short}</p>
            {/if}
        </a>
        {/foreach}
    </div>
</section>
{/if}

{* === FOOTER NAVIGATION === *}
{if $categories_with_tools}
<section class="home-footnav">
    <div class="home-footnav__inner">
        {foreach $categories_with_tools as $cat}
        <div class="home-footnav__col">
            <h4 class="home-footnav__title">
                <a href="{$locale_prefix}/{$cat.alias}">{$cat.name}</a>
            </h4>
            {if $cat.tools}
            <ul class="home-footnav__list">
                {foreach $cat.tools as $tool}
                    <li><a href="{$locale_prefix}/{$cat.alias}/{$tool.alias}">{$tool.name_short}</a></li>
                {/foreach}
            </ul>
            {/if}
        </div>
        {/foreach}

        {if $planned_categories}
        <div class="home-footnav__col home-footnav__col--planned">
            <h4 class="home-footnav__title">{$t.HOME_COMING_SOON}</h4>
            <ul class="home-footnav__list">
                {foreach $planned_categories as $planned}
                    <li><span>{$planned.name}</span></li>
                {/foreach}
            </ul>
        </div>
        {/if}
    </div>
</section>
{/if}

<script nonce="{$csp_nonce}">
(function () {
    var input = document.getElementById('homeToolSearch');
    var chips = document.getElementById('homeQuickChips');
    if (!input || !chips) return;

    function normalize(value) {
        return (value || '').toString().toLowerCase().trim();
    }

    input.addEventListener('input', function () {
        var query = normalize(input.value);
        var items = chips.querySelectorAll('.home-hero__chip');
        items.forEach(function (el) {
            var name = normalize(el.getAttribute('data-search-name'));
            el.style.display = (query === '' || name.indexOf(query) !== -1) ? '' : 'none';
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) return;
        var tag = (event.target && event.target.tagName) || '';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || (event.target && event.target.isContentEditable)) return;
        event.preventDefault();
        input.focus();
    });
})();
</script>
