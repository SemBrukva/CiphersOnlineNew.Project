{assign var="_ad_slot_id" value=$tracking_config.ad_slots[$position]|default:''}
{if $_ad_slot_id !== ''}
<div class="ad-block">
    {if $tracking_config.ad_network === 'rsya'}
        {assign var="_ad_rsya_div" value="yandex_rtb_`$_ad_slot_id`"}
        <div id="{$_ad_rsya_div}"></div>
        <script nonce="{$csp_nonce}">
        window.yaContextCb = window.yaContextCb || [];
        window.yaContextCb.push(function() {
            Ya.Context.AdvManager.render({
                blockId: "{$_ad_slot_id|escape:'javascript'}",
                renderTo: "{$_ad_rsya_div|escape:'javascript'}"
            });
        });
        </script>
    {else}
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="{$tracking_config.adsense_client_id|escape:'html'}"
             data-ad-slot="{$_ad_slot_id|escape:'html'}"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
        <script nonce="{$csp_nonce}">(adsbygoogle = window.adsbygoogle || []).push({});</script>
    {/if}
</div>
{/if}
