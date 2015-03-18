<?php /* Smarty version Smarty-3.1.14, created on 2015-03-17 23:29:17
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/category.html" */ ?>
<?php /*%%SmartyHeaderCode:193046359655088e9ddf6e97-05036819%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'dcbe763c79a2fd67db31ca576f31c10ee13b70f7' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/category.html',
      1 => 1424940677,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '193046359655088e9ddf6e97-05036819',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa_active_theme_url' => 0,
    'wa_theme_version' => 0,
    'category' => 0,
    'frontend_category' => 0,
    '_' => 0,
    'sc' => 0,
    'products' => 0,
    'filters' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_55088e9de95f26_47916264',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_55088e9de95f26_47916264')) {function content_55088e9de95f26_47916264($_smarty_tpl) {?><?php if (waRequest::isXMLHttpRequest()&&waRequest::get('page',1)==1){?>
<script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_active_theme_url']->value;?>
lazyloading.js?v<?php echo $_smarty_tpl->tpl_vars['wa_theme_version']->value;?>
"></script>
<?php }?>

<h1 class="category-name">
    <?php echo htmlspecialchars($_smarty_tpl->tpl_vars['category']->value['name'], ENT_QUOTES, 'UTF-8', true);?>

</h1>

<!-- plugin hook: 'frontend_category' -->

<?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['frontend_category']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo $_smarty_tpl->tpl_vars['_']->value;?>
<?php } ?>




<!-- subcategories -->
<!--<?php if ($_smarty_tpl->tpl_vars['category']->value['subcategories']){?>-->
<!--    <ul class="sub-categories">-->
<!--        <?php  $_smarty_tpl->tpl_vars['sc'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['sc']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['category']->value['subcategories']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['sc']->key => $_smarty_tpl->tpl_vars['sc']->value){
$_smarty_tpl->tpl_vars['sc']->_loop = true;
?>-->
<!--            <li><a href="<?php echo $_smarty_tpl->tpl_vars['sc']->value['url'];?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['sc']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</a></li>-->
<!--        <?php } ?>-->
<!--    </ul>-->
<!--<?php }?>-->

<div id="product-list">
<?php if (!$_smarty_tpl->tpl_vars['products']->value){?>
    <?php if (!empty($_smarty_tpl->tpl_vars['filters']->value)){?>
        Не найдено ни одного товара.
    <?php }else{ ?>
        В этой категории нет ни одного товара.
    <?php }?>
<?php }else{ ?>
    <?php echo $_smarty_tpl->getSubTemplate ('list-thumbs.html', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('sorting'=>!empty($_smarty_tpl->tpl_vars['category']->value['params']['enable_sorting'])), 0);?>

    
<?php }?>
</div>
<!-- description -->
<?php if ($_smarty_tpl->tpl_vars['category']->value['description']){?>
    <p><?php echo $_smarty_tpl->tpl_vars['category']->value['description'];?>
</p>
<?php }?>

<div class="clear-both"></div><?php }} ?>