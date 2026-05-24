<?php
/* Smarty version 5.8.0, created on 2026-05-24 09:53:33
  from 'file:auth/login.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12ca9da85334_56432695',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '06e6e1af17b609a736bad9c642501f85887ec43b' => 
    array (
      0 => 'auth/login.tpl',
      1 => 1779491455,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:partials/login_form.tpl' => 1,
  ),
))) {
function content_6a12ca9da85334_56432695 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/auth';
?><div class="row justify-content-center">
    <div class="col-sm-8 col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white text-center py-3">
                <h5 class="mb-0">
                    <i class="bi bi-box-arrow-in-right me-2"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_SIGN_IN_TITLE']), ENT_QUOTES, 'UTF-8');?>

                </h5>
            </div>
            <div class="card-body p-4">
                <?php $_smarty_tpl->renderSubTemplate("file:partials/login_form.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('form_email_id'=>'loginEmail','form_password_id'=>'loginPassword','form_autofocus'=>true,'form_redirect_url'=>((string)$_smarty_tpl->getValue('locale_prefix'))."/"), (int) 0, $_smarty_current_dir);
?>
            </div>
        </div>
    </div>
</div>
<?php }
}
