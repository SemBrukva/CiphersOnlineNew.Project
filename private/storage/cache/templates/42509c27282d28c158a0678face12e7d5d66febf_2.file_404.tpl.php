<?php
/* Smarty version 5.8.0, created on 2026-05-24 10:09:56
  from 'file:errors/404.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12ce748eeed9_91758954',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '42509c27282d28c158a0678face12e7d5d66febf' => 
    array (
      0 => 'errors/404.tpl',
      1 => 1779464344,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a12ce748eeed9_91758954 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/errors';
?><section class="py-5">
    <div class="text-center mb-5">
        <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle mb-3">404</span>
        <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_HEADING']), ENT_QUOTES, 'UTF-8');?>
</h1>
        <p class="lead text-muted mx-auto" style="max-width: 760px;">
            <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_TEXT']), ENT_QUOTES, 'UTF-8');?>

        </p>
    </div>

    <div class="row g-4 justify-content-center mb-4">
        <div class="col-lg-7">
            <form method="GET" action="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/search" class="card shadow-sm border-0 p-3 p-md-4">
                <label for="site-search" class="form-label fw-semibold mb-2"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_SEARCH_LABEL']), ENT_QUOTES, 'UTF-8');?>
</label>
                <div class="input-group">
                    <input
                        id="site-search"
                        type="search"
                        name="q"
                        class="form-control"
                        placeholder="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_SEARCH_PLACEHOLDER']), ENT_QUOTES, 'UTF-8');?>
"
                    >
                    <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_SEARCH_BUTTON']), ENT_QUOTES, 'UTF-8');?>
</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 justify-content-center">
        <div class="col-sm-6 col-lg-3">
            <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/" class="card h-100 text-decoration-none shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-2 text-body"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_HOME_TITLE']), ENT_QUOTES, 'UTF-8');?>
</h2>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_HOME_TEXT']), ENT_QUOTES, 'UTF-8');?>
</p>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/contacts" class="card h-100 text-decoration-none shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-2 text-body"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_CONTACTS_TITLE']), ENT_QUOTES, 'UTF-8');?>
</h2>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_CONTACTS_TEXT']), ENT_QUOTES, 'UTF-8');?>
</p>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/login" class="card h-100 text-decoration-none shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-2 text-body"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_LOGIN_TITLE']), ENT_QUOTES, 'UTF-8');?>
</h2>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_LOGIN_TEXT']), ENT_QUOTES, 'UTF-8');?>
</p>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/cabinet" class="card h-100 text-decoration-none shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-2 text-body"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_CABINET_TITLE']), ENT_QUOTES, 'UTF-8');?>
</h2>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['ERROR_404_CABINET_TEXT']), ENT_QUOTES, 'UTF-8');?>
</p>
                </div>
            </a>
        </div>
    </div>
</section>
<?php }
}
