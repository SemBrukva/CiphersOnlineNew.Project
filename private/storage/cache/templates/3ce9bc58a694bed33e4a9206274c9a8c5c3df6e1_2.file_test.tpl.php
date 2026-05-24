<?php
/* Smarty version 5.8.0, created on 2026-05-23 09:32:14
  from 'file:emails/test.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a11741e88b831_61421355',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '3ce9bc58a694bed33e4a9206274c9a8c5c3df6e1' => 
    array (
      0 => 'emails/test.tpl',
      1 => 1779528664,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a11741e88b831_61421355 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/emails';
?><h1><?php echo htmlspecialchars((string) ((($tmp = $_smarty_tpl->getValue('app_name') ?? null)===null||$tmp==='' ? 'Skeleton' ?? null : $tmp)), ENT_QUOTES, 'UTF-8');?>
 test email</h1>
<p>This is a test email for <strong><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('email')), ENT_QUOTES, 'UTF-8');?>
</strong>.</p>
<p>Sent at: <?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('sent_at')), ENT_QUOTES, 'UTF-8');?>
</p>
<?php }
}
