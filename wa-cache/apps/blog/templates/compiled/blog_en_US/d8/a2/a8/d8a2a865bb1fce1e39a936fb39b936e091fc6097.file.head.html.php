<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 15:00:09
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/blog/themes/default/head.html" */ ?>
<?php /*%%SmartyHeaderCode:14260717825506c5c9d99388-03581596%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'd8a2a865bb1fce1e39a936fb39b936e091fc6097' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/blog/themes/default/head.html',
      1 => 1423490048,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '14260717825506c5c9d99388-03581596',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa_active_theme_url' => 0,
    'wa_theme_version' => 0,
    'links' => 0,
    'role' => 0,
    'link' => 0,
    'frontend_action' => 0,
    'output' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5506c5c9dc0752_73940008',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5506c5c9dc0752_73940008')) {function content_5506c5c9dc0752_73940008($_smarty_tpl) {?><!-- blog css -->
<link href="<?php echo $_smarty_tpl->tpl_vars['wa_active_theme_url']->value;?>
default.blog.css?v<?php echo $_smarty_tpl->tpl_vars['wa_theme_version']->value;?>
" rel="stylesheet" type="text/css">

<!-- blog js -->
<script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_active_theme_url']->value;?>
default.blog.js?v<?php echo $_smarty_tpl->tpl_vars['wa_theme_version']->value;?>
"></script>

<!-- next & prev links -->
<?php  $_smarty_tpl->tpl_vars['link'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['link']->_loop = false;
 $_smarty_tpl->tpl_vars['role'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['links']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['link']->key => $_smarty_tpl->tpl_vars['link']->value){
$_smarty_tpl->tpl_vars['link']->_loop = true;
 $_smarty_tpl->tpl_vars['role']->value = $_smarty_tpl->tpl_vars['link']->key;
?>
<link rel="<?php echo $_smarty_tpl->tpl_vars['role']->value;?>
" href="<?php echo $_smarty_tpl->tpl_vars['link']->value;?>
">
<?php } ?>


<?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['frontend_action']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?>
    <?php if (!empty($_smarty_tpl->tpl_vars['output']->value['head'])){?><?php echo $_smarty_tpl->tpl_vars['output']->value['head'];?>
<?php }?>
<?php } ?>
<?php }} ?>