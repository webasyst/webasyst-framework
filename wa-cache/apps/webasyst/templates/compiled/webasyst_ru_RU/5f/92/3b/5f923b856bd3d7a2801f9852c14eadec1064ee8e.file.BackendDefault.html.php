<?php /* Smarty version Smarty-3.1.14, created on 2015-03-17 11:16:50
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-system/webasyst/templates/actions/backend/BackendDefault.html" */ ?>
<?php /*%%SmartyHeaderCode:1482495245507e2f24ca138-00011698%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '5f923b856bd3d7a2801f9852c14eadec1064ee8e' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/webasyst/templates/actions/backend/BackendDefault.html',
      1 => 1418307879,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '1482495245507e2f24ca138-00011698',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'username' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5507e2f282fb53_00601118',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5507e2f282fb53_00601118')) {function content_5507e2f282fb53_00601118($_smarty_tpl) {?><h1><?php echo sprintf("Привет, %s!",htmlspecialchars($_smarty_tpl->tpl_vars['username']->value, ENT_QUOTES, 'UTF-8', true));?>
</h1>
<div style="border:1px dashed #EAEAEA;padding:10px; margin:10px 0">
	<p>Это ваша Панель управления.</p>

	<p>Сейчас она пустая, но скоро на ней появится полезный контент.<br>
	А пока используйте значки наверху этой страницы для работы с доступными приложениями.</p>

	<p>Спасибо за использование Вебасист!</p>
</div>
<?php }} ?>