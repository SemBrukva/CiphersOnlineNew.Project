# Cookie Consent and Tracking Integration

This document describes how CiphersOnline maps cookie consent categories to local storage, Google Analytics, Google AdSense, Yandex Metrica, and Yandex Advertising Network (RSYA).

It is an implementation note, not legal advice. The public Cookie Policy and Privacy Policy must stay aligned with the actual tags enabled in production.

## Consent Categories

| Category | User-facing meaning | Technical behavior when granted | Technical behavior when denied |
| --- | --- | --- | --- |
| Necessary | Required for the site to work safely. | Session, CSRF, security and core runtime features remain enabled. Google `security_storage` is always `granted`. | Cannot be disabled. Without it authentication, CSRF protection and core security features may break. |
| Preferences | Saves user interface choices. | The site may use local storage for `cipher_favorites` and `cipher-tool:state:*`. Google `functionality_storage` and `personalization_storage` are `granted`. | Preference local storage is not read or written. When denied, known preference keys are removed. Google functionality and personalization storage are `denied`. |
| Analytics | Measures site usage. | Google Analytics and Yandex Metrica may load. Google `analytics_storage` is `granted`. Yandex Metrica counter may initialize. | Google Analytics is not configured by this module and `analytics_storage` is `denied`. Yandex Metrica is disabled through `window.disableYaCounter<ID> = true` before initialization. |
| Marketing | Advertising and ad measurement. | Google AdSense and Yandex RSYA may load. Google `ad_storage`, `ad_user_data`, and `ad_personalization` are `granted`. | Advertising scripts are not loaded by this module. Google ad-related consent flags are `denied`. |

## Google Consent Mode Mapping

The `private/resources/js/pages/tracking-consent.js` module maps CiphersOnline consent to Google Consent Mode v2 as follows:

```js
analytics_storage: analytics ? 'granted' : 'denied'
ad_storage: marketing ? 'granted' : 'denied'
ad_user_data: marketing ? 'granted' : 'denied'
ad_personalization: marketing ? 'granted' : 'denied'
functionality_storage: preferences ? 'granted' : 'denied'
personalization_storage: preferences ? 'granted' : 'denied'
security_storage: 'granted'
```

The default state is deny-by-default for all optional categories. This means external analytics and advertising tags should not assume consent before the CMP has a stored choice or the user makes a new choice.

## Provider Behavior

### Google Analytics

Configured by `TRACKING_GA_MEASUREMENT_ID`.

Google Analytics is loaded only when the Analytics category is granted. The module loads `https://www.googletagmanager.com/gtag/js?id=<ID>` and then runs:

```js
gtag('config', '<ID>', {
  anonymize_ip: true,
  send_page_view: true
})
```

If Analytics is denied, `analytics_storage` remains `denied`.

### Google AdSense

Configured by `TRACKING_ADSENSE_CLIENT_ID`.

AdSense is loaded only when the Marketing category is granted. The module loads:

```text
https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<CLIENT_ID>
```

For EEA, UK and Switzerland traffic, Google requires publishers using AdSense to use a Google-certified CMP integrated with IAB TCF. The in-house CiphersOnline CMP is useful as the project-level consent source, but AdSense production rollout in those regions may require replacing or extending it with a certified TCF CMP.

### Yandex Metrica

Configured by `TRACKING_YANDEX_METRICA_ID`.

Yandex Metrica is loaded only when Analytics is granted. Before initialization, denial is expressed as:

```js
window['disableYaCounter' + id] = true
```

When Analytics is granted, the module sets that flag to `false`, loads `https://mc.yandex.ru/metrika/tag.js`, and initializes the counter.

`TRACKING_YANDEX_METRICA_WEBVISOR` defaults to `false`. Do not enable Webvisor unless the privacy documents and masking rules are reviewed, because session recording can capture more sensitive interaction data than ordinary page analytics.

### Yandex Advertising Network (RSYA)

Configured by `TRACKING_YANDEX_RSYA_ENABLED=true`.

RSYA is loaded only when Marketing is granted. The module initializes the async callback queue and loads:

```text
https://yandex.ru/ads/system/context.js
```

Ad block rendering code should be registered through `window.yaContextCb` only after the `ciphersonline:tracking-ready` event for `yandex-rsya`, or should check Marketing consent before pushing render callbacks.

## Runtime Events

The CMP emits:

```text
ciphersonline:cookie-consent
```

with the full consent object.

The tracking module emits:

```text
ciphersonline:tracking-consent-applied
```

after it maps the current CMP choice to provider-specific consent states.

It also emits:

```text
ciphersonline:tracking-ready
```

with `detail.provider` set to one of:

```text
google-tag
google-analytics
google-adsense
yandex-metrica
yandex-rsya
```

Use these events for future ad slot code and optional tracking integrations.

## Production Environment Variables

Set only the services that are actually used:

```dotenv
TRACKING_GA_MEASUREMENT_ID=G-XXXXXXXXXX
TRACKING_ADSENSE_CLIENT_ID=ca-pub-0000000000000000
TRACKING_YANDEX_METRICA_ID=00000000
TRACKING_YANDEX_METRICA_WEBVISOR=false
TRACKING_YANDEX_RSYA_ENABLED=false
```

If an ID is empty, the corresponding script is never loaded.

## Consent Changes After Scripts Loaded

Granting consent can load a script. Later denial updates Google Consent Mode to `denied` and disables Yandex Metrica collection through `disableYaCounter<ID>`.

However, browsers and third-party scripts cannot always be fully unloaded from a running page. If the user revokes consent after a tag has already loaded, the strictest operational behavior is to apply the new consent state immediately and avoid new optional calls; a full page reload can be considered if the product needs stronger guarantees.

## Implementation Files

- CMP UI: `private/resources/views/partials/cookie_consent.tpl`
- CMP logic: `private/resources/js/pages/cookie-consent.js`
- Tracking config view: `private/resources/views/partials/tracking_config.tpl`
- Tracking loader: `private/resources/js/pages/tracking-consent.js`
- Tracking config: `private/config/tracking.php`
- Shared view data: `private/app/Http/Middleware/ShareViewDataMiddleware.php`
