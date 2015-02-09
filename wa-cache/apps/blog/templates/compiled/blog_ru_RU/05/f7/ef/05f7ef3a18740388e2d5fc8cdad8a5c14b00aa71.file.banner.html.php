<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:52:57
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/themes/default/banner.html" */ ?>
<?php /*%%SmartyHeaderCode:195306461754d8ada991fed7-99659049%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '05f7ef3a18740388e2d5fc8cdad8a5c14b00aa71' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/themes/default/banner.html',
      1 => 1416918038,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '195306461754d8ada991fed7-99659049',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa' => 0,
    'blogs' => 0,
    'blog' => 0,
    'is_search' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8ada9941e15_97634763',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8ada9941e15_97634763')) {function content_54d8ada9941e15_97634763($_smarty_tpl) {?><?php $_smarty_tpl->tpl_vars['blogs'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->blog->blogs(), null, 0);?>
<?php if (count($_smarty_tpl->tpl_vars['blogs']->value)>1){?>
    <!-- blog list -->
    <ul class="menu-h">
        <?php  $_smarty_tpl->tpl_vars['blog'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['blog']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['blogs']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['blog']->key => $_smarty_tpl->tpl_vars['blog']->value){
$_smarty_tpl->tpl_vars['blog']->_loop = true;
?>
            <li class="<?php if ($_smarty_tpl->tpl_vars['wa']->value->globals('blog_id')==$_smarty_tpl->tpl_vars['blog']->value['id']&&empty($_smarty_tpl->tpl_vars['is_search']->value)){?>selected<?php }?>">
                <a href="<?php echo $_smarty_tpl->tpl_vars['blog']->value['link'];?>
"><?php echo $_smarty_tpl->tpl_vars['blog']->value['name'];?>
</a>
            </li>
        <?php } ?>
    </ul>
<?php }?><?php }} ?>