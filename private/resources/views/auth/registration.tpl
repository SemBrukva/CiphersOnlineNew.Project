<div class="row justify-content-center" id="registrationApp"
     data-language="{$current_locale|escape:'html'}"
     data-cabinet-url="{$locale_prefix|escape:'html'}/cabinet"
     data-registering="{$t.AUTH_REGISTERING|escape:'html'}"
     data-success="{$t.AUTH_REGISTRATION_SUCCESS|escape:'html'}"
     data-failed="{$t.AUTH_REGISTRATION_FAILED|escape:'html'}"
     data-error-name-required="{$t.AUTH_ERROR_NAME_REQUIRED|escape:'html'}"
     data-error-name-length="{$t.AUTH_ERROR_NAME_LENGTH|escape:'html'}"
     data-error-email-required="{$t.AUTH_ERROR_EMAIL_REQUIRED|escape:'html'}"
     data-error-email-invalid="{$t.AUTH_ERROR_EMAIL_INVALID|escape:'html'}"
     data-error-password-required="{$t.AUTH_ERROR_PASSWORD_REQUIRED|escape:'html'}"
     data-error-password-length="{$t.AUTH_ERROR_PASSWORD_LENGTH|escape:'html'}"
     data-error-confirmation-required="{$t.AUTH_ERROR_CONFIRMATION_REQUIRED|escape:'html'}"
     data-error-confirmation-mismatch="{$t.AUTH_ERROR_CONFIRMATION_MISMATCH|escape:'html'}"
     data-error-policy-required="{$t.AUTH_ERROR_POLICY_REQUIRED|escape:'html'}">
    <div class="col-sm-10 col-md-7 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white text-center py-3">
                <h5 class="mb-0">
                    <i class="bi bi-person-plus me-2"></i>{$t.AUTH_SIGN_UP_TITLE}
                </h5>
            </div>
            <div class="card-body p-4">
                <div id="registrationAlert" class="alert d-none" role="alert"></div>

                <div class="mb-3">
                    <label for="regName" class="form-label">{$t.AUTH_NAME}</label>
                    <input type="text" id="regName" class="form-control" autocomplete="name" required>
                    <div class="invalid-feedback" id="regNameError"></div>
                </div>
                <div class="mb-3">
                    <label for="regEmail" class="form-label">{$t.AUTH_EMAIL}</label>
                    <input type="email" id="regEmail" class="form-control" autocomplete="email" required>
                    <div class="invalid-feedback" id="regEmailError"></div>
                </div>
                <div class="mb-3">
                    <label for="regPassword" class="form-label">{$t.AUTH_PASSWORD}</label>
                    <input type="password" id="regPassword" class="form-control" autocomplete="new-password" required>
                    <div class="invalid-feedback" id="regPasswordError"></div>
                </div>
                <div class="mb-3">
                    <label for="regPasswordConfirmation" class="form-label">{$t.AUTH_PASSWORD_CONFIRMATION}</label>
                    <input type="password" id="regPasswordConfirmation" class="form-control" autocomplete="new-password" required>
                    <div class="invalid-feedback" id="regPasswordConfirmationError"></div>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="regPolicyAgreement">
                        <label class="form-check-label" for="regPolicyAgreement">
                            {$t.AUTH_POLICY_AGREEMENT_TEXT}
                            <a href="{$locale_prefix}/privacy-policy" target="_blank" rel="noopener noreferrer">
                                {$t.AUTH_POLICY_AGREEMENT_LINK}
                            </a>
                        </label>
                        <div class="invalid-feedback" id="regPolicyAgreementError"></div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="button" id="registrationSubmit" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i>{$t.AUTH_SIGN_UP}
                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="{$locale_prefix}/login" class="text-decoration-none">{$t.AUTH_ALREADY_HAVE_ACCOUNT}</a>
                </div>
            </div>
        </div>
    </div>
</div>
