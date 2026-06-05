<div id="trackingConfig" hidden
     data-ga-measurement-id="{$tracking_config.ga_measurement_id|default:''|escape:'html'}"
     data-adsense-client-id="{$tracking_config.adsense_client_id|default:''|escape:'html'}"
     data-yandex-metrica-id="{$tracking_config.yandex_metrica_id|default:''|escape:'html'}"
     data-yandex-metrica-webvisor="{if $tracking_config.yandex_metrica_webvisor|default:false}1{else}0{/if}"
     data-yandex-rsya-enabled="{if $tracking_config.yandex_rsya_enabled|default:false}1{else}0{/if}"></div>
