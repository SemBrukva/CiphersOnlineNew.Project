<?php
/* Smarty version 5.8.0, created on 2026-05-24 10:45:29
  from 'file:admin/redirects/index.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12d6c94d5b55_29737227',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '4928b4b4c1d4c9b54a16041cc44a4da111ac8c95' => 
    array (
      0 => 'admin/redirects/index.tpl',
      1 => 1779462545,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a12d6c94d5b55_29737227 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/admin/redirects';
?><div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Редиректы</h1>
    <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('admin_path')), ENT_QUOTES, 'UTF-8');?>
/redirects/create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Добавить
    </a>
</div>

<?php if ($_smarty_tpl->getValue('success')) {?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('success')), ENT_QUOTES, 'UTF-8');?>

    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php }?>

<?php if ($_smarty_tpl->getValue('error')) {?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('error')), ENT_QUOTES, 'UTF-8');?>

    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php }?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if ($_smarty_tpl->getValue('redirects')) {?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width:60px">ID</th>
                        <th>Откуда</th>
                        <th>Куда</th>
                        <th style="width:80px">Код</th>
                        <th style="width:90px">Активен</th>
                        <th style="width:100px">Переходов</th>
                        <th style="width:160px">Изменён</th>
                        <th style="width:110px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('redirects'), 'r');
$foreach0DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('r')->value) {
$foreach0DoElse = false;
?>
                    <tr>
                        <td class="ps-4 text-muted small"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('r')['id']), ENT_QUOTES, 'UTF-8');?>
</td>
                        <td class="font-monospace small text-break"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('r')['from_path']), ENT_QUOTES, 'UTF-8');?>
</td>
                        <td class="font-monospace small text-break"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('r')['to_path']), ENT_QUOTES, 'UTF-8');?>
</td>
                        <td>
                            <span class="badge <?php if ($_smarty_tpl->getValue('r')['status_code'] == 301) {?>bg-secondary<?php } else { ?>bg-info text-dark<?php }?>">
                                <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('r')['status_code']), ENT_QUOTES, 'UTF-8');?>

                            </span>
                        </td>
                        <td>
                            <?php if ($_smarty_tpl->getValue('r')['is_active']) {?>
                                <span class="badge bg-success">Да</span>
                            <?php } else { ?>
                                <span class="badge bg-secondary">Нет</span>
                            <?php }?>
                        </td>
                        <td class="text-muted"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('r')['hit_count']), ENT_QUOTES, 'UTF-8');?>
</td>
                        <td class="text-muted small"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('r')['updated_at']), ENT_QUOTES, 'UTF-8');?>
</td>
                        <td class="text-end pe-3">
                            <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('admin_path')), ENT_QUOTES, 'UTF-8');?>
/redirects/<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('r')['id']), ENT_QUOTES, 'UTF-8');?>
/edit"
                               class="btn btn-sm btn-outline-secondary me-1" title="Редактировать">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('admin_path')), ENT_QUOTES, 'UTF-8');?>
/redirects/<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('r')['id']), ENT_QUOTES, 'UTF-8');?>
/delete"
                                  class="d-inline"
                                  onsubmit="return confirm('Удалить редирект?')">
                                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('csrf_token')), ENT_QUOTES, 'UTF-8');?>
">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                </tbody>
            </table>
        </div>
        <?php } else { ?>
        <div class="p-5 text-center text-muted">
            <i class="bi bi-signpost-split fs-2 d-block mb-2"></i>
            Редиректов пока нет.
            <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('admin_path')), ENT_QUOTES, 'UTF-8');?>
/redirects/create">Добавить первый</a>
        </div>
        <?php }?>
    </div>
</div>
<?php }
}
