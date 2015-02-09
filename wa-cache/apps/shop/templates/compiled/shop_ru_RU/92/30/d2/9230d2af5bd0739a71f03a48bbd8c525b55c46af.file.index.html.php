<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:48:37
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/site/themes/default/index.html" */ ?>
<?php /*%%SmartyHeaderCode:106663450954d8aca5548606-64782696%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '9230d2af5bd0739a71f03a48bbd8c525b55c46af' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/site/themes/default/index.html',
      1 => 1416916832,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '106663450954d8aca5548606-64782696',
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
    'theme_settings' => 0,
    'a' => 0,
    'wa_app_url' => 0,
    'wh' => 0,
    'cart_total' => 0,
    'blogs' => 0,
    'b' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8aca575c4f4_82814314',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8aca575c4f4_82814314')) {function content_54d8aca575c4f4_82814314($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_wa_datetime')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/modifier.wa_datetime.php';
?><!DOCTYPE html>
<html<?php if ($_smarty_tpl->tpl_vars['wa']->value->globals('isMyAccount')){?> class="my"<?php }?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1<?php if ($_smarty_tpl->tpl_vars['wa']->value->isMobile()){?>, maximum-scale=1, user-scalable=0<?php }?>" />

    <title><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['wa']->value->title(), ENT_QUOTES, 'UTF-8', true);?>
</title>
    <meta name="Keywords" content="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['wa']->value->meta('keywords'), ENT_QUOTES, 'UTF-8', true);?>
" />
    <meta name="Description" content="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['wa']->value->meta('description'), ENT_QUOTES, 'UTF-8', true);?>
" />
    
    <?php if (!empty($_smarty_tpl->tpl_vars['canonical']->value)){?><link rel="canonical" href="<?php echo $_smarty_tpl->tpl_vars['canonical']->value;?>
"/><?php }?>
    <link rel="shortcut icon" href="/favicon.ico"/>
    <?php if ($_smarty_tpl->tpl_vars['wa']->value->blog){?>
        <!-- rss -->
        <?php $_smarty_tpl->tpl_vars['rss'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->blog->rssUrl(), null, 0);?>
        <?php if ($_smarty_tpl->tpl_vars['rss']->value){?><link rel="alternate" type="application/rss+xml" title="<?php echo $_smarty_tpl->tpl_vars['wa']->value->accountName();?>
" href="<?php echo $_smarty_tpl->tpl_vars['rss']->value;?>
"><?php }?>
    <?php }?>
       
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
wa-content/js/jquery/jquery-1.11.1.min.js" ></script>
    
    <script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery/jquery-migrate-1.2.1.min.js"></script>
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
<body<?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['color_scheme']){?> class="color_scheme_<?php echo str_replace('img/backgrounds/themesettings/','',str_replace(array('.png','.jpg','bokeh_','abstract_','sky_'),'',$_smarty_tpl->tpl_vars['theme_settings']->value['color_scheme']));?>
"<?php }?>>

    <header class="globalheader">
            
        <?php if (count($_smarty_tpl->tpl_vars['wa']->value->apps())>0||$_smarty_tpl->tpl_vars['wa']->value->isAuthEnabled()){?>
        
            <!-- GLOBAL HEADER -->
            <div id="globalnav" class="dimmed">
                <div class="container">
        
                    <button id="mobile-nav-toggle"><!-- nav toggle for mobile devices --></button>
                    
                    <nav role="navigation">
                    
                        <!-- core site sections (apps) -->
                        <ul class="apps">
                            <?php  $_smarty_tpl->tpl_vars['a'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['a']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['wa']->value->apps(); if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['a']->key => $_smarty_tpl->tpl_vars['a']->value){
$_smarty_tpl->tpl_vars['a']->_loop = true;
?>
                                <li<?php if ($_smarty_tpl->tpl_vars['a']->value['url']==$_smarty_tpl->tpl_vars['wa_app_url']->value&&!$_smarty_tpl->tpl_vars['wa']->value->globals('isMyAccount')){?> class="selected"<?php }?>><a href="<?php echo $_smarty_tpl->tpl_vars['a']->value['url'];?>
"><?php echo $_smarty_tpl->tpl_vars['a']->value['name'];?>
</a></li>
                            <?php } ?>
                        </ul>
                        
                        <?php if ($_smarty_tpl->tpl_vars['wa']->value->isAuthEnabled()){?>
                            <!-- user auth -->
                            <ul class="auth">
                                                        
                                <?php if ($_smarty_tpl->tpl_vars['wa']->value->user()->isAuth()){?>
                                    <?php if ($_smarty_tpl->tpl_vars['wa']->value->myUrl()){?>
                                        <li<?php if ($_smarty_tpl->tpl_vars['wa']->value->globals('isMyAccount')){?> class="bold"<?php }?>>
                                            <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->myUrl();?>
" class="not-visited"><i class="icon16 userpic20 float-left" style="background-image: url('<?php echo $_smarty_tpl->tpl_vars['wa']->value->user()->getPhoto2x(20);?>
');"></i> <strong><?php echo $_smarty_tpl->tpl_vars['wa']->value->user('name');?>
</strong></a>
                                        </li>
                                    <?php }else{ ?>
                                         <li><strong><?php echo $_smarty_tpl->tpl_vars['wa']->value->user('name');?>
</strong></li>
                                    <?php }?>
                                    <li><a href="?logout" class="not-visited">Выйти</a></li>
                                <?php }else{ ?>
                                    <li><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->loginUrl();?>
" class="not-visited">Вход</a></li>
                                    <li><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->signupUrl();?>
" class="not-visited">Регистрация</a></li>
                                <?php }?>
                            </ul>
                        <?php }?>

                        <?php if ($_smarty_tpl->tpl_vars['wa']->value->shop&&$_smarty_tpl->tpl_vars['wa']->value->shop->settings('phone')){?>
                            <!-- offline contact information -->
                            <div class="offline">
                                <b><?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->settings('phone');?>
</b>
                                <?php if (!isset($_smarty_tpl->tpl_vars['wh'])) $_smarty_tpl->tpl_vars['wh'] = new Smarty_Variable(null);if ($_smarty_tpl->tpl_vars['wh']->value = $_smarty_tpl->tpl_vars['wa']->value->shop->settings('workhours')){?>
                                    <span class="hint"><?php echo $_smarty_tpl->tpl_vars['wh']->value['days_from_to'];?>
<?php if ($_smarty_tpl->tpl_vars['wh']->value['hours_from']&&$_smarty_tpl->tpl_vars['wh']->value['hours_to']){?> <?php echo $_smarty_tpl->tpl_vars['wh']->value['hours_from'];?>
—<?php echo $_smarty_tpl->tpl_vars['wh']->value['hours_to'];?>
<?php }?></span>
                                <?php }?>
                            </div>                            
                        <?php }?>
                        
                    </nav>
                    <div class="clear-both"></div>
                     
                </div>
            </div>
            
        <?php }?>


        <!-- EXTENDED HEADER with in-app navigation -->
        <div class="container extendednav" id="header-container">
            <h2>
                <a href="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
">
                    <?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['logo']){?>
                        <img src="<?php echo $_smarty_tpl->tpl_vars['wa_theme_url']->value;?>
<?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['logo'];?>
" alt="<?php echo $_smarty_tpl->tpl_vars['wa']->value->accountName();?>
" id="logo" />
                        <span><?php echo $_smarty_tpl->tpl_vars['wa']->value->accountName();?>
</span>
                    <?php }else{ ?>
                        <?php echo $_smarty_tpl->tpl_vars['wa']->value->accountName();?>

                    <?php }?>
                </a>
            </h2>
            
            <?php if ($_smarty_tpl->tpl_vars['wa']->value->shop){?>
                <!-- cart -->
                <?php $_smarty_tpl->tpl_vars['cart_total'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->cart->total(), null, 0);?>
                <div id="cart" class="cart<?php if (!$_smarty_tpl->tpl_vars['cart_total']->value){?> empty<?php }?>">
                     <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('shop/frontend/cart');?>
" class="cart-summary">
                         <i class="cart-icon"></i>
                         <strong class="cart-total"><?php echo wa_currency_html($_smarty_tpl->tpl_vars['cart_total']->value,$_smarty_tpl->tpl_vars['wa']->value->shop->currency());?>
</strong>
                     </a>
                     <div id="cart-content">
                         
                     </div>
                     <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('shop/frontend/cart');?>
" class="cart-to-checkout" style="display: none;">
                         Посмотреть корзину
                     </a>
                </div>
            <?php }?>
            
            <?php echo $_smarty_tpl->getSubTemplate (((string)$_smarty_tpl->tpl_vars['wa_active_theme_path']->value)."/header.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0);?>

            
            <div class="clear-both"></div> 
        </div>        
        
    </header>
    
    <!-- APP CONTENT -->
    <main class="maincontent<?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['custom_background']){?> custom-background<?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['custom_background_stretch']){?> stretched<?php }?><?php }?>" role="main">
        <div class="banner<?php if (!$_smarty_tpl->tpl_vars['theme_settings']->value['custom_background']){?> <?php echo str_replace('img/backgrounds/themesettings/','',str_replace(array('.png','.jpg'),'',$_smarty_tpl->tpl_vars['theme_settings']->value['color_scheme']));?>
<?php }else{ ?> <?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['custom_background_banner_text_color'];?>
" style="background-image: url('<?php echo $_smarty_tpl->tpl_vars['wa_theme_url']->value;?>
<?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['custom_background'];?>
');<?php }?>">
            <div class="container">

                <?php if (!empty($_smarty_tpl->tpl_vars['theme_settings']->value['banner_caption'])){?>
                    <h3><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['theme_settings']->value['banner_caption'], ENT_QUOTES, 'UTF-8', true);?>
</h3>
                <?php }?>

                <?php if ($_smarty_tpl->tpl_vars['wa']->value->globals('isMyAccount')){?>
                
                    
                    <?php echo $_smarty_tpl->tpl_vars['wa']->value->myNav('menu-h bottom-padded');?>

                    
                <?php }else{ ?>

                    <?php echo $_smarty_tpl->getSubTemplate (((string)$_smarty_tpl->tpl_vars['wa_active_theme_path']->value)."/banner.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0);?>

                
                <?php }?>

                <div class="clear-both"></div>
            </div>
        </div>
        <div class="container">
            <div class="page">
                <?php echo $_smarty_tpl->getSubTemplate (((string)$_smarty_tpl->tpl_vars['wa_active_theme_path']->value)."/main.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0);?>

            </div>
        </div>
    </main>
    
    <!-- FOOTER -->
    <footer class="globalfooter" role="contentinfo">
        <div class="container">
            <div class="footer-block" id="copyright">
                &copy; <?php echo smarty_modifier_wa_datetime(time(),"Y");?>

                <a href="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['wa']->value->accountName();?>
</a>
            </div>
                       
            <?php if ($_smarty_tpl->tpl_vars['wa']->value->shop){?>
                <div class="footer-block">
                    <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('shop/frontend');?>
" class="top">Магазин</a>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('shop/frontend/cart');?>
">Корзина</a>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('shop/frontend/checkout');?>
">Оформить заказ</a>
                </div>
            <?php }?>

            <?php if ($_smarty_tpl->tpl_vars['wa']->value->photos){?>
                <div class="footer-block">
                    <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('photos/frontend');?>
" class="top">Фото</a>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('photos/frontend');?>
">Фотопоток</a>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('photos/frontend');?>
favorites/">Избранное</a>
                </div>
            <?php }?>
            
            <?php if ($_smarty_tpl->tpl_vars['wa']->value->blog){?>
                <div class="footer-block">
                    <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('blog/frontend');?>
" class="top">Блог</a>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('blog/frontend/rss');?>
">Подписаться</a>
                    <?php $_smarty_tpl->tpl_vars['blogs'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->blog->blogs(), null, 0);?>
                    <?php  $_smarty_tpl->tpl_vars['b'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['b']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['blogs']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['b']->key => $_smarty_tpl->tpl_vars['b']->value){
$_smarty_tpl->tpl_vars['b']->_loop = true;
?>
                        <a href="<?php echo $_smarty_tpl->tpl_vars['b']->value['link'];?>
"><?php echo $_smarty_tpl->tpl_vars['b']->value['name'];?>
</a>
                    <?php } ?>
                </div>
            <?php }?>

            <div class="footer-block">
                <a href="#" class="top">Обратная связь</a>
                
                <?php if ($_smarty_tpl->tpl_vars['wa']->value->shop){?>
                    <span><?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->settings('phone');?>
</span>
                    <a href="mailto:<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->settings('email');?>
"><?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->settings('email');?>
</a>
                <?php }?>
            </div>

            
            
            <div class="clear-both"></div>
        </div>
        <div class="followus">
            <div class="container">

                <?php if ($_smarty_tpl->tpl_vars['wa']->value->mailer&&$_smarty_tpl->tpl_vars['wa']->value->getUrl('mailer/frontend/subscribe')){?>
                    <div class="mailer-subscribe">
                        
                        <form action="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('mailer/frontend/subscribe');?>
" id="mailer-subscribe-form" target="mailer-subscribe-iframe">
                            Подписаться на новости:
                            <input type="email" name="email" placeholder="your@email.here">
                            <input type="submit" value="Подписаться">
                        </form>
                        <iframe id="mailer-subscribe-iframe" name="mailer-subscribe-iframe" src="javascript:true" style="width:0;height:0;border:0px solid #666;float:right;background: #666;"></iframe>
                        <p style="display:none" id="mailer-subscribe-thankyou"><i>Спасибо! Будем держать вас в курсе.</i></p>
                    </div>
                <?php }?>
                
                <div class="social">
                    <?php if ($_smarty_tpl->tpl_vars['wa']->value->blog&&$_smarty_tpl->tpl_vars['rss']->value){?><a href="<?php echo $_smarty_tpl->tpl_vars['rss']->value;?>
" title="RSS"><i class="icon24 rss"></i></a><?php }?>
                    <?php if (!empty($_smarty_tpl->tpl_vars['theme_settings']->value['facebook'])){?><a href="<?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['facebook'];?>
" title="Фейсбук"><i class="icon24 facebook"></i></a><?php }?>
                    <?php if (!empty($_smarty_tpl->tpl_vars['theme_settings']->value['twitter'])){?><a href="https://twitter.com/<?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['twitter'];?>
" title="Твиттер"><i class="icon24 twitter"></i></a><?php }?>
                    <?php if (!empty($_smarty_tpl->tpl_vars['theme_settings']->value['vk'])){?><a href="<?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['vk'];?>
" title="ВКонтакте"><i class="icon24 vk"></i></a><?php }?>
                    <?php if (!empty($_smarty_tpl->tpl_vars['theme_settings']->value['instagram'])){?><a href="http://instagram.com/<?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['instagram'];?>
" title="Инстаграм"><i class="icon24 instagram"></i></a><?php }?>
                    <?php if (!empty($_smarty_tpl->tpl_vars['theme_settings']->value['youtube'])){?><a href="<?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['youtube'];?>
" title="Youtube"><i class="icon24 youtube"></i></a><?php }?>
                    <?php if (!empty($_smarty_tpl->tpl_vars['theme_settings']->value['pinterest'])){?><a href="<?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['pinterest'];?>
" title="Pinterest"><i class="icon24 pinterest"></i></a><?php }?>
                    <?php if (!empty($_smarty_tpl->tpl_vars['theme_settings']->value['gplus'])){?><a href="<?php echo $_smarty_tpl->tpl_vars['theme_settings']->value['gplus'];?>
" title="Google+"><i class="icon24 gplus"></i></a><?php }?>
                </div>
                
            </div>
        </div>
        <?php echo $_smarty_tpl->getSubTemplate (((string)$_smarty_tpl->tpl_vars['wa_active_theme_path']->value)."/footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0);?>

    </footer>
    
</body>
</html><?php }} ?>