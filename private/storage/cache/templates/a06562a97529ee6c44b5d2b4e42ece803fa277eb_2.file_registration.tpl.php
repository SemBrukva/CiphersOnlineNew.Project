<?php
/* Smarty version 5.8.0, created on 2026-05-24 09:53:35
  from 'file:auth/registration.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12ca9f75aee7_06677917',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'a06562a97529ee6c44b5d2b4e42ece803fa277eb' => 
    array (
      0 => 'auth/registration.tpl',
      1 => 1779469764,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a12ca9f75aee7_06677917 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/auth';
?><div class="row justify-content-center" id="registrationApp"
     data-language="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('current_locale')), ENT_QUOTES, 'UTF-8');?>
"
     data-cabinet-url="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/cabinet"
     data-registering="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_REGISTERING']), ENT_QUOTES, 'UTF-8');?>
"
     data-success="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_REGISTRATION_SUCCESS']), ENT_QUOTES, 'UTF-8');?>
"
     data-failed="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_REGISTRATION_FAILED']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-name-required="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_NAME_REQUIRED']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-name-length="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_NAME_LENGTH']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-email-required="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_EMAIL_REQUIRED']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-email-invalid="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_EMAIL_INVALID']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-password-required="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_PASSWORD_REQUIRED']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-password-length="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_PASSWORD_LENGTH']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-confirmation-required="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_CONFIRMATION_REQUIRED']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-confirmation-mismatch="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_CONFIRMATION_MISMATCH']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-policy-required="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_POLICY_REQUIRED']), ENT_QUOTES, 'UTF-8');?>
">
    <div class="col-sm-10 col-md-7 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white text-center py-3">
                <h5 class="mb-0">
                    <i class="bi bi-person-plus me-2"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_SIGN_UP_TITLE']), ENT_QUOTES, 'UTF-8');?>

                </h5>
            </div>
            <div class="card-body p-4">
                <div id="registrationAlert" class="alert d-none" role="alert"></div>

                <div class="mb-3">
                    <label for="regName" class="form-label"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_NAME']), ENT_QUOTES, 'UTF-8');?>
</label>
                    <input type="text" id="regName" class="form-control" autocomplete="name" required>
                    <div class="invalid-feedback" id="regNameError"></div>
                </div>
                <div class="mb-3">
                    <label for="regEmail" class="form-label"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_EMAIL']), ENT_QUOTES, 'UTF-8');?>
</label>
                    <input type="email" id="regEmail" class="form-control" autocomplete="email" required>
                    <div class="invalid-feedback" id="regEmailError"></div>
                </div>
                <div class="mb-3">
                    <label for="regPassword" class="form-label"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_PASSWORD']), ENT_QUOTES, 'UTF-8');?>
</label>
                    <input type="password" id="regPassword" class="form-control" autocomplete="new-password" required>
                    <div class="invalid-feedback" id="regPasswordError"></div>
                </div>
                <div class="mb-3">
                    <label for="regPasswordConfirmation" class="form-label"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_PASSWORD_CONFIRMATION']), ENT_QUOTES, 'UTF-8');?>
</label>
                    <input type="password" id="regPasswordConfirmation" class="form-control" autocomplete="new-password" required>
                    <div class="invalid-feedback" id="regPasswordConfirmationError"></div>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="regPolicyAgreement">
                        <label class="form-check-label" for="regPolicyAgreement">
                            <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_POLICY_AGREEMENT_TEXT']), ENT_QUOTES, 'UTF-8');?>

                            <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/page/privacy-policy" target="_blank" rel="noopener noreferrer">
                                <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_POLICY_AGREEMENT_LINK']), ENT_QUOTES, 'UTF-8');?>

                            </a>
                        </label>
                        <div class="invalid-feedback" id="regPolicyAgreementError"></div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="button" id="registrationSubmit" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_SIGN_UP']), ENT_QUOTES, 'UTF-8');?>

                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/login" class="text-decoration-none"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ALREADY_HAVE_ACCOUNT']), ENT_QUOTES, 'UTF-8');?>
</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php }
}
