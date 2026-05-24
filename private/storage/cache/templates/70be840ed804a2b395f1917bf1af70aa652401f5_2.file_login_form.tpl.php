<?php
/* Smarty version 5.8.0, created on 2026-05-24 10:10:02
  from 'file:partials/login_form.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12ce7a049949_25725736',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '70be840ed804a2b395f1917bf1af70aa652401f5' => 
    array (
      0 => 'partials/login_form.tpl',
      1 => 1779491448,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a12ce7a049949_25725736 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/partials';
?><div data-login-form
     data-redirect-url="<?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('form_redirect_url') ?? null)===null||$tmp==='' ? '' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
"
     data-error-email-required="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_EMAIL_REQUIRED']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-email-invalid="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_EMAIL_INVALID']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-password-required="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_ERROR_PASSWORD_REQUIRED']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-invalid="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_INVALID']), ENT_QUOTES, 'UTF-8');?>
">

    <div data-login-alert class="alert d-none" role="alert"></div>

    <div class="mb-3">
        <label for="<?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('form_email_id') ?? null)===null||$tmp==='' ? 'loginEmail' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
" class="form-label"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_EMAIL']), ENT_QUOTES, 'UTF-8');?>
</label>
        <input type="email" id="<?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('form_email_id') ?? null)===null||$tmp==='' ? 'loginEmail' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
" data-login-email
               name="email" class="form-control" autocomplete="email"
               <?php if ((($tmp = $_smarty_tpl->getValue('form_autofocus') ?? null)===null||$tmp==='' ? false ?? null : $tmp)) {?>autofocus<?php }?> required>
        <div data-login-email-error class="invalid-feedback"></div>
    </div>
    <div class="mb-4">
        <label for="<?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('form_password_id') ?? null)===null||$tmp==='' ? 'loginPassword' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
" class="form-label"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_PASSWORD']), ENT_QUOTES, 'UTF-8');?>
</label>
        <input type="password" id="<?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('form_password_id') ?? null)===null||$tmp==='' ? 'loginPassword' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
" data-login-password
               name="password" class="form-control" autocomplete="current-password" required>
        <div data-login-password-error class="invalid-feedback"></div>
    </div>
    <div class="d-grid">
        <button type="button" data-login-submit class="btn btn-primary">
            <i class="bi bi-box-arrow-in-right me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_SIGN_IN']), ENT_QUOTES, 'UTF-8');?>

        </button>
    </div>
    <?php if ($_smarty_tpl->getValue('registration_enabled')) {?>
        <div class="text-center mt-3">
            <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/registration" class="text-decoration-none"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_SIGN_UP']), ENT_QUOTES, 'UTF-8');?>
</a>
        </div>
    <?php }?>
</div>
<?php }
}
