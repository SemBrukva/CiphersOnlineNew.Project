<?php
/* Smarty version 5.8.0, created on 2026-05-24 11:04:43
  from 'file:cabinet/index.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12db4b536155_00228872',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '4b2d66206f2d9146b83bd990750caf794aaaf192' => 
    array (
      0 => 'cabinet/index.tpl',
      1 => 1779463957,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a12db4b536155_00228872 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/cabinet';
?><div class="row">
    <div class="col-lg-4 mb-4 mb-lg-0">
        <div class="card shadow-sm text-center">
            <div class="card-body py-4">
                <i class="bi bi-person-circle display-3 text-primary mb-3 d-block"></i>
                <h4 class="card-title mb-1"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('auth_user')['name']), ENT_QUOTES, 'UTF-8');?>
</h4>
                <p class="text-muted small mb-3"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('auth_user')['email']), ENT_QUOTES, 'UTF-8');?>
</p>
                <span class="badge bg-success"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CABINET_STATUS']), ENT_QUOTES, 'UTF-8');?>
</span>
            </div>
            <div class="card-footer bg-transparent">
                <form method="POST" action="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/logout">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('csrf_token')), ENT_QUOTES, 'UTF-8');?>
">
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-box-arrow-right me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CABINET_SIGN_OUT']), ENT_QUOTES, 'UTF-8');?>

                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <h1 class="mb-4"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CABINET_TITLE']), ENT_QUOTES, 'UTF-8');?>
</h1>

        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card text-center border-0 bg-primary bg-opacity-10">
                    <div class="card-body">
                        <i class="bi bi-hash fs-2 text-primary mb-2 d-block"></i>
                        <div class="fw-bold"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CABINET_ID']), ENT_QUOTES, 'UTF-8');?>
</div>
                        <div class="text-muted small">#<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('auth_user')['id']), ENT_QUOTES, 'UTF-8');?>
</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card text-center border-0 bg-success bg-opacity-10">
                    <div class="card-body">
                        <i class="bi bi-envelope-check fs-2 text-success mb-2 d-block"></i>
                        <div class="fw-bold">Email</div>
                        <div class="text-muted small text-truncate"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('auth_user')['email']), ENT_QUOTES, 'UTF-8');?>
</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card text-center border-0 bg-warning bg-opacity-10">
                    <div class="card-body">
                        <i class="bi bi-shield-check fs-2 text-warning mb-2 d-block"></i>
                        <div class="fw-bold"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CABINET_STATUS_LBL']), ENT_QUOTES, 'UTF-8');?>
</div>
                        <div class="text-muted small"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CABINET_ROLE']), ENT_QUOTES, 'UTF-8');?>
</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CABINET_ACTIVITY']), ENT_QUOTES, 'UTF-8');?>
</h6>
            </div>
            <div class="card-body text-muted small">
                <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CABINET_ACTIVITY_TXT']), ENT_QUOTES, 'UTF-8');?>

            </div>
        </div>
    </div>
</div>
<?php }
}
