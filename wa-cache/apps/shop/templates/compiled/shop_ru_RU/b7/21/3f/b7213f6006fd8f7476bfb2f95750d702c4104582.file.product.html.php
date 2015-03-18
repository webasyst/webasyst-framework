<?php /* Smarty version Smarty-3.1.14, created on 2015-03-17 23:50:59
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/product.html" */ ?>
<?php /*%%SmartyHeaderCode:145593369550893b3888437-86448434%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'b7213f6006fd8f7476bfb2f95750d702c4104582' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/product.html',
      1 => 1424418263,
      2 => 'file',
    ),
    '717c7f12022c75546ffe4823b593405f8d999682' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/product.cart.html',
      1 => 1423488545,
      2 => 'file',
    ),
    '88286da27cefb4f9cb904d9aac4510816a6f5225' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/review.html',
      1 => 1423488545,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '145593369550893b3888437-86448434',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa_url' => 0,
    'wa_theme_url' => 0,
    'product' => 0,
    'wa' => 0,
    'reviews_total_count' => 0,
    'page' => 0,
    'frontend_product' => 0,
    '_' => 0,
    'compare' => 0,
    'image' => 0,
    'c' => 0,
    't' => 0,
    'rates' => 0,
    '_total_count' => 0,
    '_count' => 0,
    'i' => 0,
    'reviews' => 0,
    'review' => 0,
    'upselling' => 0,
    'crossselling' => 0,
    'compare_ids' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_550893b3ec3513_43072325',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_550893b3ec3513_43072325')) {function content_550893b3ec3513_43072325($_smarty_tpl) {?><script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery-plugins/jquery.cookie.js"></script>
<link href="<?php echo $_smarty_tpl->tpl_vars['wa_theme_url']->value;?>
swipebox/css/swipebox.min.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_theme_url']->value;?>
swipebox/js/jquery.swipebox.js"></script>

<article itemscope itemtype="http://schema.org/Product">
    
    <h1 itemprop="name">
        <?php echo htmlspecialchars($_smarty_tpl->tpl_vars['product']->value['name'], ENT_QUOTES, 'UTF-8', true);?>

        <?php if (!empty($_smarty_tpl->tpl_vars['product']->value['rating'])&&$_smarty_tpl->tpl_vars['product']->value['rating']>0){?>
            <span class="rating nowrap" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating" title="<?php echo sprintf('Средняя оценка покупателей: %s / 5',$_smarty_tpl->tpl_vars['product']->value['rating']);?>
">
                <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->ratingHtml($_smarty_tpl->tpl_vars['product']->value['rating'],16);?>

                <span itemprop="ratingValue" style="display: none;"><?php echo $_smarty_tpl->tpl_vars['product']->value['rating'];?>
</span>
                <span itemprop="reviewCount" style="display: none;"><?php echo $_smarty_tpl->tpl_vars['reviews_total_count']->value;?>
</span>
            </span>
        <?php }?>
    </h1>

    <!-- product internal nav -->
    <nav>
        <ul class="product-nav top-padded" role="navigation">
            <li class="selected"><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productUrl($_smarty_tpl->tpl_vars['product']->value);?>
">Обзор</a></li>
            <li>
                <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productUrl($_smarty_tpl->tpl_vars['product']->value,'reviews');?>
">Отзывы</a>
                <span class="hint"><?php echo $_smarty_tpl->tpl_vars['reviews_total_count']->value;?>
</span>
            </li>
            <?php  $_smarty_tpl->tpl_vars['page'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['page']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['product']->value['pages']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['page']->key => $_smarty_tpl->tpl_vars['page']->value){
$_smarty_tpl->tpl_vars['page']->_loop = true;
?>
                <li><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productUrl($_smarty_tpl->tpl_vars['product']->value,'page',array('page_url'=>$_smarty_tpl->tpl_vars['page']->value['url']));?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['page']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</a></li>
            <?php } ?>
            
            <!-- plugin hook: 'frontend_product.menu' -->
            
            <?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['frontend_product']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo $_smarty_tpl->tpl_vars['_']->value['menu'];?>
<?php } ?>
        
        </ul>
    </nav>

    <!-- purchase -->
    <aside class="product-sidebar">
    
        <div class="cart" id="cart-flyer">
        
            <?php /*  Call merged included template "product.cart.html" */
