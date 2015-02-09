<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:48:37
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/themes/default/home.html" */ ?>
<?php /*%%SmartyHeaderCode:98892954654d8aca536ede9-92861783%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'f10f4a831fbdd2ea32d8c9173199e322a1e6a17e' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/themes/default/home.html',
      1 => 1416918753,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '98892954654d8aca536ede9-92861783',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa' => 0,
    'theme_settings' => 0,
    'promos' => 0,
    'bestsellers' => 0,
    'wa_backend_url' => 0,
    'criteria' => 0,
    'size' => 0,
    'offset' => 0,
    'limit' => 0,
    'slider_photos' => 0,
    'photo' => 0,
    'url' => 0,
    'product_id' => 0,
    '_photos' => 0,
    'b' => 0,
    'badge_html' => 0,
    'wa_theme_url' => 0,
    'frontend_homepage' => 0,
    '_' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8aca54fe5a9_05386290',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8aca54fe5a9_05386290')) {function content_54d8aca54fe5a9_05386290($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_truncate')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.truncate.php';
?>
<?php $_smarty_tpl->tpl_vars['promos'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->productSet('promo'), null, 0);?>
<?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bxslider_mode']!='photos'){?>
    <?php $_smarty_tpl->tpl_vars['bestsellers'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->productSet('bestsellers'), null, 0);?>
<?php }?>

<?php if (($_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bxslider_mode']!='photos'&&empty($_smarty_tpl->tpl_vars['promos']->value)&&empty($_smarty_tpl->tpl_vars['bestsellers']->value))){?>

    <article class="welcome">
        <h1>Добро пожаловать в ваш новый интернет-магазин!</h1>
        <p><?php echo sprintf('Начните с <a href="%s">создания товара</a> в бекенде интернет-магазина.',($_smarty_tpl->tpl_vars['wa_backend_url']->value).('shop/?action=products#/welcome/'));?>
</p>
        <style>
            .page-content.with-sidebar { margin-left: 0; border-left: 0; }
        </style>
    </article>

<?php }else{ ?>

    <!-- HOMEPAGE SLIDER -->
    <article class="bestsellers<?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bxslider_mode']=='photos'||$_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bxslider_mode']=='products_last_photo_as_background'){?> fill-entire-area<?php }?>">
    
        <?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bxslider_mode']=='photos'){?>
        
            
            
            <?php if ($_smarty_tpl->tpl_vars['wa']->value->photos){?>
            
                
                <?php $_smarty_tpl->tpl_vars['criteria'] = new Smarty_variable('', null, 0);?>
                <?php $_smarty_tpl->tpl_vars['size'] = new Smarty_variable('970', null, 0);?>
                <?php $_smarty_tpl->tpl_vars['offset'] = new Smarty_variable(null, null, 0);?>
                <?php $_smarty_tpl->tpl_vars['limit'] = new Smarty_variable(10, null, 0);?>
                <?php if (!isset($_smarty_tpl->tpl_vars['slider_photos'])) $_smarty_tpl->tpl_vars['slider_photos'] = new Smarty_Variable(null);if ($_smarty_tpl->tpl_vars['slider_photos']->value = $_smarty_tpl->tpl_vars['wa']->value->photos->photos($_smarty_tpl->tpl_vars['criteria']->value,$_smarty_tpl->tpl_vars['size']->value,$_smarty_tpl->tpl_vars['offset']->value,$_smarty_tpl->tpl_vars['limit']->value)){?>
                
                    <ul class="homepage-bxslider">
                        <?php  $_smarty_tpl->tpl_vars['photo'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['photo']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['slider_photos']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['photo']->key => $_smarty_tpl->tpl_vars['photo']->value){
$_smarty_tpl->tpl_vars['photo']->_loop = true;
?>
                            <li style="background-image: url('<?php echo $_smarty_tpl->tpl_vars['photo']->value[('thumb_').($_smarty_tpl->tpl_vars['size']->value)]['url'];?>
'); height: <?php echo $_smarty_tpl->tpl_vars['photo']->value[('thumb_').($_smarty_tpl->tpl_vars['size']->value)]['size']['height'];?>
px;" >
                                <?php $_smarty_tpl->tpl_vars['url'] = new Smarty_variable('', null, 0);?>
                                
                                <?php if ($_smarty_tpl->tpl_vars['url']->value){?><a href="<?php echo $_smarty_tpl->tpl_vars['url']->value;?>
" style="height: <?php echo $_smarty_tpl->tpl_vars['photo']->value[('thumb_').($_smarty_tpl->tpl_vars['size']->value)]['size']['height']-70;?>
px;"></a><?php }?>
                            </li>
                        <?php } ?>
                    </ul>
                
                <?php }else{ ?>
                    <p class="hint align-center"><br><em>Приложение «Фото» вернуло пустой список фотографий, так что в слайдере показать нечего.</em></p>
                <?php }?>
            
            <?php }else{ ?>
            
                <p class="hint align-center"><br><em>Установите приложение «Фото», чтобы в слайдере появились последние загруженные фотографии.</em></p>
            
            <?php }?>
        
        <?php }else{ ?>
        
            
            
            <?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bxslider_mode']=='products_last_photo_as_background'){?>
            
                <?php if ($_smarty_tpl->tpl_vars['wa']->value->shop->config('enable_2x')){?>
                    <?php $_smarty_tpl->tpl_vars['size'] = new Smarty_variable('970@2x', null, 0);?>
                <?php }else{ ?>
                    <?php $_smarty_tpl->tpl_vars['size'] = new Smarty_variable('970', null, 0);?>
                <?php }?>
                
                <?php $_smarty_tpl->tpl_vars['slider_photos'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->images(array_keys($_smarty_tpl->tpl_vars['bestsellers']->value),$_smarty_tpl->tpl_vars['size']->value), null, 0);?>
                
                <?php  $_smarty_tpl->tpl_vars['_photos'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_photos']->_loop = false;
 $_smarty_tpl->tpl_vars['product_id'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['slider_photos']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_photos']->key => $_smarty_tpl->tpl_vars['_photos']->value){
$_smarty_tpl->tpl_vars['_photos']->_loop = true;
 $_smarty_tpl->tpl_vars['product_id']->value = $_smarty_tpl->tpl_vars['_photos']->key;
?>
                    <?php $_smarty_tpl->createLocalArrayVariable('slider_photos', null, 0);
$_smarty_tpl->tpl_vars['slider_photos']->value[$_smarty_tpl->tpl_vars['product_id']->value] = end($_smarty_tpl->tpl_vars['_photos']->value);?>
                <?php } ?>

            <?php }?>
            
            <?php if ($_smarty_tpl->tpl_vars['bestsellers']->value&&count($_smarty_tpl->tpl_vars['bestsellers']->value)){?>
                <ul class="homepage-bxslider">
                    <?php  $_smarty_tpl->tpl_vars['b'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['b']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['bestsellers']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['b']->key => $_smarty_tpl->tpl_vars['b']->value){
$_smarty_tpl->tpl_vars['b']->_loop = true;
?>
                        <li itemscope itemtype ="http://schema.org/Product"<?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bxslider_mode']=='products_last_photo_as_background'){?> style="background-image: url('<?php echo $_smarty_tpl->tpl_vars['slider_photos']->value[$_smarty_tpl->tpl_vars['b']->value['id']][('url_').($_smarty_tpl->tpl_vars['size']->value)];?>
'); height: auto;"<?php }?>>
                            <a href="<?php echo $_smarty_tpl->tpl_vars['b']->value['frontend_url'];?>
" title="<?php echo $_smarty_tpl->tpl_vars['b']->value['name'];?>
<?php if ($_smarty_tpl->tpl_vars['b']->value['summary']){?> &ndash; <?php echo htmlspecialchars(strip_tags($_smarty_tpl->tpl_vars['b']->value['summary']), ENT_QUOTES, 'UTF-8', true);?>
<?php }?>">
                                <h2 itemprop="name">
                                    <span class="name"><?php echo $_smarty_tpl->tpl_vars['b']->value['name'];?>
</span>
                                </h2>
                                
                                <div class="image">
                                        <?php $_smarty_tpl->tpl_vars['badge_html'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->badgeHtml($_smarty_tpl->tpl_vars['b']->value['badge']), null, 0);?>
                                        <?php if ($_smarty_tpl->tpl_vars['badge_html']->value){?>
                                            <div class="corner top right"><?php echo $_smarty_tpl->tpl_vars['badge_html']->value;?>
</div>
                                        <?php }?>
                                        <?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bxslider_mode']=='products'){?>
                                            <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productImgHtml($_smarty_tpl->tpl_vars['b']->value,'0x320',array('itemprop'=>'image','alt'=>$_smarty_tpl->tpl_vars['b']->value['name'],'default'=>((string)$_smarty_tpl->tpl_vars['wa_theme_url']->value)."img/dummy200.png"));?>

                                        <?php }?>
                                </div>
                                <div itemprop="offers" class="info" itemscope itemtype="http://schema.org/Offer">
                                    <?php if ($_smarty_tpl->tpl_vars['b']->value['compare_price']>0){?><span class="compare-at-price nowrap"> <?php echo shop_currency_html($_smarty_tpl->tpl_vars['b']->value['compare_price']);?>
 </span><?php }?> <span class="price nowrap"><?php echo shop_currency_html($_smarty_tpl->tpl_vars['b']->value['price']);?>
</span>
                                    <meta itemprop="price" content="<?php echo $_smarty_tpl->tpl_vars['b']->value['price'];?>
">
                                    <meta itemprop="priceCurrency" content="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->primaryCurrency();?>
">
                                </div>
                                <?php if ($_smarty_tpl->tpl_vars['b']->value['summary']){?><p itemprop="description"><?php echo smarty_modifier_truncate(strip_tags($_smarty_tpl->tpl_vars['b']->value['summary']),255);?>
</p><?php }?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            <?php }else{ ?>
                <p class="hint align-center"><br><em><?php echo sprintf('Список товаров с идентификатором <strong>%s</strong> либо не существует, либо не содержит товаров. Чтобы отобразить здесь товары, добавьте их в список с таким идентификатором.','bestsellers');?>
</em><br><br></p>
            <?php }?>
            
        <?php }?>
    </article>

<?php }?>

<!-- plugin hook: 'frontend_homepage' -->

<?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['frontend_homepage']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo $_smarty_tpl->tpl_vars['_']->value;?>
<?php } ?>

<!-- BULLETS -->
<section class="bullets">
    <figure class="bullet">
        <h4><span class="b-glyph b-shipping"></span> <?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bullet_title_1'];?>
</h4>
        <p><?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bullet_body_1'];?>
</p>
    </figure>
    <figure class="bullet">
        <h4><span class="b-glyph b-payment"></span> <?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bullet_title_2'];?>
</h4>
        <p><?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bullet_body_2'];?>
</p>
    </figure>
    <figure class="bullet">
        <h4><span class="b-glyph b-location"></span> <?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bullet_title_3'];?>
</h4>
        <p><?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['homepage_bullet_body_3'];?>
</p>
    </figure>
</section>

<!-- PROMOS product list -->
<?php if ($_smarty_tpl->tpl_vars['promos']->value){?>
    <?php echo $_smarty_tpl->getSubTemplate ("list-thumbs.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('products'=>$_smarty_tpl->tpl_vars['promos']->value,'disable_compare'=>true), 0);?>

<?php }else{ ?>
    <p class="hint align-center"><em><?php echo sprintf('Перетащите несколько товаров в список <strong>%s</strong> в бекенде вашего интернет-магазина (список находится в левой колонке в разделе «Товары»), и эти товары будут автоматически опубликованы здесь на витрине вашего магазина.','promo');?>
</em></p>
<?php }?>

<!-- DISCOUNT OFFER -->
<aside class="coupon">
    <div class="scissors"></div>
    <h4><?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['homepage_promo_title'];?>
</h4>
    <p><?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['homepage_promo_body'];?>
</p>
</aside><?php }} ?>