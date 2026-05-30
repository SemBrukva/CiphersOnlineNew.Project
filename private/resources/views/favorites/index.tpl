<section class="ciphers-category-hub-hero">
    <div class="ciphers-category-hub-hero__inner">
        <h1 class="ciphers-category-hub-hero__title">
            <i class="bi bi-star-fill me-2" style="color:var(--co-accent);font-size:0.85em;vertical-align:0.05em"></i>{$t.FAVORITES_PAGE_HEADING}
        </h1>
        <p class="ciphers-category-hub-hero__desc">{$t.FAVORITES_PAGE_DESC}</p>
    </div>
</section>

<section class="panel">
    <div class="panel-content" id="favorites-root">

        {* Скелет загрузки *}
        <div class="favorites-skeleton" id="favorites-skeleton">
            {for $i=1 to 6}
            <div class="favorites-skeleton__card"></div>
            {/for}
        </div>

        {* Пустое состояние *}
        <div class="favorites-empty" id="favorites-empty" style="display:none">
            <i class="bi bi-star favorites-empty__icon"></i>
            <p class="favorites-empty__text">{$t.FAVORITES_EMPTY_TEXT}</p>
            <a href="{$locale_prefix|default:'/'}" class="site-header__btn btn btn-sm">
                {$t.FAVORITES_BROWSE_BTN}
            </a>
        </div>

        {* Сетка карточек — заполняется через JS *}
        <div class="ciphers-category-hub-grid" id="favorites-grid" style="display:none"></div>

    </div>
</section>
