<?php
/* Smarty version 5.8.0, created on 2026-05-22 17:17:32
  from 'file:page/show.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a108fac05c260_05406330',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '6c79868c7f3cce4321f0e9ae7a5ed1c588efed88' => 
    array (
      0 => 'page/show.tpl',
      1 => 1779462272,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a108fac05c260_05406330 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/page';
?><div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="mb-4"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('page')['name']), ENT_QUOTES, 'UTF-8');?>
</h1>
        <div class="card shadow-sm">
            <div class="card-body">
                <?php echo $_smarty_tpl->getValue('page')['text'];?>

            </div>
        </div>
    </div>
</div>
<?php }
}
