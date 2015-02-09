<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:39:07
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/templates/actions/backend/BackendStorefronts.html" */ ?>
<?php /*%%SmartyHeaderCode:37825420254d8aa6bbb1420-45447656%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '064999e9aa525cd7653205a34a7fbaedf8fc8266' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/templates/actions/backend/BackendStorefronts.html',
      1 => 1409656509,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '37825420254d8aa6bbb1420-45447656',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa' => 0,
    'wa_app_static_url' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8aa6bbf21e6_95112314',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8aa6bbf21e6_95112314')) {function content_54d8aa6bbf21e6_95112314($_smarty_tpl) {?><div class="sidebar right15px">
    <div class="block s-nolevel2-sidebar"></div>
</div>

<div class="sidebar left200px">

    <div class="block s-nolevel2-sidebar s-storefronts">
        <ul class="menu-v s-links with-icons">
             <?php if ($_smarty_tpl->tpl_vars['wa']->value->userRights('pages')){?>
	           <li id="s-link-pages" class="larger"><a href="#/pages/"><i class="icon16 notebook"></i>Страницы</a></li>
             <?php }?>
             <?php if ($_smarty_tpl->tpl_vars['wa']->value->userRights('design')){?>
    	       <li id="s-link-design" class="larger"><a href="#/design/"><i class="icon16 palette"></i>Дизайн</a></li>
             <?php }?>
  
        </ul>
	</div>
</div>

<div class="content left200px right15px s-nolevel2-box" id="s-storefronts-content">
    <div class="block double-padded s-settings-form">
        Загрузка...
        <i class="icon16 loading"></i>
    </div>

    <div class="clear"></div>
    <!-- settings placeholder -->
</div>

    <div class="clear"></div>
    <script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_app_static_url']->value;?>
js/storefronts.js?v<?php echo $_smarty_tpl->tpl_vars['wa']->value->version();?>
"></script>
    <script type="text/javascript">
        $.storefronts.init();
    </script><?php }} ?>