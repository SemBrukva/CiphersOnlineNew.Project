<!doctype html>
<html lang="{$current_locale}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title|default:'Application'}</title>
    {if $meta_description}<meta name="description" content="{$meta_description}">{/if}
    {if $meta_robots}<meta name="robots" content="{$meta_robots}">{/if}
    {assign var="canonical_path" value=$locale_urls[$current_locale]|default:($locale_prefix|cat:$current_path)}
    {assign var="canonical_url" value=$app_url|cat:$canonical_path}
    <link rel="canonical" href="{$canonical_url}">
    {if $multilang && $locale_urls}
        {foreach $locale_urls as $lang => $url}
            <link rel="alternate" hreflang="{$lang}" href="{$app_url}{$url}">
        {/foreach}
        {assign var="x_default_url" value=$locale_urls[$default_locale]|default:$canonical_path}
        <link rel="alternate" hreflang="x-default" href="{$app_url}{$x_default_url}">
    {/if}
    {assign var="og_locale_code" value=$locale_meta[$current_locale]['og_locale']|default:$current_locale}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{$canonical_url}">
    <meta property="og:site_name" content="{$app_name}">
    <meta property="og:locale" content="{$og_locale_code}">
    {if $multilang && $available_locales}
        {foreach $available_locales as $alt_locale}
            {if $alt_locale neq $current_locale}
                {assign var="alt_og_locale" value=$locale_meta[$alt_locale]['og_locale']|default:$alt_locale}
                <meta property="og:locale:alternate" content="{$alt_og_locale}">
            {/if}
        {/foreach}
    {/if}
    <meta property="og:title" content="{$title|default:$app_name}">
    {if $meta_description}<meta property="og:description" content="{$meta_description}">{/if}
    {assign var="og_image_url" value=$og_image|default:'/og-image.jpg'}
    {if $og_image_url|substr:0:4 == 'http'}
        <meta property="og:image" content="{$og_image_url}">
    {else}
        <meta property="og:image" content="{$app_url}{$og_image_url}">
    {/if}
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="manifest" href="/site.webmanifest" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {vite entry="private/resources/js/app.js" type="preload"}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    {vite entry="private/resources/js/app.js" type="css"}
</head>
<body class="d-flex flex-column min-vh-100" style="font-family:'Inter',system-ui,sans-serif;">

{include file="partials/navbar.tpl"}

<main class="flex-grow-1 py-4">
    <div class="container">
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

<footer class="site-footer mt-auto">
    <div class="container">
        <p>&copy; {$current_year} CiphersOnline &mdash; {$t.FOOTER_DESC}.</p>
        <ul class="footer-nav">
            <li><a href="{$locale_prefix}/contacts">{$t.MENU_CONTACTS|default:'Contacts'}</a></li>
            {foreach $nav_pages as $pg}
                <li><a href="{$locale_prefix}/{$pg.alias}">{$pg.name}</a></li>
            {/foreach}
            <li>
                <button type="button" class="footer-link-button" data-cookie-settings-open>
                    {$t.COOKIE_CONSENT_SETTINGS_LINK|default:'Cookie settings'}
                </button>
            </li>
            <li><a href="{$locale_prefix}/sitemap">{$t.MENU_SITEMAP}</a></li>
        </ul>
    </div>
</footer>

{include file="partials/cookie_consent.tpl"}
{include file="partials/tracking_config.tpl"}

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
