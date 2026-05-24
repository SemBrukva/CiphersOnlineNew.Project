<?php
/* Smarty version 5.8.0, created on 2026-05-24 11:03:36
  from 'file:home/index.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12db08ceb9f1_48835781',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '8900897de96c6a646e27c01a1ca7a72470cb7d71' => 
    array (
      0 => 'home/index.tpl',
      1 => 1779619500,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a12db08ceb9f1_48835781 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/home';
?><div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="mb-4"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('heading')), ENT_QUOTES, 'UTF-8');?>
</h1>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-rocket-takeoff me-2 text-primary"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['HOME_WELCOME_TITLE']), ENT_QUOTES, 'UTF-8');?>

                </h5>
                <p class="card-text text-muted mb-0"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['HOME_WELCOME_TEXT']), ENT_QUOTES, 'UTF-8');?>
</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-sm-6">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <h6 class="card-title text-primary">
                            <i class="bi bi-shield-lock me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['FEAT_AUTH']), ENT_QUOTES, 'UTF-8');?>

                        </h6>
                        <p class="card-text small text-muted mb-0"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['FEAT_AUTH_DESC']), ENT_QUOTES, 'UTF-8');?>
</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card h-100 border-success">
                    <div class="card-body">
                        <h6 class="card-title text-success">
                            <i class="bi bi-database me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['FEAT_DB']), ENT_QUOTES, 'UTF-8');?>

                        </h6>
                        <p class="card-text small text-muted mb-0"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['FEAT_DB_DESC']), ENT_QUOTES, 'UTF-8');?>
</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card h-100 border-warning">
                    <div class="card-body">
                        <h6 class="card-title text-warning">
                            <i class="bi bi-arrow-left-right me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['FEAT_ROUTER']), ENT_QUOTES, 'UTF-8');?>

                        </h6>
                        <p class="card-text small text-muted mb-0"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['FEAT_ROUTER_DESC']), ENT_QUOTES, 'UTF-8');?>
</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card h-100 border-info">
                    <div class="card-body">
                        <h6 class="card-title text-info">
                            <i class="bi bi-file-earmark-code me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['FEAT_TEMPLATES']), ENT_QUOTES, 'UTF-8');?>

                        </h6>
                        <p class="card-text small text-muted mb-0"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['FEAT_TEMPLATES_DESC']), ENT_QUOTES, 'UTF-8');?>
</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <?php if ($_smarty_tpl->getValue('auth_user') === null) {?>
                <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/login" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['HOME_GO_LOGIN']), ENT_QUOTES, 'UTF-8');?>

                </a>
            <?php } else { ?>
                <a href="/cabinet" class="btn btn-primary">
                    <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['HOME_GO_CABINET']), ENT_QUOTES, 'UTF-8');?>

                </a>
            <?php }?>
        </div>
    </div>
</div>
<?php }
}
