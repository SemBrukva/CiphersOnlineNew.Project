<div class="row justify-content-center">
    <div class="col-md-8">
        <h1 class="mb-4">{$t.SITEMAP_TITLE}</h1>

        <section class="mb-4">
            <h2 class="h5 text-muted mb-3">{$t.SITEMAP_MAIN_PAGES}</h2>
            <ul class="list-unstyled">
                <li class="mb-1">
                    <a href="{$locale_prefix|default:'/'}">{$t.MENU_HOME}</a>
                </li>
                <li class="mb-1">
                    <a href="{$locale_prefix}/contacts">{$t.MENU_CONTACTS}</a>
                </li>
            </ul>
        </section>

        {if $pages}
            <section class="mb-4">
                <h2 class="h5 text-muted mb-3">{$t.SITEMAP_PAGES}</h2>
                <ul class="list-unstyled">
                    {foreach $pages as $page}
                        <li class="mb-1">
                            <a href="{$locale_prefix}/page/{$page.alias}">{$page.name}</a>
                        </li>
                    {/foreach}
                </ul>
            </section>
        {/if}

        <p class="mt-4">
            <a href="/sitemap.xml" class="text-muted small">
                <i class="bi bi-filetype-xml me-1"></i>XML Sitemap
            </a>
        </p>
    </div>
</div>