$_tpl_stack[] = $_smarty_tpl;
 $_smarty_tpl = $_smarty_tpl->setupInlineSubTemplate("product.cart.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0, '145593369550893b3888437-86448434');
content_550893b39437e8_79688531($_smarty_tpl);
$_smarty_tpl = array_pop($_tpl_stack); /*  End of included template "product.cart.html" */?>
            
            <!-- compare -->
            <div>
                <a <?php if ($_smarty_tpl->tpl_vars['compare']->value){?>style="display:none"<?php }?> class="compare-add inline-link" data-product="<?php echo $_smarty_tpl->tpl_vars['product']->value['id'];?>
" href="#"><b><i>Добавить к сравнению</i></b></a>
                <a <?php if (!$_smarty_tpl->tpl_vars['compare']->value){?>style="display:none"<?php }?> class="compare-remove inline-link" data-product="<?php echo $_smarty_tpl->tpl_vars['product']->value['id'];?>
" href="#"><b><i>Удалить из сравнения</i></b></a>
                <a id="compare-link" <?php if (count($_smarty_tpl->tpl_vars['compare']->value)<2){?>style="display:none"<?php }?> rel="nofollow" href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('/frontend/compare/',array('id'=>implode(',',$_smarty_tpl->tpl_vars['compare']->value)));?>
" class="bold">Сравнить <span class="count"><?php echo count($_smarty_tpl->tpl_vars['compare']->value);?>
</span></a>
            </div>
            
            <!-- plugin hook: 'frontend_product.cart' -->
            
            <?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['frontend_product']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo $_smarty_tpl->tpl_vars['_']->value['cart'];?>
<?php } ?>
            
        </div>
    
        <!-- plugin hook: 'frontend_product.block_aux' -->
        
        <?php if (!empty($_smarty_tpl->tpl_vars['frontend_product']->value)){?>
            <div class="aux">
                <?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['frontend_product']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo $_smarty_tpl->tpl_vars['_']->value['block_aux'];?>
<?php } ?>    
            </div>
        <?php }?>
        
    </aside>    
    
    <!-- product info & gallery -->
    <div class="product-info" id="overview">

        <?php if ($_smarty_tpl->tpl_vars['product']->value['images']){?>
        
            <section class="product-gallery">
            
                <!-- main image -->
                <div class="image" id="product-core-image">
                    <div class="corner top right">
                        <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->badgeHtml($_smarty_tpl->tpl_vars['product']->value['badge']);?>

                    </div>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productImgUrl($_smarty_tpl->tpl_vars['product']->value,'970');?>
">
                        <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productImgHtml($_smarty_tpl->tpl_vars['product']->value,'750',array('itemprop'=>'image','id'=>'product-image','alt'=>htmlspecialchars($_smarty_tpl->tpl_vars['product']->value['name'], ENT_QUOTES, 'UTF-8', true)));?>

                    </a>
                    <div id="switching-image" style="display: none;"></div>
                </div>
                
                <!-- thumbs -->
                <?php if (count($_smarty_tpl->tpl_vars['product']->value['images'])>1){?>
                    <div class="more-images" id="product-gallery">
                        <?php  $_smarty_tpl->tpl_vars['image'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['image']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['product']->value['images']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['image']->key => $_smarty_tpl->tpl_vars['image']->value){
$_smarty_tpl->tpl_vars['image']->_loop = true;
?>
                            <div class="image<?php if ($_smarty_tpl->tpl_vars['image']->value['id']==$_smarty_tpl->tpl_vars['product']->value['image_id']){?> selected<?php }?>">
                                <a id="product-image-<?php echo $_smarty_tpl->tpl_vars['image']->value['id'];?>
" href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productImgUrl(array('id'=>$_smarty_tpl->tpl_vars['product']->value['id'],'image_id'=>$_smarty_tpl->tpl_vars['image']->value['id'],'ext'=>$_smarty_tpl->tpl_vars['image']->value['ext']),'970');?>
" class="swipebox">
                                    <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productImgHtml(array('id'=>$_smarty_tpl->tpl_vars['product']->value['id'],'image_id'=>$_smarty_tpl->tpl_vars['image']->value['id'],'ext'=>$_smarty_tpl->tpl_vars['image']->value['ext'],'image_desc'=>$_smarty_tpl->tpl_vars['image']->value['description']),'96x96',array('alt'=>htmlspecialchars($_smarty_tpl->tpl_vars['image']->value['description'], ENT_QUOTES, 'UTF-8', true)));?>

                                </a>
                            </div>
                        <?php } ?>
                    </div>
                <?php }?>
                
            </section>
    
        <?php }?>

        <!-- plugin hook: 'frontend_product.block' -->
        
        <?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['frontend_product']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo $_smarty_tpl->tpl_vars['_']->value['block'];?>
<?php } ?>

        <?php if ($_smarty_tpl->tpl_vars['product']->value['description']){?>
            <section class="description" id="product-description" itemprop="description"><?php echo $_smarty_tpl->tpl_vars['product']->value['description'];?>
</section>
        <?php }?>

        <!-- product features -->
        

        <!-- categories -->
        <?php if ($_smarty_tpl->tpl_vars['product']->value['categories']){?>
            <p id="product-categories">
            Категории:
                <?php  $_smarty_tpl->tpl_vars['c'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['c']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['product']->value['categories']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['c']->key => $_smarty_tpl->tpl_vars['c']->value){
$_smarty_tpl->tpl_vars['c']->_loop = true;
?><?php if ($_smarty_tpl->tpl_vars['c']->value['status']){?>
                    <span class=""><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('/frontend/category',array('category_url'=>$_smarty_tpl->tpl_vars['c']->value['full_url']));?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['c']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</a></span>
                <?php }?><?php } ?>
            </p>
        <?php }?>

        <!-- tags -->
        <?php if ($_smarty_tpl->tpl_vars['product']->value['tags']){?>
            <p class="tags" id="product-tags">
                Теги:
                <?php  $_smarty_tpl->tpl_vars['t'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['t']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['product']->value['tags']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['t']->key => $_smarty_tpl->tpl_vars['t']->value){
$_smarty_tpl->tpl_vars['t']->_loop = true;
?>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('/frontend/tag',array('tag'=>urlencode($_smarty_tpl->tpl_vars['t']->value)));?>
"><?php echo $_smarty_tpl->tpl_vars['t']->value;?>
</a>
                <?php } ?>
            </p>
        <?php }?>
    

        <!-- product reviews -->
        <section class="reviews">
            <h2><?php echo sprintf('%s отзывы',htmlspecialchars($_smarty_tpl->tpl_vars['product']->value['name'], ENT_QUOTES, 'UTF-8', true));?>
