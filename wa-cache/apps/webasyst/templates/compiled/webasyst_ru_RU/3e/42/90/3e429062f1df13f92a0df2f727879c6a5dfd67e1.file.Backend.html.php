<?php /* Smarty version Smarty-3.1.14, created on 2015-03-17 11:16:50
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-system/webasyst/templates/layouts/Backend.html" */ ?>
<?php /*%%SmartyHeaderCode:15232060985507e2f28fd812-18462938%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '3e429062f1df13f92a0df2f727879c6a5dfd67e1' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/webasyst/templates/layouts/Backend.html',
      1 => 1418307879,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '15232060985507e2f28fd812-18462938',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa' => 0,
    'wa_url' => 0,
    'content' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5507e2f294a6a8_92002527',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5507e2f294a6a8_92002527')) {function content_5507e2f294a6a8_92002527($_smarty_tpl) {?><!DOCTYPE html><html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Добро пожаловать &mdash; <?php echo $_smarty_tpl->tpl_vars['wa']->value->accountName();?>
</title>
    <?php echo $_smarty_tpl->tpl_vars['wa']->value->css();?>

    <script src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery/jquery-1.11.1.min.js" type="text/javascript"></script>
    <script src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery/jquery-migrate-1.2.1.min.js" type="text/javascript"></script>
    <script src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery-wa/wa.core.js"></script>
</head>
<body>
<?php echo $_smarty_tpl->tpl_vars['wa']->value->header();?>

<div id="wa-app" class="block double-padded">
    <?php echo $_smarty_tpl->tpl_vars['content']->value;?>

</div>
</body>
</html><?php }} ?>