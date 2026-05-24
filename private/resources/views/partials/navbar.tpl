<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{$locale_prefix|default:'/'}">
            <i class="bi bi-layers me-1"></i> Skeleton
        </a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                {foreach $nav_main as $item}
                    {if isset($item.children) && $item.children}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle{if $item.active} active{/if}" href="#" role="button"
                               data-bs-toggle="dropdown" aria-expanded="false">
                                {$item.label}
                            </a>
                            <ul class="dropdown-menu">
                                {foreach $item.children as $child}
                                    <li>
                                        <a class="dropdown-item{if $child.active} active{/if}" href="{$child.url}">
                                            {$child.label}
                                        </a>
                                    </li>
                                {/foreach}
                            </ul>
                        </li>
                    {else}
                        <li class="nav-item">
                            <a class="nav-link{if $item.active} active{/if}" href="{$item.url}">
                                {if $item.icon}<i class="bi {$item.icon} me-1"></i>{/if}
                                {$item.label}
                            </a>
                        </li>
                    {/if}
                {/foreach}
            </ul>
            <div class="d-flex align-items-center gap-2">
                {if $multilang && $auth_user === null && $available_locales|@count > 1}
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
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
                        <a href="{$admin_path}" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-shield-lock me-1"></i>{$t.MENU_ADMIN}
                        </a>
                    {/if}
                    <form method="POST" action="{$locale_prefix}/logout" class="m-0">
                        <input type="hidden" name="_csrf_token" value="{$csrf_token}">
                        <button type="submit" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i>{$t.AUTH_SIGN_OUT}
                        </button>
                    </form>
                {else}
                    <button type="button" class="btn btn-outline-light btn-sm"
                            data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-box-arrow-in-right me-1"></i>{$t.AUTH_SIGN_IN}
                    </button>
                {/if}
            </div>
        </div>
    </div>
</nav>
