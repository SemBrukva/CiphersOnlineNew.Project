<!doctype html>
<html lang="{$current_locale}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title|default:'Application'}</title>
    {if $meta_description}<meta name="description" content="{$meta_description}">{/if}
    {vite entry="private/resources/js/app.js" type="css"}
</head>
<body class="d-flex flex-column min-vh-100">

{include file="partials/navbar.tpl"}

<main class="flex-grow-1 py-4">
    <div class="container">
        {if $breadcrumbs}
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    {foreach $breadcrumbs as $crumb}
                        {if $crumb@last}
                            <li class="breadcrumb-item active" aria-current="page">{$crumb.label}</li>
                        {else}
                            <li class="breadcrumb-item"><a href="{$crumb.url}">{$crumb.label}</a></li>
                        {/if}
                    {/foreach}
                </ol>
            </nav>
        {/if}
        {if $sidebar}
            <div class="row">
                <div class="col-md-8" id="contentRow">
                    {$content nofilter}
                </div>
                <div class="col-md-4" id="sidebarRow">
                    {$sidebar nofilter}
                </div>
            </div>
        {else}
            {$content nofilter}
        {/if}
    </div>
</main>

<footer class="bg-dark text-light py-3 mt-auto">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start small">
                &copy; {$current_year} Skeleton
            </div>
            <div class="col-md-6 text-center text-md-end">
                <nav class="d-inline-flex flex-wrap gap-3">
                    {foreach $nav_pages as $pg}
                        <a href="{$locale_prefix}/page/{$pg.alias}" class="text-light text-decoration-none small">
                            {$pg.name}
                        </a>
                    {/foreach}
                    <a href="{$locale_prefix}/sitemap" class="text-light text-decoration-none small">
                        {$t.MENU_SITEMAP}
                    </a>
                </nav>
            </div>
        </div>
    </div>
</footer>

{vite entry="private/resources/js/app.js" type="js"}

{if $auth_user === null}
<div class="modal fade" id="loginModal" tabindex="-1"
     aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="loginModalLabel">
                    <i class="bi bi-box-arrow-in-right me-2"></i>{$t.AUTH_SIGN_IN_TITLE}
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {include file="partials/login_form.tpl"
                    form_email_id='modalLoginEmail'
                    form_password_id='modalLoginPassword'
                }
            </div>
        </div>
    </div>
</div>
{/if}
</body>
</html>
