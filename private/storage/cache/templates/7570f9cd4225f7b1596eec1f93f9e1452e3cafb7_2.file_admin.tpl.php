<?php
/* Smarty version 5.8.0, created on 2026-05-24 11:04:44
  from 'file:admin/layouts/admin.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12db4c5c7295_17298261',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '7570f9cd4225f7b1596eec1f93f9e1452e3cafb7' => 
    array (
      0 => 'admin/layouts/admin.tpl',
      1 => 1779487596,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a12db4c5c7295_17298261 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/admin/layouts';
?><!doctype html>
<html lang="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('current_locale')), ENT_QUOTES, 'UTF-8');?>
">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('title') ?? null)===null||$tmp==='' ? 'Admin' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
 — Admin</title>
    <?php echo $_smarty_tpl->getSmarty()->getFunctionHandler('vite')->handle(array('entry'=>"private/resources/js/admin.js",'type'=>"css"), $_smarty_tpl);?>

</head>
<body class="d-flex min-vh-100 bg-light">

<nav id="admin-sidebar" class="d-flex flex-column flex-shrink-0 p-3 bg-dark text-white">
    <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('admin_path')), ENT_QUOTES, 'UTF-8');?>
/" class="d-flex align-items-center mb-3 mb-md-0 text-white text-decoration-none">
        <i class="bi bi-shield-lock fs-4 me-2"></i>
        <span class="fs-5 fw-bold">Admin</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('admin_path')), ENT_QUOTES, 'UTF-8');?>
/" class="nav-link text-white<?php if ($_smarty_tpl->getValue('current_path') === $_smarty_tpl->getValue('admin_path') || $_smarty_tpl->getValue('current_path') === ($_smarty_tpl->getValue('admin_path')).('/')) {?> active<?php }?>">
                <i class="bi bi-speedometer2 me-2"></i>Дашборд
            </a>
        </li>
        <li class="nav-item">
            <?php $_smarty_tpl->assign('redirects_prefix', ($_smarty_tpl->getValue('admin_path')).('/redirects'), false, NULL);?>
            <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('admin_path')), ENT_QUOTES, 'UTF-8');?>
/redirects" class="nav-link text-white<?php if ($_smarty_tpl->getSmarty()->getModifierCallback('starts_with')($_smarty_tpl->getValue('current_path'),$_smarty_tpl->getValue('redirects_prefix'))) {?> active<?php }?>">
                <i class="bi bi-signpost-split me-2"></i>Редиректы
            </a>
        </li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
           data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2"></i>
            <span class="text-truncate"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('auth_user')['name']), ENT_QUOTES, 'UTF-8');?>
</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
            <li><a class="dropdown-item" href="/cabinet"><i class="bi bi-person me-1"></i>Кабинет</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="/logout" class="m-0">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('csrf_token')), ENT_QUOTES, 'UTF-8');?>
">
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="bi bi-box-arrow-right me-1"></i>Выйти
                    </button>
                </form>
            </li>
        </ul>
    </div>
</nav>

<div class="d-flex flex-column flex-grow-1 overflow-hidden">
    <header class="admin-topbar d-flex align-items-center px-4 py-2 bg-white border-bottom shadow-sm">
        <button id="sidebar-toggle" class="btn btn-sm btn-outline-secondary me-3" type="button">
            <i class="bi bi-list fs-5"></i>
        </button>
        <nav aria-label="breadcrumb" class="mb-0">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('admin_path')), ENT_QUOTES, 'UTF-8');?>
/">Admin</a></li>
                <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('breadcrumbs'), 'crumb', true);
$_smarty_tpl->getVariable('crumb')->iteration = 0;
$foreach1DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('crumb')->value) {
$foreach1DoElse = false;
$_smarty_tpl->getVariable('crumb')->iteration++;
$_smarty_tpl->getVariable('crumb')->last = $_smarty_tpl->getVariable('crumb')->iteration === $_smarty_tpl->getVariable('crumb')->total;
$foreach1Backup = clone $_smarty_tpl->getVariable('crumb');
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
$_smarty_tpl->setVariable('crumb', $foreach1Backup);
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
            </ol>
        </nav>
    </header>

    <main class="flex-grow-1 p-4 overflow-auto">
        <?php echo $_smarty_tpl->getValue('content');?>

    </main>
</div>

<?php echo $_smarty_tpl->getSmarty()->getFunctionHandler('vite')->handle(array('entry'=>"private/resources/js/admin.js",'type'=>"js"), $_smarty_tpl);?>

</body>
</html>
<?php }
}
