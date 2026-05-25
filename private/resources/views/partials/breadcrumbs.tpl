<div class="site-header__breadcrumbs">
    <div class="container">
        <div class="breadcrumbs" itemscope itemtype="https://schema.org/BreadcrumbList">
            {if $breadcrumbs}
                <div itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="{$locale_prefix|default:'/'}" class="pathway" itemprop="item">
                        <span itemprop="name">{$t.BREADCRUMB_HOME}</span>
                    </a>
                    <meta itemprop="position" content="1" />
                    <i class="bi bi-chevron-right breadcrumbs__sep"></i>
                </div>
                {foreach $breadcrumbs as $crumb}
                    <div itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        {if $crumb@last}
                            <span itemprop="name">{$crumb.label}</span>
                            <meta itemprop="position" content="{$crumb@iteration+1}" />
                        {else}
                            <a href="{$crumb.url}" class="pathway" itemprop="item">
                                <span itemprop="name">{$crumb.label}</span>
                            </a>
                            <meta itemprop="position" content="{$crumb@iteration+1}" />
                            <i class="bi bi-chevron-right breadcrumbs__sep"></i>
                        {/if}
                    </div>
                {/foreach}
            {else}
                <div itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <span itemprop="name">{$t.BREADCRUMB_HOME_FULL}</span>
                    <meta itemprop="position" content="1" />
                </div>
            {/if}
        </div>
    </div>
</div>
