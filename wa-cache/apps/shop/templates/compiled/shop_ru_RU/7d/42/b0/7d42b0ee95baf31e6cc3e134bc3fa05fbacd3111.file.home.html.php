<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 11:28:56
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/home.html" */ ?>
<?php /*%%SmartyHeaderCode:989811168550694489c50a5-58920095%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '7d42b0ee95baf31e6cc3e134bc3fa05fbacd3111' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/home.html',
      1 => 1423488544,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '989811168550694489c50a5-58920095',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa' => 0,
    'promos' => 0,
    'bestsellers' => 0,
    'wa_backend_url' => 0,
    'b' => 0,
    'badge_html' => 0,
    'wa_theme_url' => 0,
    'frontend_homepage' => 0,
    '_' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_55069448c241a5_71677577',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_55069448c241a5_71677577')) {function content_55069448c241a5_71677577($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_truncate')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.truncate.php';
?><!-- get products from predefined product lists 'promo' and 'bestsellers' -->
<?php $_smarty_tpl->tpl_vars['promos'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->productSet('promo'), null, 0);?>
<?php $_smarty_tpl->tpl_vars['bestsellers'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->productSet('bestsellers'), null, 0);?>

<?php if ((empty($_smarty_tpl->tpl_vars['promos']->value)&&empty($_smarty_tpl->tpl_vars['bestsellers']->value))){?>

    <article class="welcome">
        <h1>Добро пожаловать в ваш новый интернет-магазин!</h1>
        <p><?php echo sprintf('Начните с создания товара в <a href="%s">бекенде интернет-магазина</a>.',($_smarty_tpl->tpl_vars['wa_backend_url']->value).('shop/?action=products#/welcome/'));?>
</p>
    </article>

<?php }else{ ?>

    <!-- BESTSELLERS SLIDER -->
    <article class="bestsellers">
        <?php if ($_smarty_tpl->tpl_vars['bestsellers']->value&&count($_smarty_tpl->tpl_vars['bestsellers']->value)){?>
            <ul class="homepage-bxslider">
            <?php  $_smarty_tpl->tpl_vars['b'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['b']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['bestsellers']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['b']->key => $_smarty_tpl->tpl_vars['b']->value){
$_smarty_tpl->tpl_vars['b']->_loop = true;
?>
            <li itemscope itemtype ="http://schema.org/Product">
                <a href="<?php echo $_smarty_tpl->tpl_vars['b']->value['frontend_url'];?>
" title="<?php echo $_smarty_tpl->tpl_vars['b']->value['name'];?>
<?php if ($_smarty_tpl->tpl_vars['b']->value['summary']){?> &ndash; <?php echo htmlspecialchars(strip_tags($_smarty_tpl->tpl_vars['b']->value['summary']), ENT_QUOTES, 'UTF-8', true);?>
<?php }?>">
                    <div class="info">
                        <h2 itemprop="name">
                            <span class="name"><?php echo $_smarty_tpl->tpl_vars['b']->value['name'];?>
</span>
                        </h2>
                        <?php if ($_smarty_tpl->tpl_vars['b']->value['compare_price']>0){?><span class="compare-at-price nowrap"> <?php echo shop_currency_html($_smarty_tpl->tpl_vars['b']->value['compare_price']);?>
 </span><?php }?>
                        <p class="purchase"><span class="price nowrap"><?php echo shop_currency_html($_smarty_tpl->tpl_vars['b']->value['price']);?>
</span></p>
                        <?php if ($_smarty_tpl->tpl_vars['b']->value['summary']){?><p itemprop="description"><?php echo smarty_modifier_truncate(strip_tags($_smarty_tpl->tpl_vars['b']->value['summary']),255);?>
</p><?php }?>
                    </div>
                    <div class="image">
                            <?php $_smarty_tpl->tpl_vars['badge_html'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->badgeHtml($_smarty_tpl->tpl_vars['b']->value['badge']), null, 0);?>
                            <?php if ($_smarty_tpl->tpl_vars['badge_html']->value){?>
                                <div class="corner top right"><?php echo $_smarty_tpl->tpl_vars['badge_html']->value;?>
</div>
                            <?php }?>
                            <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productImgHtml($_smarty_tpl->tpl_vars['b']->value,'0x320',array('itemprop'=>'image','alt'=>$_smarty_tpl->tpl_vars['b']->value['name'],'default'=>((string)$_smarty_tpl->tpl_vars['wa_theme_url']->value)."img/dummy200.png"));?>

                    </div>
                </a>
            </li>
            <?php } ?>
            </ul>
        <?php }else{ ?>
            <p class="hint align-center"><br><em><?php echo sprintf('Список товаров с идентификатором <strong>%s</strong> либо не существует, либо не содержит товаров. Чтобы отобразить здесь товары, добавьте их в список с таким идентификатором.','promo');?>
</em><br><br></p>
        <?php }?>
    </article>
    
    <!-- plugin hook: 'frontend_homepage' -->
    
    <?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['frontend_homepage']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo $_smarty_tpl->tpl_vars['_']->value;?>
<?php } ?>
    
    <!-- BULLETS -->
    <!--<section class="bullets">
    
    
        <figure class="bullet">
            <h4><span class="b-glyph b-shipping"></span> Lorem Ipsum 1</h4>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed pharetra orci a lacus convallis pellentesque. Pellentesque quis dui sem. Proin nec tempus risus.</p>
        </figure>
    </section> -->
    
    <!-- PROMOS product list -->
    <?php if ($_smarty_tpl->tpl_vars['promos']->value){?>
        <?php echo $_smarty_tpl->getSubTemplate ("list-thumbs.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('products'=>$_smarty_tpl->tpl_vars['promos']->value), 0);?>

    <?php }else{ ?>
      <!--  <p class="hint align-center"><em><?php echo sprintf('Список товаров с идентификатором <strong>%s</strong> либо не существует, либо не содержит товаров. Чтобы отобразить здесь товары, добавьте их в список с таким идентификатором.','bestsellers');?>
</em></p> -->
    <?php }?>
    
<?php }?><?php }} ?>