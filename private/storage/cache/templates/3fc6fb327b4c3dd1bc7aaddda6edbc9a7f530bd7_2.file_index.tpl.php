<?php
/* Smarty version 5.8.0, created on 2026-05-24 11:04:44
  from 'file:admin/dashboard/index.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12db4c5b58b4_15371447',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '3fc6fb327b4c3dd1bc7aaddda6edbc9a7f530bd7' => 
    array (
      0 => 'admin/dashboard/index.tpl',
      1 => 1779462538,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a12db4c5b58b4_15371447 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/admin/dashboard';
?><div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Дашборд</h1>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="admin-stat-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                    <i class="bi bi-people fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Пользователи</div>
                    <div class="fs-4 fw-bold"><?php echo htmlspecialchars((string) ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('users'))), ENT_QUOTES, 'UTF-8');?>
</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0"><i class="bi bi-people me-2 text-primary"></i>Пользователи</h5>
    </div>
    <div class="card-body p-0">
        <?php if ($_smarty_tpl->getValue('users')) {?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width:60px">ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Дата регистрации</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('users'), 'user');
$foreach0DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('user')->value) {
$foreach0DoElse = false;
?>
                    <tr>
                        <td class="ps-4 text-muted small"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('user')['id']), ENT_QUOTES, 'UTF-8');?>
</td>
                        <td class="fw-medium"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('user')['name']), ENT_QUOTES, 'UTF-8');?>
</td>
                        <td class="text-muted"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('user')['email']), ENT_QUOTES, 'UTF-8');?>
</td>
                        <td class="text-muted small"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('user')['created_at']), ENT_QUOTES, 'UTF-8');?>
</td>
                    </tr>
                    <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                </tbody>
            </table>
        </div>
        <?php } else { ?>
        <div class="p-4 text-muted text-center">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
            Пользователей пока нет
        </div>
        <?php }?>
    </div>
</div>
<?php }
}
