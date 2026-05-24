<?php
/* Smarty version 5.8.0, created on 2026-05-22 23:34:43
  from 'file:admin/redirects/form.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a10e8131780a2_47751194',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '57ad4775cd7c0483a157a58b9a3c612389364bff' => 
    array (
      0 => 'admin/redirects/form.tpl',
      1 => 1779462551,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a10e8131780a2_47751194 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/admin/redirects';
?><div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('title')), ENT_QUOTES, 'UTF-8');?>
</h1>
</div>

<?php if ($_smarty_tpl->getValue('error')) {?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('error')), ENT_QUOTES, 'UTF-8');?>

    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php }?>

<div class="card border-0 shadow-sm" style="max-width:640px">
    <div class="card-body p-4">
        <?php if ($_smarty_tpl->getValue('redirect')) {?>
            <?php $_smarty_tpl->assign('action', ((string)$_smarty_tpl->getValue('admin_path'))."/redirects/".((string)$_smarty_tpl->getValue('redirect')['id']), false, NULL);?>
        <?php } else { ?>
            <?php $_smarty_tpl->assign('action', ((string)$_smarty_tpl->getValue('admin_path'))."/redirects", false, NULL);?>
        <?php }?>

        <form method="POST" action="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('action')), ENT_QUOTES, 'UTF-8');?>
">
            <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('csrf_token')), ENT_QUOTES, 'UTF-8');?>
">

            <div class="mb-3">
                <label for="from_path" class="form-label fw-medium">Откуда <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace" id="from_path" name="from_path"
                       value="<?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('redirect')['from_path'] ?? null)===null||$tmp==='' ? '' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
"
                       placeholder="/old-page" required>
                <div class="form-text">Относительный путь, начиная с /</div>
            </div>

            <div class="mb-3">
                <label for="to_path" class="form-label fw-medium">Куда <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace" id="to_path" name="to_path"
                       value="<?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('redirect')['to_path'] ?? null)===null||$tmp==='' ? '' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
"
                       placeholder="/new-page" required>
                <div class="form-text">Относительный или абсолютный путь назначения</div>
            </div>

            <div class="mb-3">
                <label for="status_code" class="form-label fw-medium">Код статуса</label>
                <select class="form-select" id="status_code" name="status_code">
                    <option value="301" <?php if (!$_smarty_tpl->getValue('redirect') || $_smarty_tpl->getValue('redirect')['status_code'] == 301) {?>selected<?php }?>>
                        301 — Постоянный
                    </option>
                    <option value="302" <?php if ($_smarty_tpl->getValue('redirect') && $_smarty_tpl->getValue('redirect')['status_code'] == 302) {?>selected<?php }?>>
                        302 — Временный
                    </option>
                </select>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="is_active" name="is_active" value="1"
                           <?php if (!$_smarty_tpl->getValue('redirect') || $_smarty_tpl->getValue('redirect')['is_active']) {?>checked<?php }?>>
                    <label class="form-check-label" for="is_active">Активен</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('admin_path')), ENT_QUOTES, 'UTF-8');?>
/redirects" class="btn btn-outline-secondary">Отмена</a>
            </div>
        </form>
    </div>
</div>
<?php }
}
