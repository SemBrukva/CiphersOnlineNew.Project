<header class="site-header">
    <div class="container">
        <div class="d-flex align-items-center gap-3">

            {* Логотип — всегда виден *}
            <a class="site-brand flex-shrink-0" href="{$locale_prefix|default:'/'}">
                <i class="bi bi-shield-lock-fill site-brand__icon"></i>
                CiphersOnline
            </a>

            {* Десктопная навигация (≥lg) *}
            <nav class="d-none d-lg-flex align-items-center gap-1 flex-grow-1">
                {foreach $nav_main as $item}
                    {if isset($item.children) && $item.children}
                        <div class="dropdown">
                            <button class="site-header-link dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                {if $item.icon}<i class="bi {$item.icon} me-1"></i>{/if}
                                {$item.label}
                            </button>
                            <ul class="dropdown-menu">
                                {foreach $item.children as $child}
                                    <li>
                                        <a class="dropdown-item{if $child.active} active{/if}" href="{$child.url}">
                                            {$child.label}
                                        </a>
                                    </li>
                                {/foreach}
                            </ul>
                        </div>
                    {else}
                        <a class="site-header-link{if $item.active} active{/if}" href="{$item.url}">
                            {if $item.icon}<i class="bi {$item.icon} me-1"></i>{/if}
                            {$item.label}
                        </a>
                    {/if}
                {/foreach}
            </nav>

            {* Десктопная правая панель: языки + авторизация (≥lg) *}
            <div class="d-none d-lg-flex align-items-center gap-2 ms-auto">
                {if $multilang && $auth_user === null && $available_locales|@count > 1}
                    <div class="dropdown">
                        <button class="site-header-link dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            {$current_locale|upper}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            {foreach $available_locales as $lang}
                                <li>
                                    <a class="dropdown-item{if $lang === $current_locale} active{/if}"
                                       href="{$locale_urls[$lang]}">
                                        {$lang|upper}
                                    </a>
                                </li>
                            {/foreach}
                        </ul>
                    </div>
                {/if}

                {if $auth_user !== null}
                    {if $is_admin}
                        <a href="{$admin_path}" class="btn btn-sm site-header__btn-admin d-inline-flex align-items-center">
                            <i class="bi bi-shield-lock me-1"></i>{$t.MENU_ADMIN}
                        </a>
                    {/if}
                    <form method="POST" action="{$locale_prefix}/logout" class="m-0">
                        <input type="hidden" name="_csrf_token" value="{$csrf_token}">
                        <button type="submit" class="btn btn-sm site-header__btn d-inline-flex align-items-center">
                            <i class="bi bi-box-arrow-right me-1"></i>{$t.AUTH_SIGN_OUT}
                        </button>
                    </form>
                {else}
                    <button type="button" class="btn btn-sm site-header__btn d-inline-flex align-items-center"
                            data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-box-arrow-in-right me-1"></i>{$t.AUTH_SIGN_IN}
                    </button>
                {/if}
            </div>

            {* Мобильный бургер (<lg) *}
            <button class="site-hamburger d-flex d-lg-none ms-auto" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#siteNav"
                    aria-controls="siteNav" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

        </div>
    </div>
    {include file="partials/breadcrumbs.tpl"}
</header>

{* Offcanvas drawer — только для мобильного *}
<div class="offcanvas offcanvas-end site-nav" id="siteNav" tabindex="-1"
     aria-labelledby="siteNavLabel">
    <div class="offcanvas-header site-nav__header">
        <div class="site-nav__title" id="siteNavLabel">Navigation</div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body site-nav__body">
        {if $nav_main}
            <ul class="site-nav__list">
                {foreach $nav_main as $item}
                    {if isset($item.children) && $item.children}
                        <li class="site-nav__item">
                            <span class="site-nav__group-label">{$item.label}</span>
                            <ul class="site-nav__sub">
                                {foreach $item.children as $child}
                                    <li>
                                        <a class="site-nav__sublink{if $child.active} active{/if}" href="{$child.url}">
                                            {$child.label}
                                        </a>
                                    </li>
                                {/foreach}
                            </ul>
                        </li>
                    {else}
                        <li class="site-nav__item">
                            <a class="site-nav__link{if $item.active} active{/if}" href="{$item.url}">
                                {if $item.icon}<i class="bi {$item.icon} me-2"></i>{/if}
                                {$item.label}
                            </a>
                        </li>
                    {/if}
                {/foreach}
            </ul>
        {/if}

        <div class="px-3 mt-3 d-flex flex-column gap-2">
            {if $auth_user !== null}
                {if $is_admin}
                    <a href="{$admin_path}" class="btn btn-sm site-header__btn-admin d-flex align-items-center justify-content-center">
                        <i class="bi bi-shield-lock me-1"></i>{$t.MENU_ADMIN}
                    </a>
                {/if}
                <form method="POST" action="{$locale_prefix}/logout" class="m-0">
                    <input type="hidden" name="_csrf_token" value="{$csrf_token}">
                    <button type="submit" class="btn btn-sm site-header__btn w-100 d-flex align-items-center justify-content-center">
                        <i class="bi bi-box-arrow-right me-1"></i>{$t.AUTH_SIGN_OUT}
                    </button>
                </form>
            {else}
                <button type="button" class="btn btn-sm site-header__btn w-100 d-flex align-items-center justify-content-center"
                        data-bs-toggle="modal" data-bs-target="#loginModal"
                        data-bs-dismiss="offcanvas">
                    <i class="bi bi-box-arrow-in-right me-1"></i>{$t.AUTH_SIGN_IN}
                </button>
            {/if}
        </div>

        {if $multilang && $auth_user === null && $available_locales|@count > 1}
            <div class="site-nav__locales">
                {foreach $available_locales as $lang}
                    <a class="site-nav__locale{if $lang === $current_locale} active{/if}"
                       href="{$locale_urls[$lang]}">
                        {$lang|upper}
                    </a>
                {/foreach}
            </div>
        {/if}
    </div>
</div>
