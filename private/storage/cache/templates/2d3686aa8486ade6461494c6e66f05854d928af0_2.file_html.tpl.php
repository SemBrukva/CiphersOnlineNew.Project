<?php
/* Smarty version 5.8.0, created on 2026-05-22 22:55:05
  from 'file:sitemap/html.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a10dec9728025_21854995',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '2d3686aa8486ade6461494c6e66f05854d928af0' => 
    array (
      0 => 'sitemap/html.tpl',
      1 => 1779490021,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a10dec9728025_21854995 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/sitemap';
?><div class="row justify-content-center">
    <div class="col-md-8">
        <h1 class="mb-4"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['SITEMAP_TITLE']), ENT_QUOTES, 'UTF-8');?>
</h1>

        <section class="mb-4">
            <h2 class="h5 text-muted mb-3"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['SITEMAP_MAIN_PAGES']), ENT_QUOTES, 'UTF-8');?>
</h2>
            <ul class="list-unstyled">
                <li class="mb-1">
                    <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['MENU_HOME']), ENT_QUOTES, 'UTF-8');?>
</a>
                </li>
                <li class="mb-1">
                    <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/contacts"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['MENU_CONTACTS']), ENT_QUOTES, 'UTF-8');?>
</a>
                </li>
            </ul>
        </section>

        <?php if ($_smarty_tpl->getValue('pages')) {?>
            <section class="mb-4">
                <h2 class="h5 text-muted mb-3"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['SITEMAP_PAGES']), ENT_QUOTES, 'UTF-8');?>
</h2>
                <ul class="list-unstyled">
                    <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('pages'), 'page');
$foreach0DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('page')->value) {
$foreach0DoElse = false;
?>
                        <li class="mb-1">
                            <a href="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('locale_prefix')), ENT_QUOTES, 'UTF-8');?>
/page/<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('page')['alias']), ENT_QUOTES, 'UTF-8');?>
"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('page')['name']), ENT_QUOTES, 'UTF-8');?>
</a>
                        </li>
                    <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
                </ul>
            </section>
        <?php }?>

        <p class="mt-4">
            <a href="/sitemap.xml" class="text-muted small">
                <i class="bi bi-filetype-xml me-1"></i>XML Sitemap
            </a>
        </p>
    </div>
</div>
<?php }
}
