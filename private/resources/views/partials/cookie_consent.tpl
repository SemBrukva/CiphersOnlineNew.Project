<section class="cookie-consent" data-cookie-consent hidden
         data-version="1"
         data-policy-url="{$locale_prefix}/cookie-policy"
         data-title="{$t.COOKIE_CONSENT_TITLE|escape:'html'}"
         data-description="{$t.COOKIE_CONSENT_DESC|escape:'html'}"
         data-policy-label="{$t.COOKIE_CONSENT_POLICY|escape:'html'}">
    <div class="cookie-consent__panel" role="dialog" aria-modal="false" aria-labelledby="cookieConsentTitle">
        <div class="cookie-consent__content">
            <p class="cookie-consent__eyebrow">{$t.COOKIE_CONSENT_EYEBROW}</p>
            <h2 class="cookie-consent__title" id="cookieConsentTitle">{$t.COOKIE_CONSENT_TITLE}</h2>
            <p class="cookie-consent__text">
                {$t.COOKIE_CONSENT_DESC}
                <a href="{$locale_prefix}/cookie-policy">{$t.COOKIE_CONSENT_POLICY}</a>.
            </p>
        </div>
        <div class="cookie-consent__actions" data-cookie-consent-actions>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-cookie-reject>
                {$t.COOKIE_CONSENT_REJECT}
            </button>
            <button type="button" class="btn btn-sm btn-outline-dark" data-cookie-settings>
                {$t.COOKIE_CONSENT_SETTINGS}
            </button>
            <button type="button" class="btn btn-sm btn-primary" data-cookie-accept>
                {$t.COOKIE_CONSENT_ACCEPT}
            </button>
        </div>
        <form class="cookie-consent__settings" data-cookie-consent-settings hidden>
            <div class="cookie-consent__options">
                <label class="cookie-consent__option cookie-consent__option--locked">
                    <input type="checkbox" checked disabled>
                    <span>
                        <strong>{$t.COOKIE_CONSENT_NECESSARY_TITLE}</strong>
                        <small>{$t.COOKIE_CONSENT_NECESSARY_DESC}</small>
                    </span>
                </label>
                <label class="cookie-consent__option">
                    <input type="checkbox" name="preferences" data-cookie-category="preferences">
                    <span>
                        <strong>{$t.COOKIE_CONSENT_PREFERENCES_TITLE}</strong>
                        <small>{$t.COOKIE_CONSENT_PREFERENCES_DESC}</small>
                    </span>
                </label>
                <label class="cookie-consent__option">
                    <input type="checkbox" name="analytics" data-cookie-category="analytics">
                    <span>
                        <strong>{$t.COOKIE_CONSENT_ANALYTICS_TITLE}</strong>
                        <small>{$t.COOKIE_CONSENT_ANALYTICS_DESC}</small>
                    </span>
                </label>
                <label class="cookie-consent__option">
                    <input type="checkbox" name="marketing" data-cookie-category="marketing">
                    <span>
                        <strong>{$t.COOKIE_CONSENT_MARKETING_TITLE}</strong>
                        <small>{$t.COOKIE_CONSENT_MARKETING_DESC}</small>
                    </span>
                </label>
            </div>
            <div class="cookie-consent__actions cookie-consent__actions--settings">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-cookie-back>
                    {$t.COOKIE_CONSENT_BACK}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-cookie-reject>
                    {$t.COOKIE_CONSENT_REJECT}
                </button>
                <button type="submit" class="btn btn-sm btn-primary">
                    {$t.COOKIE_CONSENT_SAVE}
                </button>
            </div>
        </form>
    </div>
</section>
