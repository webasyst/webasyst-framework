<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:48:37
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/themes/default/header.html" */ ?>
<?php /*%%SmartyHeaderCode:125402186854d8aca5937cb4-87311078%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '056cc81a5561e20c574360baf8309003776ec0ad' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/themes/default/header.html',
      1 => 1416918753,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '125402186854d8aca5937cb4-87311078',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa' => 0,
    'shop_pages' => 0,
    'page' => 0,
    'checkout_steps' => 0,
    '_upcoming_flag' => 0,
    'step_id' => 0,
    'checkout_current_step' => 0,
    's' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8aca5990e11_01481624',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8aca5990e11_01481624')) {function content_54d8aca5990e11_01481624($_smarty_tpl) {?><?php if (!isset($_smarty_tpl->tpl_vars['shop_pages'])) $_smarty_tpl->tpl_vars['shop_pages'] = new Smarty_Variable(null);if ($_smarty_tpl->tpl_vars['shop_pages']->value = $_smarty_tpl->tpl_vars['wa']->value->shop->pages()){?>
    <ul class="pages">
        <?php  $_smarty_tpl->tpl_vars['page'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['page']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['shop_pages']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['page']->key => $_smarty_tpl->tpl_vars['page']->value){
$_smarty_tpl->tpl_vars['page']->_loop = true;
?>
            <li<?php if (strlen($_smarty_tpl->tpl_vars['page']->value['url'])>1&&strstr($_smarty_tpl->tpl_vars['wa']->value->currentUrl(),$_smarty_tpl->tpl_vars['page']->value['url'])){?> class="selected"<?php }?>><a href="<?php echo $_smarty_tpl->tpl_vars['page']->value['url'];?>
"><?php echo $_smarty_tpl->tpl_vars['page']->value['name'];?>
</a></li>
        <?php } ?>
    </ul>
<?php }?>


<!-- checkout navigation -->
<?php if (isset($_smarty_tpl->tpl_vars['checkout_steps']->value)&&count($_smarty_tpl->tpl_vars['checkout_steps']->value)>1){?>
    <ul class="checkout-navigation">
        <li>
            <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('/frontend/cart');?>
">Корзина</a>
        </li>
        <li>&rarr;</li>
        <?php  $_smarty_tpl->tpl_vars['s'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['s']->_loop = false;
 $_smarty_tpl->tpl_vars['step_id'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['checkout_steps']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
 $_smarty_tpl->tpl_vars['s']->total= $_smarty_tpl->_count($_from);
 $_smarty_tpl->tpl_vars['s']->iteration=0;
 $_smarty_tpl->tpl_vars['s']->index=-1;
foreach ($_from as $_smarty_tpl->tpl_vars['s']->key => $_smarty_tpl->tpl_vars['s']->value){
$_smarty_tpl->tpl_vars['s']->_loop = true;
 $_smarty_tpl->tpl_vars['step_id']->value = $_smarty_tpl->tpl_vars['s']->key;
 $_smarty_tpl->tpl_vars['s']->iteration++;
 $_smarty_tpl->tpl_vars['s']->index++;
 $_smarty_tpl->tpl_vars['s']->first = $_smarty_tpl->tpl_vars['s']->index === 0;
 $_smarty_tpl->tpl_vars['s']->last = $_smarty_tpl->tpl_vars['s']->iteration === $_smarty_tpl->tpl_vars['s']->total;
?>
            <li class="<?php if (isset($_smarty_tpl->tpl_vars['_upcoming_flag']->value)){?> upcoming<?php }?><?php if ($_smarty_tpl->tpl_vars['step_id']->value==$_smarty_tpl->tpl_vars['checkout_current_step']->value){?> selected<?php $_smarty_tpl->tpl_vars['_upcoming_flag'] = new Smarty_variable(1, null, 0);?><?php }?>">
                <a href="<?php if ($_smarty_tpl->tpl_vars['s']->first){?><?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('/frontend/checkout');?>
<?php }else{ ?><?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('/frontend/checkout',array('step'=>$_smarty_tpl->tpl_vars['step_id']->value));?>
<?php }?>"><?php echo $_smarty_tpl->tpl_vars['s']->value['name'];?>
</a>
            </li>
            <?php if (!$_smarty_tpl->tpl_vars['s']->last){?><li>&rarr;</li><?php }?>
        <?php } ?>
    </ul>
    <br>
<?php }?>
<?php }} ?>