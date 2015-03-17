<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 11:41:13
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/templates/actions/backend/BackendLoc.html" */ ?>
<?php /*%%SmartyHeaderCode:31219981955069729d39498-36041351%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
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
  'nocache_hash' => '31219981955069729d39498-36041351',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'strings' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_55069729d978b4_44165348',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_55069729d978b4_44165348')) {function content_55069729d978b4_44165348($_smarty_tpl) {?>$.wa.locale = $.extend($.wa.locale, <?php ob_start();?><?php echo json_encode($_smarty_tpl->tpl_vars['strings']->value);?>
<?php $_tmp1=ob_get_clean();?><?php echo $_tmp1;?>
);<?php }} ?>