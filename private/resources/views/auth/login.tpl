<div class="row justify-content-center">
    <div class="col-sm-8 col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white text-center py-3">
                <h5 class="mb-0">
                    <i class="bi bi-box-arrow-in-right me-2"></i>{$t.AUTH_SIGN_IN_TITLE}
                </h5>
            </div>
            <div class="card-body p-4">
                {include file="partials/login_form.tpl"
                    form_email_id='loginEmail'
                    form_password_id='loginPassword'
                    form_autofocus=true
                    form_redirect_url="{$locale_prefix|default:'/'}"
                }
            </div>
        </div>
    </div>
</div>
