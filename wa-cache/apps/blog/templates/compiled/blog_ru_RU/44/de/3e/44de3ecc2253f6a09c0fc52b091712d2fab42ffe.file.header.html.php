<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:52:57
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/themes/default/header.html" */ ?>
<?php /*%%SmartyHeaderCode:181141770154d8ada98f0b81-69698048%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '44de3ecc2253f6a09c0fc52b091712d2fab42ffe' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/themes/default/header.html',
      1 => 1416918038,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '181141770154d8ada98f0b81-69698048',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa' => 0,
    'pages' => 0,
    'page' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8ada9917121_71636314',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8ada9917121_71636314')) {function content_54d8ada9917121_71636314($_smarty_tpl) {?><?php $_smarty_tpl->tpl_vars['pages'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->blog->pages(), null, 0);?>
<?php if (count($_smarty_tpl->tpl_vars['pages']->value)){?>
    <ul class="pages">
        <!-- pages -->            
        <?php  $_smarty_tpl->tpl_vars['page'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['page']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['pages']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['page']->key => $_smarty_tpl->tpl_vars['page']->value){
$_smarty_tpl->tpl_vars['page']->_loop = true;
?>
            <li<?php if (strlen($_smarty_tpl->tpl_vars['page']->value['url'])>1&&strstr($_smarty_tpl->tpl_vars['wa']->value->currentUrl(),$_smarty_tpl->tpl_vars['page']->value['url'])){?> class="selected"<?php }?>><a href="<?php echo $_smarty_tpl->tpl_vars['page']->value['url'];?>
"><?php echo $_smarty_tpl->tpl_vars['page']->value['name'];?>
</a></li>
        <?php } ?>
    </ul>
<?php }?>

<?php }} ?>