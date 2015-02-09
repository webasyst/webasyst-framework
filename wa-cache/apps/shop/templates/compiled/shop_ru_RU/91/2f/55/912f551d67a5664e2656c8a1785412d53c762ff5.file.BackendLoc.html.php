<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:39:08
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/templates/actions/backend/BackendLoc.html" */ ?>
<?php /*%%SmartyHeaderCode:39569668554d8aa6c199d81-08557379%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '912f551d67a5664e2656c8a1785412d53c762ff5' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/templates/actions/backend/BackendLoc.html',
      1 => 1360765463,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '39569668554d8aa6c199d81-08557379',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'strings' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8aa6c1c9426_35081841',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8aa6c1c9426_35081841')) {function content_54d8aa6c1c9426_35081841($_smarty_tpl) {?>$.wa.locale = $.extend($.wa.locale, <?php ob_start();?><?php echo json_encode($_smarty_tpl->tpl_vars['strings']->value);?>
<?php $_tmp1=ob_get_clean();?><?php echo $_tmp1;?>
);<?php }} ?>