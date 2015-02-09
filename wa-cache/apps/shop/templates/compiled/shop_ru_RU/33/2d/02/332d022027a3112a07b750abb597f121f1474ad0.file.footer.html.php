<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:48:37
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/themes/default/footer.html" */ ?>
<?php /*%%SmartyHeaderCode:208017496754d8aca5bd1d82-24879244%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '332d022027a3112a07b750abb597f121f1474ad0' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/themes/default/footer.html',
      1 => 1398339885,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '208017496754d8aca5bd1d82-24879244',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'frontend_footer' => 0,
    '_' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8aca5bda806_29562641',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8aca5bda806_29562641')) {function content_54d8aca5bda806_29562641($_smarty_tpl) {?><!-- plugin hook: 'frontend_footer' -->

<?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['frontend_footer']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo $_smarty_tpl->tpl_vars['_']->value;?>
<?php } ?>

<div class="poweredby" role="complementary">
    <a href="http://www.shop-script.ru/">Создание интернет-магазина</a> — Shop-Script 5 <span class="dots" title="Webasyst"></span>
</div><?php }} ?>