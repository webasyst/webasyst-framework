<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 11:28:56
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/site/themes/default/index.html" */ ?>
<?php /*%%SmartyHeaderCode:26117188455069448ddfab2-05193223%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'f72d00a5c14292f532b60fcb479395c2f6354ebc' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/site/themes/default/index.html',
      1 => 1425023139,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '26117188455069448ddfab2-05193223',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa' => 0,
    'canonical' => 0,
    'rss' => 0,
    'wa_theme_url' => 0,
    'wa_theme_version' => 0,
    'wa_url' => 0,
    'wa_active_theme_path' => 0,
    'cart_total' => 0,
    'frontend_nav' => 0,
    'output' => 0,
    'shop_pages' => 0,
    'query' => 0,
    'filters' => 0,
    'theme_settings' => 0,
    'wa_parent_theme_url' => 0,
    'fid' => 0,
    'filter' => 0,
    'c' => 0,
    '_v' => 0,
    'v_id' => 0,
    'v' => 0,
    'category' => 0,
    'product' => 0,
    'selected_category' => 0,
    'brands' => 0,
    'b' => 0,
    'plugin' => 0,
    '_' => 0,
    'latest_posts' => 0,
    'post' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_550694492a5b37_39413889',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_550694492a5b37_39413889')) {function content_550694492a5b37_39413889($_smarty_tpl) {?><?php if (!is_callable('smarty_function_wa_print_tree')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/function.wa_print_tree.php';
if (!is_callable('smarty_modifier_wa_datetime')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/modifier.wa_datetime.php';
if (!is_callable('smarty_modifier_truncate')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.truncate.php';
?><!DOCTYPE html>
<html<?php if ($_smarty_tpl->tpl_vars['wa']->value->globals('isMyAccount')){?> class="my"<?php }?>><head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1<?php if ($_smarty_tpl->tpl_vars['wa']->value->isMobile()){?>, maximum-scale=1, user-scalable=0<?php }?>" />

        <title><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['wa']->value->title(), ENT_QUOTES, 'UTF-8', true);?>
</title>
        <meta name="Keywords" content="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['wa']->value->meta('keywords'), ENT_QUOTES, 'UTF-8', true);?>
" />
        <meta name="Description" content="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['wa']->value->meta('description'), ENT_QUOTES, 'UTF-8', true);?>
" />

        <?php if (!empty($_smarty_tpl->tpl_vars['canonical']->value)){?>
        <link rel="canonical" href="<?php echo $_smarty_tpl->tpl_vars['canonical']->value;?>
"/><?php }?>
        <link rel="shortcut icon" href="/favicon.ico"/>
        <?php if ($_smarty_tpl->tpl_vars['wa']->value->blog){?>
        <!-- rss -->
        <?php $_smarty_tpl->tpl_vars['rss'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->blog->rssUrl(), null, 0);?>
        <?php if ($_smarty_tpl->tpl_vars['rss']->value){?><link rel="alternate" type="application/rss+xml" title="<?php echo $_smarty_tpl->tpl_vars['wa']->value->accountName();?>
" href="<?php echo $_smarty_tpl->tpl_vars['rss']->value;?>
"><?php }?>
        <?php }?>

        <!-- fonts -->
        <link href='//fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700&amp;subset=latin,cyrillic' rel='stylesheet' type='text/css'>

        <!-- css -->
        <link href="<?php echo $_smarty_tpl->tpl_vars['wa_theme_url']->value;?>
default.css?v<?php echo $_smarty_tpl->tpl_vars['wa_theme_version']->value;?>
" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $_smarty_tpl->tpl_vars['wa_theme_url']->value;?>
waslidemenu/waslidemenu.css?v<?php echo $_smarty_tpl->tpl_vars['wa_theme_version']->value;?>
" rel="stylesheet" type="text/css"/>
        <?php if ($_smarty_tpl->tpl_vars['wa']->value->shop&&$_smarty_tpl->tpl_vars['wa']->value->shop->currency()=='RUB'){?> <link href="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/font/ruble/arial/fontface.css" rel="stylesheet" type="text/css"><?php }?>
        <?php echo $_smarty_tpl->tpl_vars['wa']->value->css();?>
 

        <!-- js -->
        <script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery/jquery-1.8.2.min.js"></script>
        
        <script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_theme_url']->value;?>
default.js?v<?php echo $_smarty_tpl->tpl_vars['wa_theme_version']->value;?>
"></script>
        <script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_theme_url']->value;?>
waslidemenu/jquery.waslidemenu.min.js?v<?php echo $_smarty_tpl->tpl_vars['wa_theme_version']->value;?>
"></script>

        <?php echo $_smarty_tpl->tpl_vars['wa']->value->js();?>
 

        <?php echo $_smarty_tpl->getSubTemplate (((string)$_smarty_tpl->tpl_vars['wa_active_theme_path']->value)."/head.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0);?>


        <?php echo $_smarty_tpl->tpl_vars['wa']->value->headJs();?>
 

        <!--[if lt IE 9]>
        <script>
        document.createElement('header');
        document.createElement('nav');
        document.createElement('section');
        document.createElement('article');
        document.createElement('aside');
        document.createElement('footer');
        document.createElement('figure');
        document.createElement('hgroup');
        document.createElement('menu');
        </script>
        <![endif]-->

    </head>

    <body>

        <div id="main">
            <div id="head">
                <div class="hills1">
                    <div class="hills2">
                        <div class="top_links">
                            <ul>
                                <li><?php if ($_smarty_tpl->tpl_vars['wa']->value->isAuthEnabled()){?>
                                    <ul class="auth">
                                        <?php if ($_smarty_tpl->tpl_vars['wa']->value->user()->isAuth()){?>
                                        <?php if ($_smarty_tpl->tpl_vars['wa']->value->shop){?>
                                        <li> <a href="/my/"><i class="icon16 userpic20 float-left" style="background-image: url('<?php echo $_smarty_tpl->tpl_vars['wa']->value->user()->getPhoto(20);?>
');"></i> Личный кабинет</a> </li>
                                        <?php }else{ ?>
                                        <li><strong><?php echo $_smarty_tpl->tpl_vars['wa']->value->user('name');?>
</strong></li>
                                        <?php }?>
                                        <li><a href="?logout">Выйти</a></li>
                                        <?php }else{ ?>
                                        <li><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->loginUrl();?>
">Вход</a></li>
                                        <li><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->signupUrl();?>
">Регистрация</a></li>
                                        <?php }?>
                                    </ul>
                                    <?php }?></li>
                                <li></li>
                            </ul>
                        </div>
                        <div class="logo"> <a href="/"><img src="/wa-data/public/site/themes/default/img/logo.png" alt="Интернет магазин детской одежды Пчелкин Дом" title="Интернет магазин детской одежды Пчелкин Дом"></a> </div>
                        <div class="plask">
                            <div class="left"> <span>ICQ&nbsp;&nbsp;&nbsp;
                                    <img src="http://status.icq.com/5/online1.gif" border=0 alt="" border=0>646834892
                                </span><br>
                                <span>Skype 
                                    <!--
                                                                    Skype 'My status' button
                                                                    http://www.skype.com/go/skypebuttons
                                    --> 
                                    <script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script> 
                                    <img src="http://mystatus.skype.com/smallicon/pchelkindom.ru" style="border: none;" width="16" height="16" alt=""><a href="skype:pchelkindom.ru?call">pchelkindom.ru</a> </span><br>
                                <span>E-mail <img src="/wa-data/public/site/themes/default/img/mail_logo.gif" alt=""><a href="mailto:zakaz@pchelkindom.ru">zakaz@pchelkindom.ru</a></span> </div>
                            <div class="right"><?php echo $_smarty_tpl->getSubTemplate (((string)$_smarty_tpl->tpl_vars['wa_active_theme_path']->value)."/head.contacts.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0);?>
</div>
                        </div>
                        <div class="bee1">&nbsp;</div>
                        <div class="bee2"><?php if ($_smarty_tpl->tpl_vars['wa']->value->shop){?> 
                            <!-- cart --> 
                            <?php $_smarty_tpl->tpl_vars['cart_total'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->cart->total(), null, 0);?>                         
                            <div id="cart" style="<?php if ($_smarty_tpl->tpl_vars['wa']->value->user()->isAuth()){?>top:20px;<?php }?>" class="cart<?php if (!$_smarty_tpl->tpl_vars['cart_total']->value){?> empty<?php }?>"> <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('shop/frontend/cart');?>
" class="cart-summary"> <b class="cartname"> Корзина:</b><br>
                                    <strong class="cart-total"><?php echo wa_currency_html($_smarty_tpl->tpl_vars['cart_total']->value,$_smarty_tpl->tpl_vars['wa']->value->shop->currency());?>
</strong> </a> <br>
                                <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('shop/frontend/checkout');?>
" class="cart-to-checkout" style="display: none; font-size:10px;"> Оформить заказ </a> 
                                <?php if ($_smarty_tpl->tpl_vars['wa']->value->user()->isAuth()){?>
                                    <!--<?php if ($_smarty_tpl->tpl_vars['cart_total']->value){?>
                                        Скидка: <strong>-<?php echo wa_currency_html($_smarty_tpl->tpl_vars['wa']->value->shop->cart->discount(),$_smarty_tpl->tpl_vars['wa']->value->shop->currency());?>
</strong>  
                                        
                                    <?php }?> -->
                                    Скидка: <strong><?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->cart->discountPercent();?>
%</strong> 
                                <?php }?>
                            </div>
                            <?php }?> </div>
                    </div>
                </div>
                <div id="navigation">
                    <div class="bg_left">
                        <div class="bg_right">
                            <div class="clear-both"></div>
                            <?php $_smarty_tpl->tpl_vars['_hook_frontend_sidebar_section'] = new Smarty_variable(false, null, 0);?>
                            <?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['frontend_nav']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?>
                            <?php if (!empty($_smarty_tpl->tpl_vars['output']->value['section'])){?>
                            <?php $_smarty_tpl->tpl_vars['_hook_frontend_sidebar_section'] = new Smarty_variable(true, null, 0);?>
                            <?php }?>
                            <?php } ?>        
                            <?php $_smarty_tpl->tpl_vars['shop_pages'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->pages(), null, 0);?>
                            <?php $_smarty_tpl->tpl_vars['cloud'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->tags(), null, 0);?>

                            <?php if ($_smarty_tpl->tpl_vars['shop_pages']->value){?> 
                            <!-- info pages -->
                            <ul class="menu-h" id="page-list">
                                <li> 
                                    <a href="/" title="Главная">Главная</a>
                                </li>
                                <li style="background:#000;; color:#fff !important;"> 
                                    <a href="/dostavka-oplata/" title="Доставка-оплата">Доставка-оплата</a>
                                </li>
                                <li>
                                    <a href="/kak-sdyelat-zakaz/" title="Как сделать заказ?">Как сделать заказ?</a>
                                </li>
                                <li style="background:#000;; color:#fff !important;"> 
                                    <a href="/tablitsa-razmyerov/" title="Таблица размеров">Таблица размеров</a>
                                </li>
                                <li >
                                    <a href="/obratnaya-svyaz/" title="Обратная связь">Контакты</a>
                                </li>
                            </ul>
                            
                            <?php }else{ ?>
                            <div class="clear-right"></div>
                            <?php }?>
                            <div class="search"> <?php if ($_smarty_tpl->tpl_vars['wa']->value->shop){?> 
                                <div class="cpt_product_search">

                                    <!-- product search -->
                                    <form method="get" action="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('shop/frontend/search');?>
" class="search">
                                        <input type="search" id="search" name="query" <?php if (!empty($_smarty_tpl->tpl_vars['query']->value)){?>value="<?php echo $_smarty_tpl->tpl_vars['query']->value;?>
"<?php }?> placeholder="Найти товары">
                                               <button type="submit"></button>
                                    </form>

                                </div>
                                <?php }?>
                                <div class="clear-both"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End of header -->
            <div class="content-container">
                <div id="pagecontent">
                    <div id="left">
                        <div><!-- filtering by product features -->    
                            <?php if (!empty($_smarty_tpl->tpl_vars['filters']->value)){?>
                            <h1>Подбор товара</h1>
                            <?php if (waRequest::isXMLHttpRequest()){?>

                            <link href="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/css/jquery-ui/base/jquery.ui.slider.css" rel="stylesheet" type="text/css">
                            <script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery-ui/jquery.ui.core.min.js?v<?php echo $_smarty_tpl->tpl_vars['wa']->value->version(true);?>
"></script>
                            <script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery-ui/jquery.ui.widget.min.js?v<?php echo $_smarty_tpl->tpl_vars['wa']->value->version(true);?>
"></script>
                            <script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery-ui/jquery.ui.mouse.min.js?v<?php echo $_smarty_tpl->tpl_vars['wa']->value->version(true);?>
"></script>
                            <script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery-ui/jquery.ui.slider.min.js?v<?php echo $_smarty_tpl->tpl_vars['wa']->value->version(true);?>
"></script>
                            <?php }?>

                            <div class="filters<?php if (!empty($_smarty_tpl->tpl_vars['theme_settings']->value['ajax_filters'])){?> ajax<?php }?>">
                                <form method="get" action="<?php echo $_smarty_tpl->tpl_vars['wa']->value->currentUrl(0,1);?>
" data-loading="<?php echo $_smarty_tpl->tpl_vars['wa_parent_theme_url']->value;?>
img/loading16.gif">
                                    <?php  $_smarty_tpl->tpl_vars['filter'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['filter']->_loop = false;
 $_smarty_tpl->tpl_vars['fid'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['filters']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['filter']->key => $_smarty_tpl->tpl_vars['filter']->value){
$_smarty_tpl->tpl_vars['filter']->_loop = true;
 $_smarty_tpl->tpl_vars['fid']->value = $_smarty_tpl->tpl_vars['filter']->key;
?>
                                    <div class="filter-param">
                                        <?php if ($_smarty_tpl->tpl_vars['fid']->value=='price'){?>
                                        <?php $_smarty_tpl->tpl_vars['c'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->currency(true), null, 0);?>
                                        <h5>Цена</h5>
                                        <div class="slider">
                                            от <input type="text" class="min" name="price_min" <?php if ($_smarty_tpl->tpl_vars['wa']->value->get('price_min')){?>value="<?php echo (int)$_smarty_tpl->tpl_vars['wa']->value->get('price_min');?>
"<?php }?> placeholder="<?php echo floor($_smarty_tpl->tpl_vars['filter']->value['min']);?>
">
                                            до <input type="text" class="max" name="price_max" <?php if ($_smarty_tpl->tpl_vars['wa']->value->get('price_max')){?>value="<?php echo (int)$_smarty_tpl->tpl_vars['wa']->value->get('price_max');?>
"<?php }?> placeholder="<?php echo ceil($_smarty_tpl->tpl_vars['filter']->value['max']);?>
"> <?php echo $_smarty_tpl->tpl_vars['c']->value['sign'];?>

                                        </div>
                                        <?php }else{ ?>
                                        <h5><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['filter']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</h5>
                                        <?php if ($_smarty_tpl->tpl_vars['filter']->value['type']=='boolean'){?>
                                        <label><input type="radio" name="<?php echo $_smarty_tpl->tpl_vars['filter']->value['code'];?>
"<?php if ($_smarty_tpl->tpl_vars['wa']->value->get($_smarty_tpl->tpl_vars['filter']->value['code'])){?> checked<?php }?> value="1"> Да</label>
                                        <label><input type="radio" name="<?php echo $_smarty_tpl->tpl_vars['filter']->value['code'];?>
"<?php if ($_smarty_tpl->tpl_vars['wa']->value->get($_smarty_tpl->tpl_vars['filter']->value['code'])==='0'){?> checked<?php }?> value="0"> Нет</label>
                                        <label><input type="radio" name="<?php echo $_smarty_tpl->tpl_vars['filter']->value['code'];?>
"<?php if ($_smarty_tpl->tpl_vars['wa']->value->get($_smarty_tpl->tpl_vars['filter']->value['code'],'')===''){?> checked<?php }?> value=""> Неважно</label>
                                        <?php }elseif(isset($_smarty_tpl->tpl_vars['filter']->value['min'])){?>
                                        <?php $_smarty_tpl->tpl_vars['_v'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->get($_smarty_tpl->tpl_vars['filter']->value['code']), null, 0);?>
                                        <div class="slider">
                                            от <input type="text" class="min" name="<?php echo $_smarty_tpl->tpl_vars['filter']->value['code'];?>
[min]" placeholder="<?php echo $_smarty_tpl->tpl_vars['filter']->value['min'];?>
" <?php if (!empty($_smarty_tpl->tpl_vars['_v']->value['min'])){?>value="<?php echo $_smarty_tpl->tpl_vars['_v']->value['min'];?>
"<?php }?>>
                                                            до <input type="text" class="max" name="<?php echo $_smarty_tpl->tpl_vars['filter']->value['code'];?>
[max]" placeholder="<?php echo $_smarty_tpl->tpl_vars['filter']->value['max'];?>
" <?php if (!empty($_smarty_tpl->tpl_vars['_v']->value['max'])){?>value="<?php echo $_smarty_tpl->tpl_vars['_v']->value['max'];?>
"<?php }?>>
                                                            <?php if (!empty($_smarty_tpl->tpl_vars['filter']->value['unit'])){?>
                                                            <?php echo $_smarty_tpl->tpl_vars['filter']->value['unit']['title'];?>

                                                            <?php if ($_smarty_tpl->tpl_vars['filter']->value['unit']['value']!=$_smarty_tpl->tpl_vars['filter']->value['base_unit']['value']){?><input type="hidden" name="<?php echo $_smarty_tpl->tpl_vars['filter']->value['code'];?>
[unit]" value="<?php echo $_smarty_tpl->tpl_vars['filter']->value['unit']['value'];?>
"><?php }?>
                                            <?php }?>
                                        </div>
                                        <?php }elseif($_smarty_tpl->tpl_vars['fid']->value=='29'){?>
                                        <?php echo shopSize::sizeFilter($_smarty_tpl->tpl_vars['filter']->value);?>

                                        <?php }else{ ?>
                                        <?php  $_smarty_tpl->tpl_vars['v'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['v']->_loop = false;
 $_smarty_tpl->tpl_vars['v_id'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['filter']->value['values']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['v']->key => $_smarty_tpl->tpl_vars['v']->value){
$_smarty_tpl->tpl_vars['v']->_loop = true;
 $_smarty_tpl->tpl_vars['v_id']->value = $_smarty_tpl->tpl_vars['v']->key;
?>
                                        <label>
                                            <input type="checkbox" name="<?php echo $_smarty_tpl->tpl_vars['filter']->value['code'];?>
[]" <?php if (in_array($_smarty_tpl->tpl_vars['v_id']->value,(array)$_smarty_tpl->tpl_vars['wa']->value->get($_smarty_tpl->tpl_vars['filter']->value['code'],array()))){?>checked<?php }?> value="<?php echo $_smarty_tpl->tpl_vars['v_id']->value;?>
"> <?php echo $_smarty_tpl->tpl_vars['v']->value;?>

                                        </label>
                                        <?php } ?>
                                        <?php }?>
                                        <?php }?>
                                    </div>            
                                    <?php } ?>
                                    <?php if ($_smarty_tpl->tpl_vars['wa']->value->get('sort')){?><input type="hidden" name="sort" value="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['wa']->value->get('sort'), ENT_QUOTES, 'UTF-8', true);?>
"><?php }?>
                                    <?php if ($_smarty_tpl->tpl_vars['wa']->value->get('order')){?><input type="hidden" name="order" value="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['wa']->value->get('order'), ENT_QUOTES, 'UTF-8', true);?>
"><?php }?>
                                    <input type="submit" class="gray" value="Показать">
                                </form>
                            </div>
                            <?php }?>  </div>
                        <div class="clear-both"></div>
                        <!-- categories -->
                        <div class=""> 
                            <?php if (isset($_smarty_tpl->tpl_vars['category']->value)){?><?php $_smarty_tpl->tpl_vars['selected_category'] = new Smarty_variable($_smarty_tpl->tpl_vars['category']->value['id'], null, 0);?><?php }elseif(isset($_smarty_tpl->tpl_vars['product']->value['category_id'])){?><?php $_smarty_tpl->tpl_vars['selected_category'] = new Smarty_variable($_smarty_tpl->tpl_vars['product']->value['category_id'], null, 0);?><?php }else{ ?><?php $_smarty_tpl->tpl_vars['selected_category'] = new Smarty_variable(null, null, 0);?>  <?php }?>

                            <?php echo smarty_function_wa_print_tree(array('tree'=>$_smarty_tpl->tpl_vars['wa']->value->shop->categories(0,null,true),'selected'=>$_smarty_tpl->tpl_vars['selected_category']->value,'unfolded'=>false,'class'=>"menu-v category-tree",'elem'=>'<a href=":url" title=":name">:name</a>'),$_smarty_tpl);?>


                        </div>
                        <div class="clear-both"></div>

                        <div class="brandblock">
                            <?php $_smarty_tpl->tpl_vars['brands'] = new Smarty_variable(shopProductbrandsPlugin::getBrands(), null, 0);?>
                            <topic>Бренды</topic>
                            <ul class="menu-v brands">
                                <?php  $_smarty_tpl->tpl_vars['b'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['b']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['brands']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['b']->key => $_smarty_tpl->tpl_vars['b']->value){
$_smarty_tpl->tpl_vars['b']->_loop = true;
?>
                                <li <?php if ($_smarty_tpl->tpl_vars['b']->value['name']==$_smarty_tpl->tpl_vars['wa']->value->param('brand')){?>class="selected"<?php }?>>
                                    <a href="<?php echo $_smarty_tpl->tpl_vars['b']->value['url'];?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['b']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</a>
                                </li>
                                <?php } ?>
                            </ul>
                            <br><a style="margin-left: 20px;" href="/shop/auxpage_all-brends/">Все бренды >>></a>
                        </div>
                        <!--                        <div>
                                                    <?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['frontend_nav']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['_']->key;
?>
                                                    <?php if ($_smarty_tpl->tpl_vars['plugin']->value=='productbrands-plugin'&&!empty($_smarty_tpl->tpl_vars['_']->value)){?>
                                                    <div class="brandblock">
                                                        <topic>Бренды</topic>
                                                        <?php echo $_smarty_tpl->tpl_vars['_']->value;?>

                                                        <br><a style="margin-left: 20px;" href="/shop/auxpage_all-brends/">Все бренды >>></a>
                                                    </div>
                                                    <?php }?>
                                                    <?php } ?>
                                                </div>-->

                        <div class="clear-both"></div>
                        <div><?php echo $_smarty_tpl->getSubTemplate (((string)$_smarty_tpl->tpl_vars['wa_active_theme_path']->value)."/left.contacts.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0);?>
</div>
                        <div>
                            <topic>Новости</topic>
                            <?php $_smarty_tpl->tpl_vars['latest_posts'] = new Smarty_variable(shopPostsPlugin::getBlog(1), null, 0);?>
                            <div class="postblock"><?php  $_smarty_tpl->tpl_vars['post'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['post']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['latest_posts']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['post']->key => $_smarty_tpl->tpl_vars['post']->value){
$_smarty_tpl->tpl_vars['post']->_loop = true;
?>
                                <div class="post">
                                    <div class="date"><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['post']->value['datetime'],"humandate");?>
</div>
                                    <div class="title">
                                        <a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['link'];?>
"><?php echo $_smarty_tpl->tpl_vars['post']->value['title'];?>
</a>
                                        
                                        <?php if (!empty($_smarty_tpl->tpl_vars['post']->value['plugins']['post_title'])){?>
                                        <?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['post']->value['plugins']['post_title']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?><?php echo $_smarty_tpl->tpl_vars['output']->value;?>
<?php } ?>
                                        <?php }?> 
                                    </div>
                                    <!-- <p><?php echo smarty_modifier_truncate(preg_replace('!<[^>]*?>!', ' ', $_smarty_tpl->tpl_vars['post']->value['text']),125);?>
</p> --> 
                                </div>

                                <?php } ?></div>
                            <br><a style="margin-left: 20px;" href="/blog/">Смотреть все >>></a>
                            <div class="clear-both"></div></br>
                        </div>

                        <div>        
                            <topic>Блог</topic>
                            <?php $_smarty_tpl->tpl_vars['latest_posts'] = new Smarty_variable(shopPostsPlugin::getBlog(2), null, 0);?>
                            <div class="postblock"><?php  $_smarty_tpl->tpl_vars['post'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['post']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['latest_posts']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['post']->key => $_smarty_tpl->tpl_vars['post']->value){
$_smarty_tpl->tpl_vars['post']->_loop = true;
?>
                                <div class="post">
                                    <div class="date"><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['post']->value['datetime'],"humandate");?>
</div>
                                    <div class="title">
                                        <a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['link'];?>
"><?php echo $_smarty_tpl->tpl_vars['post']->value['title'];?>
</a>
                                        
                                        <?php if (!empty($_smarty_tpl->tpl_vars['post']->value['plugins']['post_title'])){?>
                                        <?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['post']->value['plugins']['post_title']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?><?php echo $_smarty_tpl->tpl_vars['output']->value;?>
<?php } ?>
                                        <?php }?> 
                                    </div>
                                    <!-- <p><?php echo smarty_modifier_truncate(preg_replace('!<[^>]*?>!', ' ', $_smarty_tpl->tpl_vars['post']->value['text']),125);?>
</p> --> 
                                </div>

                                <?php } ?></div>
                            <br><a style="margin-left: 20px;" href="/post/">Смотреть все >>></a>
                            <div class="clear-both"></div></br>
                        </div>

                        <div align="center">
                            <br/>

                            <!--LiveInternet counter--><script type="text/javascript"><!--
                                                                document.write("<a href='http://www.liveinternet.ru/click' " +
                                        "target=_blank><img src='http://counter.yadro.ru/hit?t44.13;r" +
                                        escape(document.referrer) + ((typeof (screen) == "undefined") ? "" :
                                        ";s" + screen.width + "*" + screen.height + "*" + (screen.colorDepth ?
                                                screen.colorDepth : screen.pixelDepth)) + ";u" + escape(document.URL) +
                                        ";h" + escape(document.title.substring(0, 80)) + ";" + Math.random() +
                                        "' alt='' title='LiveInternet' " +
                                        "border='0' width='31' height='31'></a>")
                                //--></script><!--/LiveInternet--> 

                            <script type="text/javascript">
                                var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
                                document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
                            </script> 
                            <script type="text/javascript">
                                try {
                                    var pageTracker = _gat._getTracker("UA-10113467-1");
                                    pageTracker._trackPageview();
                                } catch (err) {
                                }</script>

                            <!-- begin WebMoney Transfer : accept label --> 
                            &nbsp;<a href="http://www.megastock.ru/" target="_blank"><img src="http://www.megastock.ru/Doc/88x31_accept/blue_rus.gif" alt="www.megastock.ru" border="0"></a> 
                            <!-- end WebMoney Transfer : accept label --> 
                            <!-- begin WebMoney Transfer : attestation label --> 
                            &nbsp;<a href="https://passport.webmoney.ru/asp/certview.asp?wmid=425144048921" target="_blank"><img src="http://www.megastock.ru/doc/88x31_merchant/azure_rus.gif" alt=" 425144048921" border="0"></a> 
                            <!-- end WebMoney Transfer : attestation label --> 

                            <br>
                            <p>Пчелкин Дом в соцсетях:</p>
                            <p><a target="_blank" title="Пчелкин Дом Вконтакте" href="http://vk.com/club17410078"><img alt="Пчелкин Дом Вконтакте" height="32" width="32" src="/wa-data/public/site/themes/default/img/vk.jpg" title="Пчелкин Дом Вконтакте" /></a> <a target="_blank" title="Пчелкин Дом в Facebook" href="https://www.facebook.com/www.pchelkindom.ru"><img title="Пчелкин Дом в Facebook" height="32" width="32" src="/wa-data/public/site/themes/default/img/facebook.jpg" alt="Пчелкин Дом в Facebook" /></a> <a target="_blank" title="Пчелкин Дом в Твиттер" href="https://twitter.com/PchelkinDom"><img alt="Пчелкин Дом в Твиттер" height="32" width="32" src="/wa-data/public/site/themes/default/img/twitter.jpg" title="Пчелкин Дом в Твиттер" /></a> <a target="_blank" title="Пчелкин Дом в Одноклассники" href="http://www.odnoklassniki.ru/group/53175436181741  "><img alt="Пчелкин Дом в Одноклассниках" height="32" width="32" src="/wa-data/public/site/themes/default/img/odnoklassniki.jpg" title="Пчелкин Дом в Одноклассниках" /></a></p>
                            <!-- Yandex.Metrika counter --> 

                            <script type="text/javascript">

                            </script>
                            <noscript>
                            <div><img src="//mc.yandex.ru/watch/260426" style="position:absolute; left:-9999px;" alt="" /></div>
                            </noscript>
                            <!-- /Yandex.Metrika counter --> 

                        </div>     
                    </div>
                    <div id="center"> 
                        <!-- APP CONTENT -->
                        <main role="main">
                            <div class="">
                                <div class="page"> <?php echo $_smarty_tpl->getSubTemplate (((string)$_smarty_tpl->tpl_vars['wa_active_theme_path']->value)."/main.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0);?>

                                    <p><br><br>
                                    </p>
                                </div>
                            </div>
                        </main>
                        <div class="clear"></div>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
        </div>
        <!-- End of main container -->
        <div id="footer">
            <!-- plugin hook: 'frontend_footer' -->
            <div class="right_angle">
                <div class="left_angle">
                    <div class="bee"></div>
                    <div class="text">
                        <p class="intro">2009-2014 (c) Пчелкин Дом - интернет магазин детской одежды. </p>
                        <?php if ($_smarty_tpl->tpl_vars['shop_pages']->value){?> 
                        <!-- info pages --> 
                        <?php echo smarty_function_wa_print_tree(array('tree'=>$_smarty_tpl->tpl_vars['shop_pages']->value,'class'=>"menu-footer",'attrs'=>'id="page-list"','elem'=>' <a href=":url" title=":title">:name</a>','collapsible_class'=>'collapsible','selected'=>$_smarty_tpl->tpl_vars['wa']->value->param('page_id')),$_smarty_tpl);?>

                        <?php }else{ ?>
                        <div class="clear-right"></div>
                        <?php }?>
                        <?php if ($_smarty_tpl->tpl_vars['wa']->value->isAuthEnabled()){?>
                        <ul class="footer-auth">
                            <?php if ($_smarty_tpl->tpl_vars['wa']->value->user()->isAuth()){?>
                            <?php if ($_smarty_tpl->tpl_vars['wa']->value->shop){?>
                            <li> <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->myUrl();?>
"> Личный кабинет</a> </li>
                            <?php }else{ ?>
                            <li><strong><?php echo $_smarty_tpl->tpl_vars['wa']->value->user('name');?>
</strong></li>
                            <?php }?>
                            <li><a href="">Выйти</a></li>
                            <?php }else{ ?>
                            <li><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->loginUrl();?>
">Вход</a></li>
                            <li><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->signupUrl();?>
">Регистрация</a></li>
                            <?php }?>
                        </ul>
                        <?php }?> </div>
                </div>
            </div>
        </div>

        <!-- End of main container -->
        <div style="display: none; z-index:100;" id="add_to_cart"> <!--<img src="img/bee.gif" class="opacity">--> </div>


    </body>
</html>
<?php }} ?>