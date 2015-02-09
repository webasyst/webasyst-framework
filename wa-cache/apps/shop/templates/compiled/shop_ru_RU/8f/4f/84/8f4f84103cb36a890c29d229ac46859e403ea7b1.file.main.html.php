<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:48:37
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/themes/default/main.html" */ ?>
<?php /*%%SmartyHeaderCode:150399737054d8aca59e2670-38967095%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '8f4f84103cb36a890c29d229ac46859e403ea7b1' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/themes/default/main.html',
      1 => 1416918753,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '150399737054d8aca59e2670-38967095',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'frontend_header' => 0,
    '_' => 0,
    'theme_settings' => 0,
    'action' => 0,
    'category' => 0,
    'wa' => 0,
    'frontend_nav' => 0,
    'output' => 0,
    '_NAV_SIDEBAR' => 0,
    'wa_app_url' => 0,
    '_hook_frontend_sidebar_section' => 0,
    '_key' => 0,
    'categories' => 0,
    'selected_category' => 0,
    'tagcloud' => 0,
    'tag' => 0,
    'breadcrumbs' => 0,
    'breadcrumb' => 0,
    'content' => 0,
    'currencies' => 0,
    'c_code' => 0,
    'currency' => 0,
    'c' => 0,
    'in_comparison' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8aca5b5cf57_23448838',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8aca5b5cf57_23448838')) {function content_54d8aca5b5cf57_23448838($_smarty_tpl) {?><?php if (!is_callable('smarty_function_wa_print_tree')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/function.wa_print_tree.php';
?><!-- plugin hook: 'frontend_header' -->

<?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['frontend_header']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo $_smarty_tpl->tpl_vars['_']->value;?>
<?php } ?>            


<?php if ($_smarty_tpl->tpl_vars['theme_settings']->value['category_nav_mode']=='sidebar'&&!(strstr($_smarty_tpl->tpl_vars['action']->value,'product')||$_smarty_tpl->tpl_vars['action']->value=='cart'||$_smarty_tpl->tpl_vars['action']->value=='compare')){?>
    <?php $_smarty_tpl->tpl_vars['_NAV_SIDEBAR'] = new Smarty_variable(1, null, 0);?>
<?php }else{ ?>
    <?php $_smarty_tpl->tpl_vars['_NAV_SIDEBAR'] = new Smarty_variable(0, null, 0);?>
<?php }?>

<nav class="page-nav" role="navigation">

    <?php if (isset($_smarty_tpl->tpl_vars['category']->value)){?><?php $_smarty_tpl->tpl_vars['selected_category'] = new Smarty_variable($_smarty_tpl->tpl_vars['category']->value['id'], null, 0);?><?php }else{ ?><?php $_smarty_tpl->tpl_vars['selected_category'] = new Smarty_variable(null, null, 0);?><?php }?>
    <?php $_smarty_tpl->tpl_vars['categories'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->categories(0,null,true), null, 0);?>
    <?php $_smarty_tpl->tpl_vars['tagcloud'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->tags(), null, 0);?>
    <!-- plugin hook: 'frontend_nav' -->
    
    <?php $_smarty_tpl->tpl_vars['_hook_frontend_sidebar_section'] = new Smarty_variable(false, null, 0);?>
    <?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['frontend_nav']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?>
        <?php if (!empty($_smarty_tpl->tpl_vars['output']->value)){?>
            <?php $_smarty_tpl->tpl_vars['_hook_frontend_sidebar_section'] = new Smarty_variable(true, null, 0);?>
        <?php }?>
    <?php } ?>

    <?php if ($_smarty_tpl->tpl_vars['_NAV_SIDEBAR']->value){?>

        <!-- SIDEBAR with plugins nav, categories and tags -->
        <div class="nav-sidebar">
            <a href="<?php echo $_smarty_tpl->tpl_vars['wa_app_url']->value;?>
" class="nav-sidebar-header">Каталог товаров</a>
            <div class="nav-sidebar-body">
                <?php if ($_smarty_tpl->tpl_vars['_hook_frontend_sidebar_section']->value){?>
                    <!-- 'frontend_nav' plugins -->
                    <div class="dropdown nav-sidebar-hide-this-on-tablets-and-mobile">
                        <ul class="menu-v">
                        <?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_smarty_tpl->tpl_vars['_key'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['frontend_nav']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
 $_smarty_tpl->tpl_vars['_key']->value = $_smarty_tpl->tpl_vars['_']->key;
?>
                            <li class="collapsible">
                                <a href="#" onClick="return false">
                                    <?php if (substr($_smarty_tpl->tpl_vars['_key']->value,-7)=='-plugin'){?><?php echo $_smarty_tpl->tpl_vars['wa']->value->pluginName(substr($_smarty_tpl->tpl_vars['_key']->value,0,-7));?>
<?php }else{ ?>Еще<?php }?>
                                </a>
                                <div class="hoax-sidebar<?php if (!strstr($_smarty_tpl->tpl_vars['_']->value,'<ul class="menu-v')){?> inner<?php }?>" id="hoax-sidebar-<?php echo $_smarty_tpl->tpl_vars['_key']->value;?>
" data-key="<?php echo $_smarty_tpl->tpl_vars['_key']->value;?>
">
                                    <?php echo $_smarty_tpl->tpl_vars['_']->value;?>

                                </div>
                            </li>
                        <?php } ?>
                        </ul>
                    </div>
                <?php }?>

                <!-- categories -->
                <div class="slidemenu" data-back-lbl="Назад">
                    <?php echo smarty_function_wa_print_tree(array('tree'=>$_smarty_tpl->tpl_vars['categories']->value,'selected'=>$_smarty_tpl->tpl_vars['selected_category']->value,'unfolded'=>true,'collapsible_class'=>'collapsible','class'=>"menu-v category-tree",'elem'=>'<a href=":url" title=":name">:name</a>'),$_smarty_tpl);?>

                    <div class="clear-both"></div>
                </div>                
                    
                <?php if ($_smarty_tpl->tpl_vars['tagcloud']->value){?>
                    <!-- tags -->
                    <p class="tags block align-center hoax-sidebar nav-sidebar-hide-this-on-tablets-and-mobile" id="hoax-sidebar-tags" data-key="tags">
                        <?php  $_smarty_tpl->tpl_vars['tag'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['tag']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['tagcloud']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['tag']->key => $_smarty_tpl->tpl_vars['tag']->value){
$_smarty_tpl->tpl_vars['tag']->_loop = true;
?>
                            <a href="<?php echo $_smarty_tpl->tpl_vars['wa_app_url']->value;?>
tag/<?php echo $_smarty_tpl->tpl_vars['tag']->value['uri_name'];?>
/" style="font-size: <?php echo $_smarty_tpl->tpl_vars['tag']->value['size'];?>
%; opacity: <?php echo $_smarty_tpl->tpl_vars['tag']->value['opacity'];?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['tag']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</a>
                        <?php } ?>
                    </p>
                <?php }?>

            </div>
        </div>
        

        <!-- + HOAX DROPDOWN nav bar for tags and plugins; to be shown on tablets only: requires JS for hoax elements implementation -->
        <?php if ($_smarty_tpl->tpl_vars['tagcloud']->value||$_smarty_tpl->tpl_vars['_hook_frontend_sidebar_section']->value){?>
            <div class="nav-hotties nav-sidebar-ousted dropdown" id="hoax-hotties">
                
                <?php if ($_smarty_tpl->tpl_vars['tagcloud']->value||$_smarty_tpl->tpl_vars['_hook_frontend_sidebar_section']->value){?>
                    <ul class="menu-h aux">
                    
                        <!-- 'frontend_nav' plugins -->
                        <?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_smarty_tpl->tpl_vars['_key'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['frontend_nav']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
 $_smarty_tpl->tpl_vars['_key']->value = $_smarty_tpl->tpl_vars['_']->key;
?>
                            <li class="collapsible">
                                <a href="#" onClick="return false">
                                    <?php if (substr($_smarty_tpl->tpl_vars['_key']->value,-7)=='-plugin'){?><?php echo $_smarty_tpl->tpl_vars['wa']->value->pluginName(substr($_smarty_tpl->tpl_vars['_key']->value,0,-7));?>
<?php }else{ ?>Еще<?php }?>
                                </a>
                                <div class="inner hoax-hotties" id="hoax-hotties-<?php echo $_smarty_tpl->tpl_vars['_key']->value;?>
" data-key="<?php echo $_smarty_tpl->tpl_vars['_key']->value;?>
"></div>
                            </li>
                        <?php } ?>
                        
                        <?php if ($_smarty_tpl->tpl_vars['tagcloud']->value){?>
                            <!-- tags -->
                            <li class="collapsible">
                                <a href="#" onClick="return false">Теги</a>
                                <div class="inner shadowed">
                                    <p class="tags block align-center hoax-hotties" id="hoax-hotties-tags" data-key="tags"></p>
                                </div>
                            </li>
                        <?php }?>
                        
                    </ul>
                <?php }?>
                        
            </div>
        <?php }?>  
        
    <?php }elseif(!(strstr($_smarty_tpl->tpl_vars['action']->value,'product')||$_smarty_tpl->tpl_vars['action']->value=='cart'||$_smarty_tpl->tpl_vars['action']->value=='compare')){?>

        <!-- DROPDOWN nav bar -->
        <div class="nav-hotties nav-sidebar-ousted dropdown">

            <?php if ($_smarty_tpl->tpl_vars['tagcloud']->value||$_smarty_tpl->tpl_vars['_hook_frontend_sidebar_section']->value){?>
                <ul class="menu-h aux">
                
                    <!-- 'frontend_nav' plugins -->
                    <?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_smarty_tpl->tpl_vars['_key'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['frontend_nav']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
 $_smarty_tpl->tpl_vars['_key']->value = $_smarty_tpl->tpl_vars['_']->key;
?>
                        <li class="collapsible">
                            <a href="#" onClick="return false">
                                <?php if (substr($_smarty_tpl->tpl_vars['_key']->value,-7)=='-plugin'){?><?php echo $_smarty_tpl->tpl_vars['wa']->value->pluginName(substr($_smarty_tpl->tpl_vars['_key']->value,0,-7));?>
<?php }else{ ?>Еще<?php }?>
                            </a>
                            <div class="inner">
                                <?php echo $_smarty_tpl->tpl_vars['_']->value;?>

                            </div>
                        </li>
                    <?php } ?>
                        
                    <?php if ($_smarty_tpl->tpl_vars['tagcloud']->value){?>
                        <!-- tags -->
                        <li class="collapsible">
                            <a href="#" onClick="return false">Теги</a>
                            <div class="inner shadowed">
                                <p class="tags block align-center">
                                    <?php  $_smarty_tpl->tpl_vars['tag'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['tag']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['tagcloud']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['tag']->key => $_smarty_tpl->tpl_vars['tag']->value){
$_smarty_tpl->tpl_vars['tag']->_loop = true;
?>
                                        <a href="<?php echo $_smarty_tpl->tpl_vars['wa_app_url']->value;?>
tag/<?php echo $_smarty_tpl->tpl_vars['tag']->value['uri_name'];?>
/" style="font-size: <?php echo $_smarty_tpl->tpl_vars['tag']->value['size'];?>
%; opacity: <?php echo $_smarty_tpl->tpl_vars['tag']->value['opacity'];?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['tag']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</a>
                                    <?php } ?>
                                </p>
                            </div>
                        </li>
                    <?php }?>
                    
                </ul>
            <?php }?>

            <!-- categories as a horizontal dropdown menu -->
            <?php echo smarty_function_wa_print_tree(array('tree'=>$_smarty_tpl->tpl_vars['categories']->value,'selected'=>$_smarty_tpl->tpl_vars['selected_category']->value,'unfolded'=>true,'collapsible_class'=>'collapsible','class'=>"menu-h dropdown",'elem'=>'<a href=":url" title=":name">:name</a>'),$_smarty_tpl);?>


            <div class="clear-right"></div>
                    
        </div>
    
    <?php }else{ ?>

        
    
    <?php }?>
    
</nav>

<div class="page-content<?php if ($_smarty_tpl->tpl_vars['_NAV_SIDEBAR']->value){?> with-sidebar<?php }?>" id="page-content" itemscope itemtype="http://schema.org/<?php if ($_smarty_tpl->tpl_vars['action']->value=='default'){?>Store<?php }else{ ?>WebPage<?php }?>">

    <?php if ($_smarty_tpl->tpl_vars['action']->value=='default'){?>
        <meta itemprop="name" content="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->settings('name');?>
">
        <meta itemprop="address" content="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->settings('country');?>
">
        <meta itemprop="telephone" content="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->settings('phone');?>
">
        <meta itemprop="currenciesAccepted" content="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->currency();?>
">
    <?php }?>

    <!-- internal navigation breadcrumbs -->
    <?php if (!empty($_smarty_tpl->tpl_vars['breadcrumbs']->value)){?>
        <nav class="breadcrumbs" itemprop="breadcrumb">
            <?php if (!$_smarty_tpl->tpl_vars['wa']->value->globals('isMyAccount')){?>
                <a href="<?php echo $_smarty_tpl->tpl_vars['wa_app_url']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->settings('name');?>
</a>  <span class="rarr">&rarr;</span>
            <?php }else{ ?>
                
                <?php $_smarty_tpl->createLocalArrayVariable('breadcrumbs', null, 0);
$_smarty_tpl->tpl_vars['breadcrumbs']->value[0] = null;?>
            <?php }?>
            <?php  $_smarty_tpl->tpl_vars['breadcrumb'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['breadcrumb']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['breadcrumbs']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['breadcrumb']->key => $_smarty_tpl->tpl_vars['breadcrumb']->value){
$_smarty_tpl->tpl_vars['breadcrumb']->_loop = true;
?>
                <?php if ($_smarty_tpl->tpl_vars['breadcrumb']->value){?>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['breadcrumb']->value['url'];?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['breadcrumb']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</a> <span class="rarr">&rarr;</span>
                <?php }?>
            <?php } ?>
        </nav>
    <?php }?>

    <?php echo $_smarty_tpl->tpl_vars['content']->value;?>

    
    <div class="clear-both"></div>
    
</div>

<aside class="currency-toggle">
    <?php if (count($_smarty_tpl->tpl_vars['currencies']->value)>1){?>
        <select id="currency">
            <?php $_smarty_tpl->tpl_vars['currency'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->currency(), null, 0);?>
            <?php  $_smarty_tpl->tpl_vars['c'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['c']->_loop = false;
 $_smarty_tpl->tpl_vars['c_code'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['currencies']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['c']->key => $_smarty_tpl->tpl_vars['c']->value){
$_smarty_tpl->tpl_vars['c']->_loop = true;
 $_smarty_tpl->tpl_vars['c_code']->value = $_smarty_tpl->tpl_vars['c']->key;
?>
                <option value="<?php echo $_smarty_tpl->tpl_vars['c_code']->value;?>
" <?php if ($_smarty_tpl->tpl_vars['c_code']->value==$_smarty_tpl->tpl_vars['currency']->value){?> selected<?php }?>><?php echo $_smarty_tpl->tpl_vars['c']->value['title'];?>
</option>
            <?php } ?>
        </select>
        <script>
            $("#currency").change(function () {
                var url = location.href;
                if (url.indexOf('?') == -1) {
                    url += '?';
                } else {
                    url += '&';
                }
                location.href = url + 'currency=' + $(this).val();
            });
        </script>
    <?php }?>
</aside>  

<div class="clear-both"></div>

<div id="dialog" class="dialog">
    <div class="dialog-background"></div>
    <div class="dialog-window">
        <!-- common part -->
        <div class="cart">

        </div>
        <!-- /common part -->

    </div>
</div>

<aside id="compare-leash">
    <?php $_smarty_tpl->tpl_vars['in_comparison'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->shop->inComparison(), null, 0);?>
    <a <?php if (!$_smarty_tpl->tpl_vars['in_comparison']->value){?>style="display:none;"<?php }?> href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->getUrl('/frontend/compare',array('id'=>implode(',',$_smarty_tpl->tpl_vars['in_comparison']->value)));?>
">Сравнить выбранные товары (<strong><?php echo count($_smarty_tpl->tpl_vars['in_comparison']->value);?>
</strong>)</a>
</aside><?php }} ?>