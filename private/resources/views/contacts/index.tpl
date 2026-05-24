<div class="contact-page"
     id="contactPage"
     data-sending="{$t.CONTACT_SENDING|escape:'html'}"
     data-success="{$t.CONTACT_SUCCESS|escape:'html'}"
     data-failed="{$t.CONTACT_FAILED|escape:'html'}"
     data-error-name="{$t.CONTACT_ERROR_NAME|escape:'html'}"
     data-error-email="{$t.CONTACT_ERROR_EMAIL|escape:'html'}"
     data-error-message="{$t.CONTACT_ERROR_MESSAGE|escape:'html'}"
     data-error-message-max="{$t.CONTACT_ERROR_MESSAGE_MAX|escape:'html'}">
    <div class="row g-0 contact-layout">
        <div class="col-lg-5 col-xl-4">
            <div class="contact-info-panel">
                <div class="contact-info-panel__orb contact-info-panel__orb--1"></div>
                <div class="contact-info-panel__orb contact-info-panel__orb--2"></div>
                <div class="contact-info-panel__orb contact-info-panel__orb--3"></div>
                <div class="contact-info-panel__content">
                    <div class="contact-info-panel__brand"><i class="bi bi-envelope-heart-fill me-2"></i>{$app_name|escape:'html'}</div>
                    <p class="contact-info-panel__desc">{$t.CONTACT_DESCRIPTION}</p>
                    <div class="contact-info-panel__divider"></div>
                    <ul class="contact-info-panel__features">
                        <li>
                            <span class="contact-info-panel__feature-icon"><i class="bi bi-lightning-charge-fill"></i></span>
                            <span>{$t.CONTACT_FEATURE_FAST}</span>
                        </li>
                        <li>
                            <span class="contact-info-panel__feature-icon"><i class="bi bi-shield-check"></i></span>
                            <span>{$t.CONTACT_FEATURE_SECURE}</span>
                        </li>
                        <li>
                            <span class="contact-info-panel__feature-icon"><i class="bi bi-chat-heart"></i></span>
                            <span>{$t.CONTACT_FEATURE_FRIENDLY}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-xl-8">
            <div class="contact-form-panel">
                <h1 class="contact-form-panel__title">{$t.CONTACTS_TITLE}</h1>
                <div id="contact_form">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="contact-field">
                                <label class="contact-field__label" for="contact-name">{$t.CONTACT_YOUR_NAME}</label>
                                <input class="form-control contact-field__input" id="contact-name" name="name" maxlength="100" value="{$contact_prefill_name|escape:'html'}">
                                <div class="error contact-field__error d-none" id="contact-name-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="contact-field">
                                <label class="contact-field__label" for="contact-email">{$t.CONTACT_YOUR_EMAIL}</label>
                                <input class="form-control contact-field__input" id="contact-email" name="email" maxlength="100" value="{$contact_prefill_email|escape:'html'}">
                                <div class="error contact-field__error d-none" id="contact-email-error"></div>
                            </div>
                        </div>
                    </div>
                    <div class="contact-field mb-4">
                        <label class="contact-field__label" for="contact-text">{$t.CONTACT_MESSAGE}</label>
                        <textarea maxlength="10000" id="contact-text" class="form-control contact-field__input" rows="8" name="text"></textarea>
                        <span class="error contact-field__error d-none" id="contact-message-error"></span>
                    </div>
                    <div class="contact-submit-error d-none alert" id="contact-submit-error"></div>
                    <div class="d-flex justify-content-end">
                        <input type="hidden" name="language" value="{$language|escape:'html'}">
                        <input type="hidden" id="contact_timestamp" value="{$timestamp|escape:'html'}">
                        <input type="hidden" id="contact_token" value="{$token|escape:'html'}">
                        <button type="button" class="btn btn-primary contact-submit-btn" id="contact_form_send">
                            <span class="spinner-block d-flex align-items-center gap-2">
                                <span class="spinner spinner-border spinner-border-sm text-light invisible" role="status" id="contact-submit-spinner">
                                    <span class="sr-only">Loading...</span>
                                </span>
                                <span id="button_text">{$t.CONTACT_SEND}</span>
                                <i class="bi bi-send-fill contact-send-icon"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
