<?php
/* Smarty version 5.8.0, created on 2026-05-24 11:04:43
  from 'file:layouts/main.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12db4b547fe8_72160272',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'c70717caadc516d901edbe91cbe25630ef82fcde' => 
    array (
      0 => 'layouts/main.tpl',
      1 => 1779492423,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:partials/navbar.tpl' => 1,
    'file:partials/login_form.tpl' => 1,
  ),
))) {
function content_6a12db4b547fe8_72160272 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/layouts';
?><!doctype html>
<html lang="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('current_locale')), ENT_QUOTES, 'UTF-8');?>
">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('title') ?? null)===null||$tmp==='' ? 'Application' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
</title>
    <?php if ($_smarty_tpl->getValue('meta_description')) {?><meta name="description" content="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('meta_description')), ENT_QUOTES, 'UTF-8');?>
"><?php }?>
    <?php echo $_smarty_tpl->getSmarty()->getFunctionHandler('vite')->handle(array('entry'=>"private/resources/js/app.js",'type'=>"css"), $_smarty_tpl);?>

</head>
<body class="d-flex flex-column min-vh-100">

<?php $_smarty_tpl->renderSubTemplate("file:partials/navbar.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), (int) 0, $_smarty_current_dir);
?>

<main class="flex-grow-1 py-4">
    <div class="container">
        <?php if ($_smarty_tpl->getValue('breadcrumbs')) {?>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('breadcrumbs'), 'crumb', true);
$_smarty_tpl->getVariable('crumb')->iteration = 0;
$foreach0DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('crumb')->value) {
$foreach0DoElse = false;
$_smarty_tpl->getVariable('crumb')->iteration++;
$_smarty_tpl->getVariable('crumb')->last = $_smarty_tpl->getVariable('crumb')->iteration === $_smarty_tpl->getVariable('crumb')->total;
$foreach0Backup = clone $_smarty_tpl->getVariable('crumb');
?>
                        <?php if ($_smarty_tpl->getVariable('crumb')->last) {?>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('crumb')['label']), ENT_QUOTES, 'UTF-8');?>
</li>
                        <?php } else { ?>
                            <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('crumb')['url']), ENT_QUOTES, 'UTF-8');?>
"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('crumb')['label']), ENT_QUOTES, 'UTF-8');?>
</a></li>
                        <?php }?>
                    <?php
$_smarty_tpl->setVariable('crumb', $foreach0Backup);
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                </ol>
            </nav>
        <?php }?>
        <?php if ($_smarty_tpl->getValue('sidebar')) {?>
            <div class="row">
                <div class="col-md-8" id="contentRow">
                    <?php echo $_smarty_tpl->getValue('content');?>

                </div>
                <div class="col-md-4" id="sidebarRow">
                    <?php echo $_smarty_tpl->getValue('sidebar');?>

                </div>
            </div>
        <?php } else { ?>
            <?php echo $_smarty_tpl->getValue('content');?>

        <?php }?>
    </div>
</main>

<footer class="bg-dark text-light py-3 mt-auto">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start small">
                &copy; <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('current_year')), ENT_QUOTES, 'UTF-8');?>
 Skeleton
            </div>
            <div class="col-md-6 text-center text-md-end">
                <nav class="d-inline-flex flex-wrap gap-3">
                    <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('nav_pages'), 'pg');
$foreach1DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('pg')->value) {
$foreach1DoElse = false;
?>
                        <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/page/<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('pg')['alias']), ENT_QUOTES, 'UTF-8');?>
" class="text-light text-decoration-none small">
                            <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('pg')['name']), ENT_QUOTES, 'UTF-8');?>

                        </a>
                    <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                    <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/sitemap" class="text-light text-decoration-none small">
                        <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['MENU_SITEMAP']), ENT_QUOTES, 'UTF-8');?>

                    </a>
                </nav>
            </div>
        </div>
    </div>
</footer>

<?php echo $_smarty_tpl->getSmarty()->getFunctionHandler('vite')->handle(array('entry'=>"private/resources/js/app.js",'type'=>"js"), $_smarty_tpl);?>


<?php if ($_smarty_tpl->getValue('auth_user') === null) {?>
<div class="modal fade" id="loginModal" tabindex="-1"
     aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="loginModalLabel">
                    <i class="bi bi-box-arrow-in-right me-2"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_SIGN_IN_TITLE']), ENT_QUOTES, 'UTF-8');?>

                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <?php $_smarty_tpl->renderSubTemplate("file:partials/login_form.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('form_email_id'=>'modalLoginEmail','form_password_id'=>'modalLoginPassword'), (int) 0, $_smarty_current_dir);
?>
            </div>
        </div>
    </div>
</div>
<?php }?>
</body>
</html>
<?php }
}
