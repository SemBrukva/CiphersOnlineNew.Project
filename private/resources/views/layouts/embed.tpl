<!doctype html>
<html lang="{$current_locale}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title|default:'CiphersOnline'}</title>
    {* Встраиваемый виджет никогда не индексируется — он лишь дубль основной страницы. *}
    <meta name="robots" content="{$meta_robots|default:'noindex, nofollow'}">
    {if $meta_description}<meta name="description" content="{$meta_description}">{/if}
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    {vite entry="private/resources/js/app.js" type="preload"}
    {vite entry="private/resources/js/app.js" type="css"}
</head>
<body class="ciphers-embed-body" style="font-family:'Inter Variable','Inter',system-ui,sans-serif;">

<main class="ciphers-embed-main">
    {$content nofilter}

    <div class="ciphers-embed-brand">
        <a href="{$embed_tool_url|default:$app_url}" target="_blank" rel="noopener">
            {$embed_tool_name|default:'CiphersOnline'} — {$app_name|default:'CiphersOnline'}
        </a>
    </div>
</main>

{vite entry="private/resources/js/app.js" type="js"}
</body>
</html>