</h2>
            
            <?php if (!empty($_smarty_tpl->tpl_vars['rates']->value)){?>
                <p class="rating">
                    Средняя оценка покупателей:
                    <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->ratingHtml($_smarty_tpl->tpl_vars['product']->value['rating'],16);?>
 (<a href="reviews/"><?php echo $_smarty_tpl->tpl_vars['reviews_total_count']->value;?>
</a>)
                    <?php if ($_smarty_tpl->tpl_vars['product']->value['rating']>0){?><span class="hint"><?php echo sprintf('%s из 5 звезд',$_smarty_tpl->tpl_vars['product']->value['rating']);?>
</span><?php }?>
                </p>
                
                <table class="rating-distribution">
                    <?php $_smarty_tpl->tpl_vars['_total_count'] = new Smarty_variable(0, null, 0);?>
                    <?php  $_smarty_tpl->tpl_vars['_count'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_count']->_loop = false;
 $_smarty_tpl->tpl_vars['_rate'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['rates']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_count']->key => $_smarty_tpl->tpl_vars['_count']->value){
$_smarty_tpl->tpl_vars['_count']->_loop = true;
 $_smarty_tpl->tpl_vars['_rate']->value = $_smarty_tpl->tpl_vars['_count']->key;
?>
                        <?php $_smarty_tpl->tpl_vars['_total_count'] = new Smarty_variable($_smarty_tpl->tpl_vars['_total_count']->value+$_smarty_tpl->tpl_vars['_count']->value, null, 0);?>
                    <?php } ?>
                    
                    <?php $_smarty_tpl->tpl_vars['i'] = new Smarty_Variable;$_smarty_tpl->tpl_vars['i']->step = -1;$_smarty_tpl->tpl_vars['i']->total = (int)ceil(($_smarty_tpl->tpl_vars['i']->step > 0 ? 0+1 - (5) : 5-(0)+1)/abs($_smarty_tpl->tpl_vars['i']->step));
if ($_smarty_tpl->tpl_vars['i']->total > 0){
for ($_smarty_tpl->tpl_vars['i']->value = 5, $_smarty_tpl->tpl_vars['i']->iteration = 1;$_smarty_tpl->tpl_vars['i']->iteration <= $_smarty_tpl->tpl_vars['i']->total;$_smarty_tpl->tpl_vars['i']->value += $_smarty_tpl->tpl_vars['i']->step, $_smarty_tpl->tpl_vars['i']->iteration++){
$_smarty_tpl->tpl_vars['i']->first = $_smarty_tpl->tpl_vars['i']->iteration == 1;$_smarty_tpl->tpl_vars['i']->last = $_smarty_tpl->tpl_vars['i']->iteration == $_smarty_tpl->tpl_vars['i']->total;?>
                        <?php if (empty($_smarty_tpl->tpl_vars['rates']->value[$_smarty_tpl->tpl_vars['i']->value])||!$_smarty_tpl->tpl_vars['rates']->value[$_smarty_tpl->tpl_vars['i']->value]){?><?php $_smarty_tpl->tpl_vars['_count'] = new Smarty_variable(0, null, 0);?><?php }else{ ?><?php $_smarty_tpl->tpl_vars['_count'] = new Smarty_variable($_smarty_tpl->tpl_vars['rates']->value[$_smarty_tpl->tpl_vars['i']->value], null, 0);?><?php }?>
                        <?php if ($_smarty_tpl->tpl_vars['i']->value||$_smarty_tpl->tpl_vars['_count']->value){?>
                            <tr>
                                <td class="min-width hint"><?php echo $_smarty_tpl->tpl_vars['_count']->value;?>
</td>
                                <td>
                                    <div class="bar">
                                        <div class="filling" style="width: <?php if ($_smarty_tpl->tpl_vars['_total_count']->value>0){?><?php echo str_replace(',','.',100*$_smarty_tpl->tpl_vars['_count']->value/$_smarty_tpl->tpl_vars['_total_count']->value);?>
<?php }else{ ?>0<?php }?>%;<?php if (!$_smarty_tpl->tpl_vars['i']->value){?> background: #ddd;<?php }?>"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="rating"><?php $_smarty_tpl->tpl_vars['j'] = new Smarty_Variable;$_smarty_tpl->tpl_vars['j']->step = 1;$_smarty_tpl->tpl_vars['j']->total = (int)ceil(($_smarty_tpl->tpl_vars['j']->step > 0 ? $_smarty_tpl->tpl_vars['i']->value+1 - (1) : 1-($_smarty_tpl->tpl_vars['i']->value)+1)/abs($_smarty_tpl->tpl_vars['j']->step));
if ($_smarty_tpl->tpl_vars['j']->total > 0){
for ($_smarty_tpl->tpl_vars['j']->value = 1, $_smarty_tpl->tpl_vars['j']->iteration = 1;$_smarty_tpl->tpl_vars['j']->iteration <= $_smarty_tpl->tpl_vars['j']->total;$_smarty_tpl->tpl_vars['j']->value += $_smarty_tpl->tpl_vars['j']->step, $_smarty_tpl->tpl_vars['j']->iteration++){
$_smarty_tpl->tpl_vars['j']->first = $_smarty_tpl->tpl_vars['j']->iteration == 1;$_smarty_tpl->tpl_vars['j']->last = $_smarty_tpl->tpl_vars['j']->iteration == $_smarty_tpl->tpl_vars['j']->total;?><i class="icon10 star"></i><?php }} else { ?><span class="hint">без оценки</span><?php }  ?></span>
                                </td>
                            </tr>
                        <?php }?>
                    <?php }} ?>
                </table>
            <?php }?>
            
            <ul>
                <?php  $_smarty_tpl->tpl_vars['review'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['review']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['reviews']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['review']->key => $_smarty_tpl->tpl_vars['review']->value){
$_smarty_tpl->tpl_vars['review']->_loop = true;
?>
                    <li data-id=<?php echo $_smarty_tpl->tpl_vars['review']->value['id'];?>
 data-parent-id="0">
                        <?php /*  Call merged included template "review.html" */
$_tpl_stack[] = $_smarty_tpl;
 $_smarty_tpl = $_smarty_tpl->setupInlineSubTemplate("review.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('reply_allowed'=>false), 0, '145593369550893b3888437-86448434');
content_550893b3db8b00_52873669($_smarty_tpl);
$_smarty_tpl = array_pop($_tpl_stack); /*  End of included template "review.html" */?>
                    </li>
                <?php } ?>
            </ul>
            <?php if (!$_smarty_tpl->tpl_vars['reviews']->value){?>
                <p><?php echo sprintf('Оставьте <a href="%s">отзыв об этом товаре</a> первым!','reviews/');?>
</p>
            <?php }else{ ?>
                <?php echo sprintf(_w('Read <a href="%s">all %d review</a> on %s','Read <a href="%s">all %d reviews</a> on %s',$_smarty_tpl->tpl_vars['reviews_total_count']->value,false),'reviews/',$_smarty_tpl->tpl_vars['reviews_total_count']->value,htmlspecialchars($_smarty_tpl->tpl_vars['product']->value['name'], ENT_QUOTES, 'UTF-8', true));?>

            <?php }?>
        </section>

    </div>

</article>


<!-- RELATED PRODUCTS -->
<?php $_smarty_tpl->tpl_vars['upselling'] = new Smarty_variable($_smarty_tpl->tpl_vars['product']->value->upSelling(12), null, 0);?>
<?php $_smarty_tpl->tpl_vars['crossselling'] = new Smarty_variable($_smarty_tpl->tpl_vars['product']->value->crossSelling(12), null, 0);?>

<?php if ($_smarty_tpl->tpl_vars['upselling']->value||$_smarty_tpl->tpl_vars['crossselling']->value){?>
    <div class="product-info">

        <?php if ($_smarty_tpl->tpl_vars['crossselling']->value){?>
            <figure class="related">
                <h3><?php echo sprintf('Покупатели, которые приобрели %s, также купили',htmlspecialchars($_smarty_tpl->tpl_vars['product']->value['name'], ENT_QUOTES, 'UTF-8', true));?>
</h3>
                <?php echo $_smarty_tpl->getSubTemplate ("list-thumbs-mini.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('products'=>$_smarty_tpl->tpl_vars['crossselling']->value,'ulclass'=>"related-bxslider"), 0);?>

            </figure>
        <?php }?>
        
        <?php if ($_smarty_tpl->tpl_vars['upselling']->value){?>
            <figure class="related">
                <h3>
                    Рекомендуем посмотреть
                    <?php $_smarty_tpl->tpl_vars['compare_ids'] = new Smarty_variable(array_merge(array($_smarty_tpl->tpl_vars['product']->value['id']),array_keys($_smarty_tpl->tpl_vars['upselling']->value)), null, 0);?>
                    <input type="button" onClick="javascript:window.location='<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('/frontend/compare',array('id'=>implode(',',$_smarty_tpl->tpl_vars['compare_ids']->value)));?>
';" value="Сравнить все" class="gray" />
                    
                </h3>
                <?php echo $_smarty_tpl->getSubTemplate ("list-thumbs-mini.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('products'=>$_smarty_tpl->tpl_vars['upselling']->value,'ulclass'=>"related-bxslider"), 0);?>

            </figure>
        <?php }?>  

    </div>
<?php }?>
<?php }} ?><?php /* Smarty version Smarty-3.1.14, created on 2015-03-17 23:50:59
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/product.cart.html" */ ?>
<?php if ($_valid && !is_callable('content_550893b39437e8_79688531')) {function content_550893b39437e8_79688531($_smarty_tpl) {?><form id="cart-form<?php if ($_smarty_tpl->tpl_vars['wa']->value->get('cart')){?>-dialog<?php }?>" method="post" action="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('/frontendCart/add');?>
">

    <h4><?php echo sprintf('Купить %s',htmlspecialchars($_smarty_tpl->tpl_vars['product']->value['name'], ENT_QUOTES, 'UTF-8', true));?>
</h4>
    

    <?php if ($_smarty_tpl->tpl_vars['product']->value['sku_type']){?>

        <!-- SELECTABLE FEATURES selling mode -->
        <?php $_smarty_tpl->tpl_vars['default_sku_features'] = new Smarty_variable($_smarty_tpl->tpl_vars['product']->value['sku_features'], null, 0);?>
        <?php $_smarty_tpl->tpl_vars['product_available'] = new Smarty_variable($_smarty_tpl->tpl_vars['product']->value['status'], null, 0);?>
       
        <?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['selectable_features_control']=='inline'){?>
            <div class="options">
                <?php  $_smarty_tpl->tpl_vars['f'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['f']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['features_selectable']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['f']->key => $_smarty_tpl->tpl_vars['f']->value){
$_smarty_tpl->tpl_vars['f']->_loop = true;
?>
                    <div class="inline-select<?php if ($_smarty_tpl->tpl_vars['f']->value['type']=='color'){?> color<?php }?>">
                    
                 <?php  $_smarty_tpl->tpl_vars['v'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['v']->_loop = false;
 $_smarty_tpl->tpl_vars['key'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['product']->value['features_selectable']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['v']->key => $_smarty_tpl->tpl_vars['v']->value){
$_smarty_tpl->tpl_vars['v']->_loop = true;
 $_smarty_tpl->tpl_vars['key']->value = $_smarty_tpl->tpl_vars['v']->key;
?>
                    <?php if ($_smarty_tpl->tpl_vars['key']->value=='29'){?>
                    <?php $_smarty_tpl->tpl_vars["feat"] = new Smarty_variable("29", null, 0);?>
                    <?php }?>
                 <?php } ?>
                    <?php if ($_smarty_tpl->tpl_vars['feat']->value=='29'){?>
                     <?php echo shopSize::sizeProductCard($_smarty_tpl->tpl_vars['f']->value,$_smarty_tpl->tpl_vars['product']->value);?>

                     <?php }else{ ?>
                       
               <?php  $_smarty_tpl->tpl_vars['v'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['v']->_loop = false;
 $_smarty_tpl->tpl_vars['v_id'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['f']->value['values']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['v']->key => $_smarty_tpl->tpl_vars['v']->value){
$_smarty_tpl->tpl_vars['v']->_loop = true;
 $_smarty_tpl->tpl_vars['v_id']->value = $_smarty_tpl->tpl_vars['v']->key;
?>

                <?php if (!isset($_smarty_tpl->tpl_vars['default_sku_features']->value[$_smarty_tpl->tpl_vars['f']->value['id']])){?><?php $_smarty_tpl->createLocalArrayVariable('default_sku_features', null, 0);
$_smarty_tpl->tpl_vars['default_sku_features']->value[$_smarty_tpl->tpl_vars['f']->value['id']] = $_smarty_tpl->tpl_vars['v_id']->value;?><?php }?>
                <a data-value="<?php echo $_smarty_tpl->tpl_vars['v_id']->value;?>
" href="#"<?php if ($_smarty_tpl->tpl_vars['v_id']->value==ifset($_smarty_tpl->tpl_vars['default_sku_features']->value[$_smarty_tpl->tpl_vars['f']->value['id']])){?> class="selected"<?php }?><?php if ($_smarty_tpl->tpl_vars['f']->value['type']=='color'){?> style="<?php echo $_smarty_tpl->tpl_vars['v']->value->style;?>
; margin-bottom: 20px;"<?php }?>>
                    <?php if ($_smarty_tpl->tpl_vars['f']->value['type']=='color'){?>&nbsp;<span class="color_name"><?php echo strip_tags($_smarty_tpl->tpl_vars['v']->value);?>
</span><?php }else{ ?><?php echo $_smarty_tpl->tpl_vars['v']->value;?>
<?php }?>
                </a>
            <?php } ?>
            <?php }?>
                        <input type="hidden" data-feature-id="<?php echo $_smarty_tpl->tpl_vars['f']->value['id'];?>
" class="sku-feature" name="features[<?php echo $_smarty_tpl->tpl_vars['f']->value['id'];?>
]" value="<?php echo ifset($_smarty_tpl->tpl_vars['default_sku_features']->value[$_smarty_tpl->tpl_vars['f']->value['id']]);?>
">
                    </div>
                <?php } ?>
            </div>        
        <?php }else{ ?>
            <div class="options">
                <?php  $_smarty_tpl->tpl_vars['f'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['f']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['features_selectable']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['f']->key => $_smarty_tpl->tpl_vars['f']->value){
$_smarty_tpl->tpl_vars['f']->_loop = true;
?>
                    <?php echo $_smarty_tpl->tpl_vars['f']->value['name'];?>
:
                    <select data-feature-id="<?php echo $_smarty_tpl->tpl_vars['f']->value['id'];?>
" class="sku-feature" name="features[<?php echo $_smarty_tpl->tpl_vars['f']->value['id'];?>
]">
                        <?php  $_smarty_tpl->tpl_vars['v'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['v']->_loop = false;
 $_smarty_tpl->tpl_vars['v_id'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['f']->value['values']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['v']->key => $_smarty_tpl->tpl_vars['v']->value){
$_smarty_tpl->tpl_vars['v']->_loop = true;
 $_smarty_tpl->tpl_vars['v_id']->value = $_smarty_tpl->tpl_vars['v']->key;
?>
                        <option value="<?php echo $_smarty_tpl->tpl_vars['v_id']->value;?>
" <?php if ($_smarty_tpl->tpl_vars['v_id']->value==ifset($_smarty_tpl->tpl_vars['default_sku_features']->value[$_smarty_tpl->tpl_vars['f']->value['id']])){?>selected<?php }?>><?php echo $_smarty_tpl->tpl_vars['v']->value;?>
</option>
                        <?php } ?>
                    </select>
                    <br>
                <?php } ?>
            </div>
        <?php }?>
        
    <?php }else{ ?>

        <!-- FLAT SKU LIST selling mode -->
        <?php $_smarty_tpl->tpl_vars['product_available'] = new Smarty_variable(false, null, 0);?>
        <?php if (count($_smarty_tpl->tpl_vars['product']->value['skus'])>1){?>
    
            
        
            <ul class="skus">
                <?php  $_smarty_tpl->tpl_vars['sku'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['sku']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['product']->value['skus']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['sku']->key => $_smarty_tpl->tpl_vars['sku']->value){
$_smarty_tpl->tpl_vars['sku']->_loop = true;
?>
                <?php $_smarty_tpl->tpl_vars['sku_available'] = new Smarty_variable($_smarty_tpl->tpl_vars['product']->value['status']&&$_smarty_tpl->tpl_vars['sku']->value['available']&&($_smarty_tpl->tpl_vars['wa']->value->shop->settings('ignore_stock_count')||$_smarty_tpl->tpl_vars['sku']->value['count']===null||$_smarty_tpl->tpl_vars['sku']->value['count']>0), null, 0);?>
                <li itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                    <label<?php if (!$_smarty_tpl->tpl_vars['sku']->value['available']){?> class="disabled"<?php }?>>
                    <input name="sku_id" type="radio" value="<?php echo $_smarty_tpl->tpl_vars['sku']->value['id'];?>
"<?php if (!$_smarty_tpl->tpl_vars['sku']->value['available']){?> disabled="true"<?php }?><?php if (!$_smarty_tpl->tpl_vars['sku_available']->value){?>data-disabled="1"<?php }?><?php if ($_smarty_tpl->tpl_vars['sku']->value['id']==$_smarty_tpl->tpl_vars['product']->value['sku_id']){?> checked="checked"<?php }?> data-compare-price="<?php echo shop_currency($_smarty_tpl->tpl_vars['sku']->value['compare_price'],$_smarty_tpl->tpl_vars['product']->value['currency'],null,0);?>
" data-price="<?php echo shop_currency($_smarty_tpl->tpl_vars['sku']->value['price'],$_smarty_tpl->tpl_vars['product']->value['currency'],null,0);?>
"<?php if ($_smarty_tpl->tpl_vars['sku']->value['image_id']){?> data-image-id="<?php echo $_smarty_tpl->tpl_vars['sku']->value['image_id'];?>
"<?php }?>> <span itemprop="name"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['sku']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</span>
                    <?php if ($_smarty_tpl->tpl_vars['sku']->value['sku']){?><span class="hint" itemprop="name"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['sku']->value['sku'], ENT_QUOTES, 'UTF-8', true);?>
</span><?php }?>
                    <meta itemprop="price" content="<?php echo shop_currency($_smarty_tpl->tpl_vars['sku']->value['price'],$_smarty_tpl->tpl_vars['product']->value['currency']);?>
">
                    <span class="price tiny nowrap"><?php echo shop_currency_html($_smarty_tpl->tpl_vars['sku']->value['price'],$_smarty_tpl->tpl_vars['product']->value['currency']);?>
</span>
                    <?php if ((!($_smarty_tpl->tpl_vars['sku']->value['count']===null)&&$_smarty_tpl->tpl_vars['sku']->value['count']<=0)){?>
                    <link itemprop="availability" href="http://schema.org/OutOfStock" />
                    <?php }else{ ?>
                    <link itemprop="availability" href="http://schema.org/InStock" />
                    <?php }?>
                    </label>
                </li>
                <?php $_smarty_tpl->tpl_vars['product_available'] = new Smarty_variable($_smarty_tpl->tpl_vars['product_available']->value||$_smarty_tpl->tpl_vars['sku_available']->value, null, 0);?>
                <?php } ?>
            </ul>

        <?php }else{ ?>
    
            
        
            <div itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                <?php $_smarty_tpl->tpl_vars['sku'] = new Smarty_variable($_smarty_tpl->tpl_vars['product']->value['skus'][$_smarty_tpl->tpl_vars['product']->value['sku_id']], null, 0);?>
                <?php if ($_smarty_tpl->tpl_vars['sku']->value['sku']){?><span class="hint" itemprop="name"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['sku']->value['sku'], ENT_QUOTES, 'UTF-8', true);?>
</span><?php }?>
                <meta itemprop="price" content="<?php echo shop_currency($_smarty_tpl->tpl_vars['sku']->value['price'],$_smarty_tpl->tpl_vars['product']->value['currency']);?>
">
                <?php if (!$_smarty_tpl->tpl_vars['sku']->value['available']){?>
                <link itemprop="availability" href="http://schema.org/Discontinued" />
                <p><em class="bold error">Этот товар временно недоступен для заказа</em></p>
                <?php }elseif(!$_smarty_tpl->tpl_vars['wa']->value->shop->settings('ignore_stock_count')&&!($_smarty_tpl->tpl_vars['sku']->value['count']===null||$_smarty_tpl->tpl_vars['sku']->value['count']>0)){?>
                <link itemprop="availability" href="http://schema.org/OutOfStock" />
                <div class="stocks"><strong class="stock-none"><i class="icon16 stock-transparent"></i><?php if ($_smarty_tpl->tpl_vars['wa']->value->shop->settings('ignore_stock_count')){?>Под заказ<?php }else{ ?>Нет в наличии<?php }?></strong></div>
                <?php }else{ ?>
                <link itemprop="availability" href="http://schema.org/InStock" />
                <?php }?>
                <input name="sku_id" type="hidden" value="<?php echo $_smarty_tpl->tpl_vars['product']->value['sku_id'];?>
">
                <?php $_smarty_tpl->tpl_vars['product_available'] = new Smarty_variable($_smarty_tpl->tpl_vars['product']->value['status']&&$_smarty_tpl->tpl_vars['sku']->value['available']&&($_smarty_tpl->tpl_vars['wa']->value->shop->settings('ignore_stock_count')||$_smarty_tpl->tpl_vars['sku']->value['count']===null||$_smarty_tpl->tpl_vars['sku']->value['count']>0), null, 0);?>
            </div>
    
        <?php }?>

    <?php }?>

    <!-- stock info -->
 <!--    -->

    <div class="purchase">

        <?php if ($_smarty_tpl->tpl_vars['services']->value){?>
        <!-- services -->
        <div class="services">
            <?php  $_smarty_tpl->tpl_vars['s'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['s']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['services']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['s']->key => $_smarty_tpl->tpl_vars['s']->value){
$_smarty_tpl->tpl_vars['s']->_loop = true;
?>
            <div class="service-<?php echo $_smarty_tpl->tpl_vars['s']->value['id'];?>
">
                <label>
                    <input data-price="<?php echo shop_currency($_smarty_tpl->tpl_vars['s']->value['price'],$_smarty_tpl->tpl_vars['s']->value['currency'],null,0);?>
" <?php if (!$_smarty_tpl->tpl_vars['product_available']->value){?>disabled="disabled"<?php }?> type="checkbox" name="services[]" value="<?php echo $_smarty_tpl->tpl_vars['s']->value['id'];?>
"> <?php echo htmlspecialchars($_smarty_tpl->tpl_vars['s']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
 <?php if ($_smarty_tpl->tpl_vars['s']->value['price']&&!isset($_smarty_tpl->tpl_vars['s']->value['variants'])){?>(+<span class="service-price"><?php echo shop_currency_html($_smarty_tpl->tpl_vars['s']->value['price'],$_smarty_tpl->tpl_vars['s']->value['currency']);?>
</span>)<?php }?>
                </label>
                <?php if (isset($_smarty_tpl->tpl_vars['s']->value['variants'])){?>
                <select data-variant-id="<?php echo $_smarty_tpl->tpl_vars['s']->value['variant_id'];?>
" class="service-variants" name="service_variant[<?php echo $_smarty_tpl->tpl_vars['s']->value['id'];?>
]" disabled>
                    <?php  $_smarty_tpl->tpl_vars['v'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['v']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['s']->value['variants']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['v']->key => $_smarty_tpl->tpl_vars['v']->value){
$_smarty_tpl->tpl_vars['v']->_loop = true;
?>
                    <option <?php if ($_smarty_tpl->tpl_vars['s']->value['variant_id']==$_smarty_tpl->tpl_vars['v']->value['id']){?>selected<?php }?> data-price="<?php echo shop_currency($_smarty_tpl->tpl_vars['v']->value['price'],$_smarty_tpl->tpl_vars['s']->value['currency'],null,0);?>
" value="<?php echo $_smarty_tpl->tpl_vars['v']->value['id'];?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['v']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
 (+<?php echo shop_currency($_smarty_tpl->tpl_vars['v']->value['price'],$_smarty_tpl->tpl_vars['s']->value['currency']);?>
)</option>
                    <?php } ?>
                </select>
                <?php }else{ ?>
                <input type="hidden" name="service_variant[<?php echo $_smarty_tpl->tpl_vars['s']->value['id'];?>
]" value="<?php echo $_smarty_tpl->tpl_vars['s']->value['variant_id'];?>
">
                <?php }?>
            </div>
            <?php } ?>
        </div>
        <?php }?>

        <!-- price -->
        <div class="add2cart">
            <?php if ($_smarty_tpl->tpl_vars['product']->value['compare_price']>0){?><span class="compare-at-price nowrap"> <?php echo shop_currency_html($_smarty_tpl->tpl_vars['product']->value['compare_price']);?>
 </span><?php }?>
            <span data-price="<?php echo shop_currency($_smarty_tpl->tpl_vars['product']->value['price'],null,null,0);?>
" class="price nowrap"><?php echo shop_currency_html($_smarty_tpl->tpl_vars['product']->value['price']);?>
</span>
            <input type="hidden" name="product_id" value="<?php echo $_smarty_tpl->tpl_vars['product']->value['id'];?>
">
                        <span class="qty">
                            &times; <input type="text" name="quantity" value="1">
                        </span>
            <input style="display: block; margin-right: auto; margin-left: auto; margin-top: 20px;" type="submit" <?php if (!$_smarty_tpl->tpl_vars['product_available']->value){?>disabled="disabled"<?php }?> value="В корзину">
            <div style="clear: both;"></div>
            <span class="added2cart" style="display: none;"><?php echo sprintf('%s теперь <a href="%s"><strong>в вашей корзине покупок</strong></a>',htmlspecialchars($_smarty_tpl->tpl_vars['product']->value['name'], ENT_QUOTES, 'UTF-8', true),$_smarty_tpl->tpl_vars['wa']->value->getUrl('shop/frontend/cart'));?>
</span>
        </div>
    </div>
</form>

<script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_theme_url']->value;?>
product.js?v<?php echo $_smarty_tpl->tpl_vars['wa_theme_version']->value;?>
"></script>
<script type="text/javascript">
    $(function () {
        new Product('#cart-form<?php if ($_smarty_tpl->tpl_vars['wa']->value->get('cart')){?>-dialog<?php }?>', {
            currency: <?php echo json_encode($_smarty_tpl->tpl_vars['currency_info']->value);?>

            <?php if (count($_smarty_tpl->tpl_vars['product']->value['skus'])>1||$_smarty_tpl->tpl_vars['product']->value['sku_type']){?>
            ,services: <?php echo json_encode($_smarty_tpl->tpl_vars['sku_services']->value);?>

            <?php }?>
            <?php if ($_smarty_tpl->tpl_vars['product']->value['sku_type']){?>
            ,features: <?php echo json_encode($_smarty_tpl->tpl_vars['sku_features_selectable']->value);?>

            <?php }?>
        });
    });
</script><?php }} ?><?php /* Smarty version Smarty-3.1.14, created on 2015-03-17 23:50:59
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/review.html" */ ?>
<?php if ($_valid && !is_callable('content_550893b3db8b00_52873669')) {function content_550893b3db8b00_52873669($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_date_format')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.date_format.php';
if (!is_callable('smarty_modifier_wa_datetime')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/modifier.wa_datetime.php';
?>

<?php if (!empty($_smarty_tpl->tpl_vars['ajax_append']->value)){?><li data-id=<?php echo $_smarty_tpl->tpl_vars['review']->value['id'];?>
 data-parent-id="<?php echo $_smarty_tpl->tpl_vars['review']->value['parent_id'];?>
"><?php }?>

<figure class="review" itemprop="review" itemscope itemtype="http://schema.org/Review">
    <div class="summary">
        <h6>
            <?php if (!$_smarty_tpl->tpl_vars['review']->value['parent_id']&&!empty($_smarty_tpl->tpl_vars['review']->value['rate'])){?>
            <span itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">
                <?php $_smarty_tpl->tpl_vars['rate'] = new Smarty_variable(round($_smarty_tpl->tpl_vars['review']->value['rate']), null, 0);?>
                <meta itemprop="worstRating" content = "1">
                <meta itemprop="ratingValue" content="<?php echo $_smarty_tpl->tpl_vars['rate']->value;?>
">
                <meta itemprop="bestRating" content = "5">
                <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->ratingHtml($_smarty_tpl->tpl_vars['rate']->value);?>

            </span>
            <?php }?>
            <span itemprop="name"><?php if ($_smarty_tpl->tpl_vars['review']->value['title']){?><?php echo $_smarty_tpl->tpl_vars['review']->value['title'];?>
<?php }?></span>
        </h6>
         
        <?php if (empty($_smarty_tpl->tpl_vars['review']->value['site'])){?>
            <span class="username" itemprop="author"><?php echo $_smarty_tpl->tpl_vars['review']->value['author']['name'];?>
</span>
        <?php }else{ ?>
            <a href="<?php echo $_smarty_tpl->tpl_vars['review']->value['site'];?>
" class="username" itemprop="author"><?php echo $_smarty_tpl->tpl_vars['review']->value['author']['name'];?>
</a>
        <?php }?>
        
        <?php if (!empty($_smarty_tpl->tpl_vars['review']->value['author']['is_user'])){?>
            <span class="staff"><?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->settings('name');?>
</span>
        <?php }?>
        
        <meta itemprop="datePublished" content="<?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['review']->value['datetime'],'Y-m-d');?>
">
        <span class="date" title="<?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['review']->value['datetime']);?>
"><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['review']->value['datetime'],"humandatetime");?>
</span>
    </div>
    <?php if ($_smarty_tpl->tpl_vars['review']->value['text']){?>
        <p itemprop="description"><?php echo $_smarty_tpl->tpl_vars['review']->value['text'];?>
</p>
    <?php }?>
    
    <?php if ($_smarty_tpl->tpl_vars['reply_allowed']->value){?>
        <div class="actions">
            <a href="<?php if (isset($_smarty_tpl->tpl_vars['reply_link']->value)){?><?php echo $_smarty_tpl->tpl_vars['reply_link']->value;?>
<?php }else{ ?>#<?php }?>" class="review-reply inline-link"><b><i>ответить</i></b></a>
        </div>
    <?php }?>
</figure>

<?php if (!empty($_smarty_tpl->tpl_vars['ajax_append']->value)){?><ul class="reviews-branch"></ul></li><?php }?><?php }} ?>