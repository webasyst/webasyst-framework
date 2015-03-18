<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 12:24:44
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/error.html" */ ?>
<?php /*%%SmartyHeaderCode:2946625665506a15c75b830-64442254%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '70d82e200e28db03c458be6bdce3fa62907ddffc' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/error.html',
      1 => 1423488544,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '2946625665506a15c75b830-64442254',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'error_message' => 0,
    'error_code' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5506a15c7b06d9_04051746',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5506a15c7b06d9_04051746')) {function content_5506a15c7b06d9_04051746($_smarty_tpl) {?><h1><?php echo (($tmp = @$_smarty_tpl->tpl_vars['error_message']->value)===null||$tmp==='' ? "Ошибка" : $tmp);?>
</h1>
<?php if ($_smarty_tpl->tpl_vars['error_code']->value){?><strong><?php echo $_smarty_tpl->tpl_vars['error_code']->value;?>
.</strong> <?php }?>Запрошенный ресурс недоступен.
<?php }} ?>