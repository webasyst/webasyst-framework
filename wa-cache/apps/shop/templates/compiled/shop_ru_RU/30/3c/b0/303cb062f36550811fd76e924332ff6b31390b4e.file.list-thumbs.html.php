<?php /* Smarty version Smarty-3.1.14, created on 2015-03-17 23:29:17
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/list-thumbs.html" */ ?>
<?php /*%%SmartyHeaderCode:191007427455088e9debe477-41856953%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '303cb062f36550811fd76e924332ff6b31390b4e' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/list-thumbs.html',
      1 => 1423560309,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '191007427455088e9debe477-41856953',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'sorting' => 0,
    'active_sort' => 0,
    'wa' => 0,
    'category' => 0,
    'sort_fields' => 0,
    'sort' => 0,
    'name' => 0,
    'products' => 0,
    'p' => 0,
    'filter' => 0,
    'available' => 0,
    'sizeAvailable' => 0,
    'badge_html' => 0,
    'wa_theme_url' => 0,
    'pages_count' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_55088e9e108791_99548946',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_55088e9e108791_99548946')) {function content_55088e9e108791_99548946($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_truncate')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.truncate.php';
if (!is_callable('smarty_function_wa_pagination')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/function.wa_pagination.php';
?><!-- products thumbnail list view -->
<?php if (!empty($_smarty_tpl->tpl_vars['sorting']->value)){?>
    <!-- sorting -->
    <?php $_smarty_tpl->tpl_vars['sort_fields'] = new Smarty_variable(array('name'=>'Название','price'=>'Цена'), null, 0);?>
        
    <?php if (!isset($_smarty_tpl->tpl_vars['active_sort']->value)){?>
        <?php $_smarty_tpl->tpl_vars['active_sort'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->get('sort','create_datetime'), null, 0);?>
    <?php }?>
    <ul class="sorting">
        <li>Сортировать:</li>
        <?php if (!empty($_smarty_tpl->tpl_vars['category']->value)&&!$_smarty_tpl->tpl_vars['category']->value['sort_products']){?>
            <li<?php if (!$_smarty_tpl->tpl_vars['active_sort']->value){?> class="selected"<?php }?>><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->currentUrl(0,1);?>
">Новые и популярные</a></li>
        <?php }?>
        <?php  $_smarty_tpl->tpl_vars['name'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['name']->_loop = false;
 $_smarty_tpl->tpl_vars['sort'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['sort_fields']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['name']->key => $_smarty_tpl->tpl_vars['name']->value){
$_smarty_tpl->tpl_vars['name']->_loop = true;
 $_smarty_tpl->tpl_vars['sort']->value = $_smarty_tpl->tpl_vars['name']->key;
?>
            <li<?php if ($_smarty_tpl->tpl_vars['active_sort']->value==$_smarty_tpl->tpl_vars['sort']->value){?> class="selected"<?php }?>><?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->sortUrl($_smarty_tpl->tpl_vars['sort']->value,$_smarty_tpl->tpl_vars['name']->value);?>
</li>
            <?php if ($_smarty_tpl->tpl_vars['wa']->value->get('sort')==$_smarty_tpl->tpl_vars['sort']->value){?><?php echo $_smarty_tpl->tpl_vars['wa']->value->title((($_smarty_tpl->tpl_vars['wa']->value->title()).(' — ')).($_smarty_tpl->tpl_vars['name']->value));?>
<?php }?>
        <?php } ?>
    </ul>
<?php }?>



<ul class="thumbs product-list">
<?php  $_smarty_tpl->tpl_vars['p'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['p']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['products']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['p']->key => $_smarty_tpl->tpl_vars['p']->value){
$_smarty_tpl->tpl_vars['p']->_loop = true;
?>
    <?php $_smarty_tpl->tpl_vars['available'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->settings('ignore_stock_count')||$_smarty_tpl->tpl_vars['p']->value['count']===null||$_smarty_tpl->tpl_vars['p']->value['count']>0, null, 0);?>
    <?php $_smarty_tpl->tpl_vars['sizeAvailable'] = new Smarty_variable(shopSize::sizeAvailable($_smarty_tpl->tpl_vars['p']->value['id'],(array)$_smarty_tpl->tpl_vars['wa']->value->get($_smarty_tpl->tpl_vars['filter']->value['code'],array())), null, 0);?>
    <?php if ($_smarty_tpl->tpl_vars['available']->value&&$_smarty_tpl->tpl_vars['sizeAvailable']->value=='true'){?>
    
    <li itemscope itemtype ="http://schema.org/Product">

        <a href="<?php echo $_smarty_tpl->tpl_vars['p']->value['frontend_url'];?>
" title="<?php echo $_smarty_tpl->tpl_vars['p']->value['name'];?>
">
            <div class="image">
                <div class="badge-wrapper">
                    <?php $_smarty_tpl->tpl_vars['badge_html'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->badgeHtml($_smarty_tpl->tpl_vars['p']->value['badge']), null, 0);?>
                    <?php if ($_smarty_tpl->tpl_vars['badge_html']->value){?>
                        <div class="corner top right"><?php echo $_smarty_tpl->tpl_vars['badge_html']->value;?>
</div>
                    <?php }?>
                    <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productImgHtml($_smarty_tpl->tpl_vars['p']->value,'200',array('itemprop'=>'image','alt'=>$_smarty_tpl->tpl_vars['p']->value['name'],'default'=>((string)$_smarty_tpl->tpl_vars['wa_theme_url']->value)."img/dummy200.png"));?>

                </div>
            </div>                
            <h5 itemprop="name">
                <?php echo $_smarty_tpl->tpl_vars['p']->value['name'];?>

                <?php if ($_smarty_tpl->tpl_vars['p']->value['rating']>0){?>
                    <span class="rating nowrap"><?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->ratingHtml($_smarty_tpl->tpl_vars['p']->value['rating']);?>
</span>
                <?php }?>
            </h5>
            <?php if ($_smarty_tpl->tpl_vars['p']->value['summary']){?><span itemprop="description" class="summary"><?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['p']->value['summary'],100);?>
</span><?php }?>                
        </a>        
        <div itemprop="offers" class="offers" itemscope itemtype="http://schema.org/Offer">
            
                <form class="purchase addtocart" <?php if ($_smarty_tpl->tpl_vars['p']->value['sku_count']>1){?>data-url="<?php echo $_smarty_tpl->tpl_vars['p']->value['frontend_url'];?>
<?php if (strpos($_smarty_tpl->tpl_vars['p']->value['frontend_url'],'?')){?>&<?php }else{ ?>?<?php }?>cart=1"<?php }?> method="post" action="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('/frontendCart/add');?>
">
                    <?php if ($_smarty_tpl->tpl_vars['p']->value['compare_price']>0){?><span class="compare-at-price nowrap"> <?php echo shop_currency_html($_smarty_tpl->tpl_vars['p']->value['compare_price']);?>
 </span><?php }?>                
                    <span class="price nowrap" itemprop="price"><?php echo shop_currency_html($_smarty_tpl->tpl_vars['p']->value['price']);?>
</span>
                    <input type="hidden" name="product_id" value="<?php echo $_smarty_tpl->tpl_vars['p']->value['id'];?>
">
                    <input type="submit" value="В корзину">
                    <span class="added2cart" style="display: none;"><?php echo sprintf('%s теперь <a href="%s"><strong>в вашей корзине покупок</strong></a>',$_smarty_tpl->tpl_vars['p']->value['name'],$_smarty_tpl->tpl_vars['wa']->value->getUrl('shop/frontend/cart'));?>
</span>
                </form>
                <link itemprop="availability" href="http://schema.org/InStock" />
        <!--    -->
        </div>
    </li>
    <?php }?>
<?php } ?>
</ul>

<?php if (isset($_smarty_tpl->tpl_vars['pages_count']->value)&&$_smarty_tpl->tpl_vars['pages_count']->value>1){?>
<div class="block lazyloading-paging">
    <?php echo smarty_function_wa_pagination(array('total'=>$_smarty_tpl->tpl_vars['pages_count']->value,'attrs'=>array('class'=>"menu-h")),$_smarty_tpl);?>

</div>
<?php }?>
 <?php }} ?>