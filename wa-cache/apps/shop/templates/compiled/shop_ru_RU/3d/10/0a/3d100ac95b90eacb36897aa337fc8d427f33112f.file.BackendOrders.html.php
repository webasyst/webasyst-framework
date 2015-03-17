<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 11:41:13
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/templates/actions/backend/BackendOrders.html" */ ?>
<?php /*%%SmartyHeaderCode:181738841055069729207524-41902542%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '3d100ac95b90eacb36897aa337fc8d427f33112f' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/templates/actions/backend/BackendOrders.html',
      1 => 1401793949,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '181738841055069729207524-41902542',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa_app_static_url' => 0,
    'wa' => 0,
    'pending_count' => 0,
    'state_counters' => 0,
    'backend_orders' => 0,
    '_' => 0,
    'all_count' => 0,
    'states' => 0,
    'id' => 0,
    'state' => 0,
    'storefronts' => 0,
    'url' => 0,
    'cnt' => 0,
    'coupons_count' => 0,
    'template_content' => 0,
    'template' => 0,
    'default_view' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5506972943cfd1_57714711',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5506972943cfd1_57714711')) {function content_5506972943cfd1_57714711($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_replace')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.replace.php';
?><script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_app_static_url']->value;?>
js/tmpl.min.js?v=<?php echo $_smarty_tpl->tpl_vars['wa']->value->version();?>
"></script>
<script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_app_static_url']->value;?>
js/lazy.load.js?v=<?php echo $_smarty_tpl->tpl_vars['wa']->value->version();?>
"></script>
<script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_app_static_url']->value;?>
js/orders.js?v=<?php echo $_smarty_tpl->tpl_vars['wa']->value->version();?>
"></script>
<script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_app_static_url']->value;?>
js/order/list.js?v=<?php echo $_smarty_tpl->tpl_vars['wa']->value->version();?>
"></script>
<script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_app_static_url']->value;?>
js/order/edit.js?v=<?php echo $_smarty_tpl->tpl_vars['wa']->value->version();?>
"></script>

<div class="sidebar left200px s-inner-sidebar" id="s-sidebar">
    <div class="block">
        <ul class="menu-v with-icons">
            <li class="bottom-padded">
                <a href="#/orders/new/" class="bold"><i class="icon16 add"></i>Новый заказ</a>
            </li>
            <li id="s-pending-orders" class="list">
                <span class="count"><?php if (!empty($_smarty_tpl->tpl_vars['pending_count']->value)){?><?php echo $_smarty_tpl->tpl_vars['pending_count']->value;?>
<?php }?></span>
                <a href="#/orders/state_id=new|processing|paid">
                    <i class="icon16 ss orders-processing"></i>В обработке
                    <strong class="highlighted small"><?php if (!empty($_smarty_tpl->tpl_vars['state_counters']->value['new'])){?>+<?php echo $_smarty_tpl->tpl_vars['state_counters']->value['new'];?>
<?php }?></strong>
                </a>
            </li>

            <!-- plugin hook: 'backend_orders.sidebar_top_li' -->
            
            <?php if (!empty($_smarty_tpl->tpl_vars['backend_orders']->value)){?><?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['backend_orders']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo ifset($_smarty_tpl->tpl_vars['_']->value['sidebar_top_li']);?>
<?php } ?><?php }?>

            <li id="s-all-orders" class="list">
                <span class="count"><?php if (!empty($_smarty_tpl->tpl_vars['all_count']->value)){?><?php echo $_smarty_tpl->tpl_vars['all_count']->value;?>
<?php }?></span>
                <a href="#/orders/all/">
                    <i class="icon16 ss orders-all"></i>Все заказы
                </a>
            </li>
        </ul>
    </div>

    <!-- plugin hook: 'backend_orders.sidebar_section' -->
    
    <?php if (!empty($_smarty_tpl->tpl_vars['backend_orders']->value)){?><?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['backend_orders']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo ifset($_smarty_tpl->tpl_vars['_']->value['sidebar_section']);?>
<?php } ?><?php }?>

    <div class="block">
        <h5 class="heading">Статусы заказов</h5>
        <ul class="menu-v with-icons collapsible">

            <?php  $_smarty_tpl->tpl_vars['state'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['state']->_loop = false;
 $_smarty_tpl->tpl_vars['id'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['states']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['state']->key => $_smarty_tpl->tpl_vars['state']->value){
$_smarty_tpl->tpl_vars['state']->_loop = true;
 $_smarty_tpl->tpl_vars['id']->value = $_smarty_tpl->tpl_vars['state']->key;
?>
                <li data-state-id="<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
" class="list">
                    <span class="count"><?php if (isset($_smarty_tpl->tpl_vars['state_counters']->value[$_smarty_tpl->tpl_vars['id']->value])){?><?php echo $_smarty_tpl->tpl_vars['state_counters']->value[$_smarty_tpl->tpl_vars['id']->value];?>
<?php }else{ ?>0<?php }?></span>
                    <a href="#/orders/state_id=<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
/" style="<?php echo $_smarty_tpl->tpl_vars['state']->value->getStyle();?>
">
                        <i class="<?php echo $_smarty_tpl->tpl_vars['state']->value->getOption('icon');?>
"></i><?php echo $_smarty_tpl->tpl_vars['state']->value->getName();?>

                    </a>
                </li>
            <?php } ?>
        </ul>
    </div>

    <?php if (!empty($_smarty_tpl->tpl_vars['storefronts']->value)&&count($_smarty_tpl->tpl_vars['storefronts']->value)>1){?>
    <div class="block">
        <h5 class="heading collapse-handler">
            Витрины
        </h5>
        <ul class="menu-v with-icons s-storefronts-filter">
            <?php  $_smarty_tpl->tpl_vars['cnt'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['cnt']->_loop = false;
 $_smarty_tpl->tpl_vars['url'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['storefronts']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['cnt']->key => $_smarty_tpl->tpl_vars['cnt']->value){
$_smarty_tpl->tpl_vars['cnt']->_loop = true;
 $_smarty_tpl->tpl_vars['url']->value = $_smarty_tpl->tpl_vars['cnt']->key;
?>
                <li data-storefront="<?php if (substr($_smarty_tpl->tpl_vars['url']->value,-1)=='/'){?><?php echo substr($_smarty_tpl->tpl_vars['url']->value,0,-1);?>
<?php }else{ ?><?php echo $_smarty_tpl->tpl_vars['url']->value;?>
<?php }?>">
                    <span class="count"><?php echo $_smarty_tpl->tpl_vars['cnt']->value;?>
</span>
                    <a href="#/orders/storefront=<?php echo urlencode($_smarty_tpl->tpl_vars['url']->value);?>
" style="margin-right: 1em;"><?php if (substr($_smarty_tpl->tpl_vars['url']->value,-1)=='/'){?><?php echo str_replace('www.','',substr($_smarty_tpl->tpl_vars['url']->value,0,-1));?>
<?php }else{ ?><?php echo str_replace('www.','',$_smarty_tpl->tpl_vars['url']->value);?>
<?php }?></a>
                </li>
            <?php } ?>
        </ul>
    </div>
    <?php }?>


    <div class="block">
        <ul class="menu-v with-icons">
            <li id="s-coupons">
                <span class="count"><?php echo $_smarty_tpl->tpl_vars['coupons_count']->value;?>
</span>
                <a href="#/coupons/"><i class="icon16 ss coupon"></i>Купоны на скидку</a>
            </li>

            <!-- plugin hook: 'backend_orders.sidebar_bottom_li' -->
            
            <?php if (!empty($_smarty_tpl->tpl_vars['backend_orders']->value)){?><?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['backend_orders']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo ifset($_smarty_tpl->tpl_vars['_']->value['sidebar_bottom_li']);?>
<?php } ?><?php }?>

        </ul>
    </div>

</div>

<div class="content left200px blank" id="s-content" style="padding-left: 10px;">
    <div class="block double-padded">
    Загрузка <i class="icon16 loading"></i>
    </div>
</div>

<?php $_smarty_tpl->_capture_stack[0][] = array("template-order-product-img", null, null); ob_start(); ?>

{% if(o.url){ %}<img src="{%#o.url%}">{% } else { %}<img src="<?php echo $_smarty_tpl->tpl_vars['wa_app_static_url']->value;?>
img/image-dummy-small.png" class="not-found" style="width: 48px; height: 48px;">{% } %}

<?php list($_capture_buffer, $_capture_assign, $_capture_append) = array_pop($_smarty_tpl->_capture_stack[0]);
if (!empty($_capture_buffer)) {
 if (isset($_capture_assign)) $_smarty_tpl->assign($_capture_assign, ob_get_contents());
 if (isset( $_capture_append)) $_smarty_tpl->append( $_capture_append, ob_get_contents());
 Smarty::$_smarty_vars['capture'][$_capture_buffer]=ob_get_clean();
} else $_smarty_tpl->capture_error();?>

<?php $_smarty_tpl->_capture_stack[0][] = array("template-order", null, null); ob_start(); ?>
    
    {% var options = o.options || {}; %}
    {% var product = o.data.product; %}
    {% var sku_ids = o.data.sku_ids; %}
    {% var index = options.index || '0'; %}
    {% var sku_count = $.shop.helper.size(product.skus); %}
    {% var chosen_sku_id = product.sku_id; %}
    {% var currency = options.currency; %}
    {% var product_sku = null; %}
    {% var stocks = o.options.stocks || {}; %}
    <tr data-product-id="{%#product.id%}" data-index={%#index%} class="s-order-item">
        <td class="min-width valign-top">{% include('template-order-product-img', { url: product.url_crop_small }); %}</td>
        <td>
            <strong class="large">{%=product.name%}</strong>
            <span class="gray">{%#product.price_str%}</span>

            {% if (sku_count == 1) { %}
                <span class="s-orders-stock-icon-aggregate">
                    {%#product.icon%}
                </span>
                <span class="s-orders-stock-icon"></span>
            {% } else { %}
                {%#product.icon%}
            {% } %}

            {% if (sku_count > 1) { %}
                <p>
                    <ul class="menu-v compact small s-orders-skus">
                        {% for (var i = 0, n = sku_ids.length; i < n; i += 1) { %}
                            {% var sku_id = sku_ids[i]; %}
                            {% if (product.skus[sku_id].checked) { %}
                                {% product_sku = product.skus[sku_id]; %}
                            {% } %}
                            <li>
                                <label>
                                    <input type="radio" name="sku[add][{%#index%}]" value="{%#sku_id%}"
                                                                {% if (product.skus[sku_id].checked) { %}
                                            checked="checked" {% chosen_sku_id = sku_id; %}
                                        {% } %}
                                    >{%=product.skus[sku_id].name%}
                                    {% if (product.skus[sku_id].sku) { %}<span class="gray">{%=product.skus[sku_id].name%}</span>{% } %}
                                    <strong>{%#product.skus[sku_id].price_str%}</strong>
                                    <span class="s-orders-stock-icon-aggregate">
                                        {%#product.skus[sku_id].icon%}
                                    </span>
                                    <span class="s-orders-stock-icon" style="display:none;"></span>
                                </label>
                            </li>
                        {% } %}
                    </ul>
                </p>
            {% } else { %}
                {% product_sku = product.skus[product.sku_id]; %}
                <input type="hidden" name="sku[add][{%#index%}]" value="{%#product.sku_id%}">
            {% } %}

            {% if (!$.isEmptyObject(product.services)) { %}
                <p>{% include('template-order-services', { 
                        services: product.services,
                        service_ids: o.data.service_ids,
                        options: options 
                    }); %}</p>
            {% } %}
        </td>
        <input type="hidden" name="product[add][{%#index%}]" value="{%#product.id%}">
        <td class="valign-top nowrap{% if (options.price_edit) { %} align-right{% } %}">
            <span style="padding-top: 2px;" class="gray">&times;</span>
            <input type="text"
                name="quantity[add][{%#index%}][product]"
                class="s-orders-quantity short numerical"
                value="1"
            >
        </td>

        {% include('template-order-stocks-add', { sku: product_sku, stocks: stocks, index: index }); %}
        <td class="valign-top align-right s-orders-product-price">
            {% if (!options.price_edit) { %}
                <span>{%#product.skus[chosen_sku_id].price_str%}</span>
                <input type="hidden" name="price[add][{%#index%}][product]" value="{%#''+product.skus[chosen_sku_id].price%}" class="short">
            {% } else { %}
                <input type="text" name="price[add][{%#index%}][product]" value="{%#''+product.skus[chosen_sku_id].price%}" class="short"><span style="padding-top: 2px;">{%#currency%}</span>
            {% } %}
        </td>
        <td class="valign-top min-width"><a href="#" class="s-order-item-delete"><i class="icon16 delete"></i></a></td>
    </tr>
    
<?php list($_capture_buffer, $_capture_assign, $_capture_append) = array_pop($_smarty_tpl->_capture_stack[0]);
if (!empty($_capture_buffer)) {
 if (isset($_capture_assign)) $_smarty_tpl->assign($_capture_assign, ob_get_contents());
 if (isset( $_capture_append)) $_smarty_tpl->append( $_capture_append, ob_get_contents());
 Smarty::$_smarty_vars['capture'][$_capture_buffer]=ob_get_clean();
} else $_smarty_tpl->capture_error();?>
<?php $_smarty_tpl->_capture_stack[0][] = array("template-order-stocks-edit", null, null); ob_start(); ?>
    
        {% var sku = o.sku; %}
        {% var stocks = o.stocks; %}
        {% var item_id = o.item_id; %}
        <td class="valign-top align-right nowrap s-orders-product-stocks">
            {% if (sku && !$.isEmptyObject(sku.stock)) { %}
                @ <select name="stock[edit][{%#item_id%}]" class="s-orders-stock">
                    {% for (var i = 0; i < stocks.length; i += 1) { %}
                        {% var stock_id = stocks[i].id; %}
                        <option value="{%#stock_id%}" 
                                           data-icon="{%#''+sku.icons[stock_id]%}"
                                      >
                            {%#stocks[i].name%}
                        </option>
                    {% } %}
                </select>
                <em class="errormsg s-error-item-stock_id"></em>
            {% } else { %}
                <input type="hidden" name="stock[edit][{%#item_id%}]" value="0">
            {% } %}
        </td>

    
<?php list($_capture_buffer, $_capture_assign, $_capture_append) = array_pop($_smarty_tpl->_capture_stack[0]);
if (!empty($_capture_buffer)) {
 if (isset($_capture_assign)) $_smarty_tpl->assign($_capture_assign, ob_get_contents());
 if (isset( $_capture_append)) $_smarty_tpl->append( $_capture_append, ob_get_contents());
 Smarty::$_smarty_vars['capture'][$_capture_buffer]=ob_get_clean();
} else $_smarty_tpl->capture_error();?>
<?php $_smarty_tpl->_capture_stack[0][] = array("template-order-stocks-add", null, null); ob_start(); ?>
    
        {% var sku = o.sku; %}
        {% var stocks = o.stocks; %}
        {% var index = o.index; %}
        <td class="valign-top align-right nowrap s-orders-product-stocks">
            {% if (sku && !$.isEmptyObject(sku.stock)) { %}
                @ <select name="stock[add][{%#index%}][product]" class="s-orders-stock">
                    {% for (var i = 0; i < stocks.length; i += 1) { %}
                        {% var stock_id = stocks[i].id; %}
                        <option value="{%#stocks[i].id%}" 
                                          data-icon="{%#''+sku.icons[stock_id]%}"
                                      >
                            {%#stocks[i].name%}
                        </option>
                    {% } %}
                </select>
                <em class="errormsg s-error-item-stock_id"></em>
            {% } else { %}
                <input type="hidden" name="stock[add][{%#index%}][product]" value="0">
            {% } %}
        </td>
    
<?php list($_capture_buffer, $_capture_assign, $_capture_append) = array_pop($_smarty_tpl->_capture_stack[0]);
if (!empty($_capture_buffer)) {
 if (isset($_capture_assign)) $_smarty_tpl->assign($_capture_assign, ob_get_contents());
 if (isset( $_capture_append)) $_smarty_tpl->append( $_capture_append, ob_get_contents());
 Smarty::$_smarty_vars['capture'][$_capture_buffer]=ob_get_clean();
} else $_smarty_tpl->capture_error();?>
<?php $_smarty_tpl->_capture_stack[0][] = array("template-order-services", null, null); ob_start(); ?>
    
    <ul class="menu-v compact small s-orders-services">
        {% var options = o.options || {}; %}
        {% var index = options.index || '0'; %}
        {% var currency = options.currency; %}
        {% var services = o.services; %}
        {% var service_ids = o.service_ids; %}
        {% for (var i = 0, n = service_ids.length; i < n; i += 1) { %}
            {% var service_id = service_ids[i]; %}
            {% var service = services[service_id]; %}
            {% var multi_variants = $.shop.helper.size(service.variants) > 1; %}
            <li>
                <label>
                    <input type="checkbox" name="service[add][{%#index%}][]" value="{%#service_id%}" 
                                          {% if (service.checked) { %}checked="checked"{% } %}>
                          {%=service.name%}
                          {% if (!multi_variants) { %}
                              <strong>{%#service.variants[service.variant_id].price_str%}</strong>
                          {% } %}
                </label>
                {% if (multi_variants) { %}
                    <select name="variant[add][{%#index%}][{%#service_id%}]" class="s-orders-service-variant">
                        {% for (var variant_id in service.variants) { %}
                            {% var variant = service.variants[variant_id]; %}
                            <option value="{%#variant.id%}" data-price="{%#''+variant.price%}" 
                                                   data-currency="{%#variant.currency%}"
                                                   data-price="{%#variant.price%}"
                                                   {% if (service.currency === $.order_edit.getPercentSymbol()) { %}data-percent-price="{%#variant.percent_price%}"{% } %}
                                {% if (variant.status == <?php echo shopProductServicesModel::STATUS_DEFAULT;?>
) { %}selected="selected"{% } %}>
                                {%=variant.name%} ({%#variant.price_str%})
                            </option>
                        {% } %}
                    </select>
                {% } else { %}
                    <input type="hidden" name="variant[add][{%#index%}][{%#service_id%}]" value="{%#service.variant_id%}">
                {% } %}
                <input type="text" name="price[add][{%#index%}][service][{%#service_id%}]" value="{%#''+service.price%}" 
                                      data-currency="{%#service.currency%}"
                                      data-price="{%#service.price%}"
                                      {% if (service.currency === $.order_edit.getPercentSymbol()) { %}data-percent-price="{%#service.percent_price%}"{% } %}
                        class="short s-orders-service-price" >{%#currency%}
            </li>
        {% } %}
    </ul>
    
<?php list($_capture_buffer, $_capture_assign, $_capture_append) = array_pop($_smarty_tpl->_capture_stack[0]);
if (!empty($_capture_buffer)) {
 if (isset($_capture_assign)) $_smarty_tpl->assign($_capture_assign, ob_get_contents());
 if (isset( $_capture_append)) $_smarty_tpl->append( $_capture_append, ob_get_contents());
 Smarty::$_smarty_vars['capture'][$_capture_buffer]=ob_get_clean();
} else $_smarty_tpl->capture_error();?>



<?php  $_smarty_tpl->tpl_vars['template_content'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['template_content']->_loop = false;
 $_smarty_tpl->tpl_vars['template'] = new Smarty_Variable;
 $_from = Smarty::$_smarty_vars['capture']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['template_content']->key => $_smarty_tpl->tpl_vars['template_content']->value){
$_smarty_tpl->tpl_vars['template_content']->_loop = true;
 $_smarty_tpl->tpl_vars['template']->value = $_smarty_tpl->tpl_vars['template_content']->key;
?>
    <?php if ($_smarty_tpl->tpl_vars['template_content']->value&&(strpos($_smarty_tpl->tpl_vars['template']->value,'template-')===0)){?>
        <script id="<?php echo $_smarty_tpl->tpl_vars['template']->value;?>
" type="text/html">
            <?php echo smarty_modifier_replace($_smarty_tpl->tpl_vars['template_content']->value,'</','<\/');?>

        </script>
        <?php $_smarty_tpl->_capture_stack[0][] = array($_smarty_tpl->tpl_vars['template']->value, null, null); ob_start(); ?><?php list($_capture_buffer, $_capture_assign, $_capture_append) = array_pop($_smarty_tpl->_capture_stack[0]);
if (!empty($_capture_buffer)) {
 if (isset($_capture_assign)) $_smarty_tpl->assign($_capture_assign, ob_get_contents());
 if (isset( $_capture_append)) $_smarty_tpl->append( $_capture_append, ob_get_contents());
 Smarty::$_smarty_vars['capture'][$_capture_buffer]=ob_get_clean();
} else $_smarty_tpl->capture_error();?>
    <?php }?>
<?php } ?>

<script type="text/javascript">$(function() {
    $.orders.init({
        view: '<?php echo $_smarty_tpl->tpl_vars['default_view']->value;?>
'
    });
});</script>
<?php }} ?>