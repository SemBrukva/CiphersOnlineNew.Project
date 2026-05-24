<div data-login-form
     data-redirect-url="{$form_redirect_url|default:''|escape:'html'}"
     data-error-email-required="{$t.AUTH_ERROR_EMAIL_REQUIRED|escape:'html'}"
     data-error-email-invalid="{$t.AUTH_ERROR_EMAIL_INVALID|escape:'html'}"
     data-error-password-required="{$t.AUTH_ERROR_PASSWORD_REQUIRED|escape:'html'}"
     data-error-invalid="{$t.AUTH_INVALID|escape:'html'}">

    <div data-login-alert class="alert d-none" role="alert"></div>

    <div class="mb-3">
        <label for="{$form_email_id|default:'loginEmail'}" class="form-label">{$t.AUTH_EMAIL}</label>
        <input type="email" id="{$form_email_id|default:'loginEmail'}" data-login-email
               name="email" class="form-control" autocomplete="email"
               {if $form_autofocus|default:false}autofocus{/if} required>
        <div data-login-email-error class="invalid-feedback"></div>
    </div>
    <div class="mb-4">
        <label for="{$form_password_id|default:'loginPassword'}" class="form-label">{$t.AUTH_PASSWORD}</label>
        <input type="password" id="{$form_password_id|default:'loginPassword'}" data-login-password
               name="password" class="form-control" autocomplete="current-password" required>
        <div data-login-password-error class="invalid-feedback"></div>
    </div>
    <div class="d-grid">
        <button type="button" data-login-submit class="btn btn-primary">
            <i class="bi bi-box-arrow-in-right me-1"></i>{$t.AUTH_SIGN_IN}
        </button>
    </div>
    {if $registration_enabled}
        <div class="text-center mt-3">
            <a href="{$locale_prefix}/registration" class="text-decoration-none">{$t.AUTH_SIGN_UP}</a>
        </div>
    {/if}
</div>
