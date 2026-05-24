<?php
/* Smarty version 5.8.0, created on 2026-05-24 11:04:43
  from 'file:partials/navbar.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12db4b555cf1_20240180',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'b11ca7c1a648ac8324c9b5abdf45725a8beb5534' => 
    array (
      0 => 'partials/navbar.tpl',
      1 => 1779619476,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a12db4b555cf1_20240180 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/partials';
?><nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('locale_prefix') ?? null)===null||$tmp==='' ? '/' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
">
            <i class="bi bi-layers me-1"></i> Skeleton
        </a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('nav_main'), 'item');
$foreach2DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('item')->value) {
$foreach2DoElse = false;
?>
                    <li class="nav-item">
                        <a class="nav-link<?php if ($_smarty_tpl->getValue('item')['active']) {?> active<?php }?>" href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('item')['url']), ENT_QUOTES, 'UTF-8');?>
">
                            <?php if ($_smarty_tpl->getValue('item')['icon']) {?><i class="bi <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('item')['icon']), ENT_QUOTES, 'UTF-8');?>
 me-1"></i><?php }?>
                            <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('item')['label']), ENT_QUOTES, 'UTF-8');?>

                        </a>
                    </li>
                <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if ($_smarty_tpl->getValue('multilang') && $_smarty_tpl->getValue('auth_user') === null && $_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('available_locales')) > 1) {?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars((string) (mb_strtoupper((string) $_smarty_tpl->getValue('current_locale') ?? '', 'UTF-8')), ENT_QUOTES, 'UTF-8');?>

                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('available_locales'), 'lang');
$foreach3DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('lang')->value) {
$foreach3DoElse = false;
?>
                                <li>
                                    <a class="dropdown-item<?php if ($_smarty_tpl->getValue('lang') === $_smarty_tpl->getValue('current_locale')) {?> active<?php }?>"
                                       href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_urls')[$_smarty_tpl->getValue('lang')]), ENT_QUOTES, 'UTF-8');?>
">
                                        <?php echo htmlspecialchars((string) (mb_strtoupper((string) $_smarty_tpl->getValue('lang') ?? '', 'UTF-8')), ENT_QUOTES, 'UTF-8');?>

                                    </a>
                                </li>
                            <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                        </ul>
                    </div>
                <?php }?>

                <?php if ($_smarty_tpl->getValue('auth_user') !== null) {?>
                    <?php if ($_smarty_tpl->getValue('is_admin')) {?>
                        <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('admin_path')), ENT_QUOTES, 'UTF-8');?>
" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-shield-lock me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['MENU_ADMIN']), ENT_QUOTES, 'UTF-8');?>

                        </a>
                    <?php }?>
                    <form method="POST" action="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/logout" class="m-0">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('csrf_token')), ENT_QUOTES, 'UTF-8');?>
">
                        <button type="submit" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_SIGN_OUT']), ENT_QUOTES, 'UTF-8');?>

                        </button>
                    </form>
                <?php } else { ?>
                    <button type="button" class="btn btn-outline-light btn-sm"
                            data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-box-arrow-in-right me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['AUTH_SIGN_IN']), ENT_QUOTES, 'UTF-8');?>

                    </button>
                <?php }?>
            </div>
        </div>
    </div>
</nav>
<?php }
}
