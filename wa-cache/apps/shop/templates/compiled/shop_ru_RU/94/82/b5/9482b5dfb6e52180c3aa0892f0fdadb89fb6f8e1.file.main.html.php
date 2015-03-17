<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 11:28:58
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/main.html" */ ?>
<?php /*%%SmartyHeaderCode:20363625665506944a4f5f04-24237364%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '9482b5dfb6e52180c3aa0892f0fdadb89fb6f8e1' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/main.html',
      1 => 1423488545,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '20363625665506944a4f5f04-24237364',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'frontend_header' => 0,
    '_' => 0,
    'action' => 0,
    'breadcrumbs' => 0,
    'breadcrumb' => 0,
    'content' => 0,
    '_DROPDOWN_SIDEBAR' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5506944a571144_19637727',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5506944a571144_19637727')) {function content_5506944a571144_19637727($_smarty_tpl) {?><!-- plugin hook: 'frontend_header' -->

<?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['frontend_header']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo $_smarty_tpl->tpl_vars['_']->value;?>
<?php } ?>            

<?php if ($_smarty_tpl->tpl_vars['action']->value=='product'||$_smarty_tpl->tpl_vars['action']->value=='productReviews'||$_smarty_tpl->tpl_vars['action']->value=='cart'){?>
    
    <?php $_smarty_tpl->tpl_vars['_DROPDOWN_SIDEBAR'] = new Smarty_variable(1, null, 0);?>
<?php }?>


    
        
        
   
    
    <div class="page-content" id="page-content">
    
        <!-- internal navigation breadcrumbs -->
        <?php if (!empty($_smarty_tpl->tpl_vars['breadcrumbs']->value)){?>
            <nav class="breadcrumbs">
                <?php  $_smarty_tpl->tpl_vars['breadcrumb'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['breadcrumb']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['breadcrumbs']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['breadcrumb']->key => $_smarty_tpl->tpl_vars['breadcrumb']->value){
$_smarty_tpl->tpl_vars['breadcrumb']->_loop = true;
?>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['breadcrumb']->value['url'];?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['breadcrumb']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</a> <span class="rarr">&rarr;</span>
                <?php } ?>
            </nav>
        <?php }?>
    
        <?php echo $_smarty_tpl->tpl_vars['content']->value;?>

        
        <div class="clear-both"></div>
        
    </div>

<?php if (!empty($_smarty_tpl->tpl_vars['_DROPDOWN_SIDEBAR']->value)){?>
</div>
<?php }?>


<div class="clear-both"></div>

<div id="dialog" class="dialog">
    <div class="dialog-background"></div>
    <div class="dialog-window">
        <!-- common part -->
        <div class="cart">

        </div>
        <!-- /common part -->

    </div>
</div><?php }} ?>