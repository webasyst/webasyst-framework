<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 11:41:14
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/templates/actions/order/Order.html" */ ?>
<?php /*%%SmartyHeaderCode:19236318015506972aba08e8-73521885%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'b56665caaf94eefe9ca0b28b37ed62529b9ea61e' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/shop/templates/actions/order/Order.html',
      1 => 1426233163,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '19236318015506972aba08e8-73521885',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'order' => 0,
    'top_buttons' => 0,
    'b' => 0,
    'backend_order' => 0,
    '_' => 0,
    'printable_docs' => 0,
    'printable_doc' => 0,
    'plugin_id' => 0,
    'filter_params_str' => 0,
    'wa' => 0,
    'last_action_datetime' => 0,
    'buttons' => 0,
    'customer' => 0,
    'main_contact_info' => 0,
    'top_field' => 0,
    'shipping_address' => 0,
    'shipping_address_text' => 0,
    'params' => 0,
    'custom_fields' => 0,
    'f' => 0,
    'tracking' => 0,
    'billing_address' => 0,
    'item' => 0,
    'wa_app_static_url' => 0,
    'subtotal' => 0,
    'bottom_buttons' => 0,
    'log' => 0,
    'row' => 0,
    '_tmp' => 0,
    'pl' => 0,
    'count_new' => 0,
    'currency' => 0,
    'offset' => 0,
    'timeout' => 0,
    'filter_params' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5506972b050853_35272799',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5506972b050853_35272799')) {function content_5506972b050853_35272799($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_wa_datetime')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/modifier.wa_datetime.php';
if (!is_callable('smarty_modifier_truncate')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.truncate.php';
?><?php if (empty($_smarty_tpl->tpl_vars['order']->value)){?>

<div class="block double-padded align-center blank">
    <br><br><br><br>
    <span class="gray large">В этом списке нет заказов.</span>
    <div class="clear-left"></div>
</div>
</div>

<?php }else{ ?>

<div class="block double-padded s-order">
    <div class="float-right s-order-aux">

        <div class="block half-padded s-printable-print-button align-center">
            <input type="button" value="Печать" onClick="window.print();">
        </div>

        <!-- order action links -->
        <ul class="menu-v with-icons compact workflow-actions">
            <li><a href="#" target="_blank" class="js-print" data-selector="div.s-order"><i class="icon16 print"></i>Версия для печати</a></li>
            <?php  $_smarty_tpl->tpl_vars['b'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['b']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['top_buttons']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['b']->key => $_smarty_tpl->tpl_vars['b']->value){
$_smarty_tpl->tpl_vars['b']->_loop = true;
?>
            <li><?php echo $_smarty_tpl->tpl_vars['b']->value;?>
</li>
            <?php } ?>

            <!-- plugin hook: 'backend_order.action_link' -->
            
            <?php if (!empty($_smarty_tpl->tpl_vars['backend_order']->value)){?><?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['backend_order']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php if ((!empty($_smarty_tpl->tpl_vars['_']->value['action_link']))){?><li><?php echo $_smarty_tpl->tpl_vars['_']->value['action_link'];?>
</li><?php }?><?php } ?><?php }?>

        </ul>
        <div class="workflow-content"></div>

        <!-- printable docs -->
        <?php if (count($_smarty_tpl->tpl_vars['printable_docs']->value)){?>
        <br>
        <ul class="menu-v compactt js-printable-docs">
            <?php  $_smarty_tpl->tpl_vars['printable_doc'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['printable_doc']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin_id'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['printable_docs']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['printable_doc']->key => $_smarty_tpl->tpl_vars['printable_doc']->value){
$_smarty_tpl->tpl_vars['printable_doc']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin_id']->value = $_smarty_tpl->tpl_vars['printable_doc']->key;
?>
            <li>
                <label>
                    <input type="checkbox" checked="true" value="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['printable_doc']->value['url'], ENT_QUOTES, 'UTF-8', true);?>
" data-name="<?php echo $_smarty_tpl->tpl_vars['plugin_id']->value;?>
" data-target="_printform_<?php echo ((string)$_smarty_tpl->tpl_vars['plugin_id']->value)."_".((string)$_smarty_tpl->tpl_vars['order']->value['id']);?>
">
                    <?php echo htmlspecialchars($_smarty_tpl->tpl_vars['printable_doc']->value['name'], ENT_QUOTES, 'UTF-8', true);?>

                </label>
            </li>
            <?php } ?>
        </ul>
        <input type="button" value="Печать" class="js-printable-docs">
        <br><br>
        <?php }?>

        <!-- order aux info -->
        <p class="gray">
            Заказ создан: <strong><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['order']->value['create_datetime'],"humandatetime");?>
</strong><br>
            <?php if (!empty($_smarty_tpl->tpl_vars['order']->value['params']['referer'])){?>Реферер: <strong><a href="<?php echo $_smarty_tpl->tpl_vars['order']->value['params']['referer'];?>
" target="_blank" style="color: #03c;"><?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['order']->value['params']['referer'],42);?>
</a></strong><br><?php }?>
            <?php if (!empty($_smarty_tpl->tpl_vars['order']->value['params']['storefront'])){?>Витрина: <strong><?php echo $_smarty_tpl->tpl_vars['order']->value['params']['storefront'];?>
</strong><br><?php }?>
            <?php if (!empty($_smarty_tpl->tpl_vars['order']->value['params']['keyword'])){?>Ключевое слово: <strong><?php echo $_smarty_tpl->tpl_vars['order']->value['params']['keyword'];?>
</strong><br><?php }?>
            <?php if (!empty($_smarty_tpl->tpl_vars['order']->value['params']['ip'])){?>IP-адрес: <strong><?php echo $_smarty_tpl->tpl_vars['order']->value['params']['ip'];?>
</strong><br><?php }?>

            <!-- plugin hook: 'backend_order.aux_info' -->
            
            <?php if (!empty($_smarty_tpl->tpl_vars['backend_order']->value)){?><?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['backend_order']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php if ((!empty($_smarty_tpl->tpl_vars['_']->value['aux_info']))){?><?php echo $_smarty_tpl->tpl_vars['_']->value['aux_info'];?>
<br><?php }?><?php } ?><?php }?>
        </p>

    </div>

    <!-- order title -->
    <h1 id="s-order-title">
        <a href="#/orders/<?php if ($_smarty_tpl->tpl_vars['filter_params_str']->value){?><?php echo $_smarty_tpl->tpl_vars['filter_params_str']->value;?>
&view=table/<?php }?>" class="back order-list" style="display:none;">&larr; Заказы</a>
        <a href="#/order/<?php echo $_smarty_tpl->tpl_vars['order']->value['id'];?>
/<?php if ($_smarty_tpl->tpl_vars['filter_params_str']->value){?><?php echo $_smarty_tpl->tpl_vars['filter_params_str']->value;?>
/<?php }?>" class="back read-mode" style="display:none;">&larr; Назад</a>
        <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->orderId($_smarty_tpl->tpl_vars['order']->value['id']);?>

        <i class="icon16 loading" style="display:none"></i>

        <!-- plugin hook: 'backend_order.title_suffix' -->
        
        <?php if (!empty($_smarty_tpl->tpl_vars['backend_order']->value)){?><?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['backend_order']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php echo ifset($_smarty_tpl->tpl_vars['_']->value['title_suffix']);?>
<?php } ?><?php }?>

        <?php if ($_smarty_tpl->tpl_vars['order']->value['state']){?>
        <span class="small" style="font-size: 16px; margin-left: 10px; position: relative; top: -2px; <?php echo $_smarty_tpl->tpl_vars['order']->value['state']->getStyle();?>
">
            <i class="<?php echo $_smarty_tpl->tpl_vars['order']->value['state']->getOption('icon');?>
" style="margin-top: 7px;"></i><span style="margin-right: 10px;"><?php echo $_smarty_tpl->tpl_vars['order']->value['state']->getName();?>
</span>
            <?php if ($_smarty_tpl->tpl_vars['last_action_datetime']->value){?>
            <em class="hint nowrap"><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['last_action_datetime']->value,'humandatetime');?>
</em>
            <em class="hint nowrap s-print-only"><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['last_action_datetime']->value,'datetime');?>
</em>
            <?php }?>
        </span>
        <?php }else{ ?>
        Неизвестный статус: <?php echo $_smarty_tpl->tpl_vars['order']->value['state_id'];?>

        <?php }?>

    </h1>

    <!-- order action buttons -->
    <div class="block not-padded s-order-readable">
        <ul class="menu-h s-order-actions workflow-actions">
            <?php  $_smarty_tpl->tpl_vars['b'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['b']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['buttons']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['b']->key => $_smarty_tpl->tpl_vars['b']->value){
$_smarty_tpl->tpl_vars['b']->_loop = true;
?>
            <li><?php echo $_smarty_tpl->tpl_vars['b']->value;?>
</li>
            <?php } ?>

            <!-- plugin hook: 'backend_order.action_button' -->
            
            <?php if (!empty($_smarty_tpl->tpl_vars['backend_order']->value)){?><?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['backend_order']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php if ((!empty($_smarty_tpl->tpl_vars['_']->value['action_button']))){?><li><?php echo $_smarty_tpl->tpl_vars['_']->value['action_button'];?>
</li><?php }?><?php } ?><?php }?>
        </ul>
        <div class="workflow-content" id="workflow-content"></div>
    </div>

    <!-- customer info -->
    <div class="profile image50px">
        <div class="image">
            <a href="?action=customers#/id/<?php echo $_smarty_tpl->tpl_vars['order']->value['contact']['id'];?>
"><img src="<?php echo $_smarty_tpl->tpl_vars['order']->value['contact']['photo_50x50'];?>
" class="userpic" /></a>
        </div>
        <div class="details">
            <h3>
                <a href="?action=customers#/id/<?php echo $_smarty_tpl->tpl_vars['order']->value['contact']['id'];?>
"><?php echo $_smarty_tpl->tpl_vars['order']->value['contact']['name'];?>
</a>
                <?php if ($_smarty_tpl->tpl_vars['customer']->value['number_of_orders']==1){?>
                <em class="hint">Это первый заказ данного покупателя</em>
                <?php }else{ ?>
                <em class="hint"><a href="#/orders/contact_id=<?php echo $_smarty_tpl->tpl_vars['order']->value['contact_id'];?>
/"><?php echo _w('%d order','%d orders',$_smarty_tpl->tpl_vars['customer']->value['number_of_orders']);?>
</a></em>
                <?php }?>
            </h3>
            <?php if ($_smarty_tpl->tpl_vars['main_contact_info']->value){?>
            <ul class="menu-v with-icons compact">
                <?php  $_smarty_tpl->tpl_vars['top_field'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['top_field']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['main_contact_info']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['top_field']->key => $_smarty_tpl->tpl_vars['top_field']->value){
$_smarty_tpl->tpl_vars['top_field']->_loop = true;
?>
                <li><?php if ($_smarty_tpl->tpl_vars['top_field']->value['id']!='im'){?><i class="icon16 <?php echo $_smarty_tpl->tpl_vars['top_field']->value['id'];?>
"></i><?php }?><?php echo $_smarty_tpl->tpl_vars['top_field']->value['value'];?>
</li>
                <?php } ?>
            </ul>
            <?php }?>
        </div>
    </div>

    <!-- plugin hook: 'backend_order.info_section' -->
    
    <?php if (!empty($_smarty_tpl->tpl_vars['backend_order']->value)){?><?php  $_smarty_tpl->tpl_vars['_'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['backend_order']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_']->key => $_smarty_tpl->tpl_vars['_']->value){
$_smarty_tpl->tpl_vars['_']->_loop = true;
?><?php if ((!empty($_smarty_tpl->tpl_vars['_']->value['info_section']))){?><?php echo $_smarty_tpl->tpl_vars['_']->value['info_section'];?>
<?php }?><?php } ?><?php }?>

    <div class="clear-right"></div>

    <!-- order comment -->
    <?php if ($_smarty_tpl->tpl_vars['order']->value['comment']){?>
    <pre class="block double-padded s-order-comment"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['order']->value['comment'], ENT_QUOTES, 'UTF-8', true);?>
</pre>
    <?php }?>

    <?php if ($_smarty_tpl->tpl_vars['shipping_address']->value){?>
    <!-- order shipping & billing addresses -->
    <div class="float-right s-order-aux">
        <a target="_blank" href="https://maps.google.ru/maps?q=<?php echo urlencode($_smarty_tpl->tpl_vars['shipping_address_text']->value);?>
&z=15">
            <img src="https://maps.googleapis.com/maps/api/staticmap?center=<?php echo urlencode($_smarty_tpl->tpl_vars['shipping_address_text']->value);?>
&zoom=13&size=200x150&markers=color:red%7Clabel:A%7C<?php echo urlencode($_smarty_tpl->tpl_vars['shipping_address_text']->value);?>
&sensor=false" />
        </a>
    </div>
    <?php }?>

    <h3><span class="gray">Доставка<?php if (!empty($_smarty_tpl->tpl_vars['params']->value['shipping_name'])){?> &mdash;<?php }?></span> <strong><?php echo ifset($_smarty_tpl->tpl_vars['params']->value['shipping_name']);?>
</strong></h3>
    <?php if ($_smarty_tpl->tpl_vars['shipping_address']->value!==null){?>
    <p class="s-order-address">
        <?php echo $_smarty_tpl->tpl_vars['shipping_address']->value;?>

    </p>
    <?php if (!empty($_smarty_tpl->tpl_vars['custom_fields']->value)){?>
    <p>
        <?php  $_smarty_tpl->tpl_vars['f'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['f']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['custom_fields']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['f']->key => $_smarty_tpl->tpl_vars['f']->value){
$_smarty_tpl->tpl_vars['f']->_loop = true;
?>
        <?php echo $_smarty_tpl->tpl_vars['f']->value['title'];?>
: <?php echo $_smarty_tpl->tpl_vars['f']->value['value'];?>
<br>
        <?php } ?>
    </p>
    <?php }?>
    <!-- shipping plugin output -->
    <?php if (!empty($_smarty_tpl->tpl_vars['params']->value['tracking_number'])){?>
    <h3><span class="gray">Номер отправления:</span> <strong><?php echo $_smarty_tpl->tpl_vars['params']->value['tracking_number'];?>
</strong></h3>
    <?php }?>
    <?php if (!empty($_smarty_tpl->tpl_vars['tracking']->value)&&$_smarty_tpl->tpl_vars['order']->value['state_id']!='completed'){?>
    <blockquote class="plugin s-tracking">
        <?php echo $_smarty_tpl->tpl_vars['tracking']->value;?>

    </blockquote>
    <?php }?>
    <div class="">
        <a href="">
            <img src="/wa-apps/shop/img/sdek.png" height="30"/>
        </a>
    </div>
    <?php }?>

    <?php if (!empty($_smarty_tpl->tpl_vars['params']->value['payment_name'])){?>
    <h3><span class="gray">Оплата &mdash;</span> <strong><?php echo $_smarty_tpl->tpl_vars['params']->value['payment_name'];?>
</strong></h3>
    <?php if ($_smarty_tpl->tpl_vars['billing_address']->value!==null){?>
    <p class="s-order-address">
        <?php echo $_smarty_tpl->tpl_vars['billing_address']->value;?>

    </p>
    <?php }?>
    <?php }?>

    <?php if (!empty($_smarty_tpl->tpl_vars['order']->value['coupon'])){?>
    <h3><span class="gray">Скидка </span> <strong>(<?php echo $_smarty_tpl->tpl_vars['order']->value['coupon']['code'];?>
)</strong></h3>
    <?php }?>

    <div class="clear-right"></div>

    <!-- order content -->
    <?php if ($_smarty_tpl->tpl_vars['order']->value['items']){?>
    <table id="s-order-items" class="light s-order-readable">
        <tr>
            <th colspan="2"></th>
            <th class="align-right">Кол-во</th>
            <th class="align-right">Итого</th>
        </tr>
        <?php $_smarty_tpl->tpl_vars['subtotal'] = new Smarty_variable(0, null, 0);?>
        <?php  $_smarty_tpl->tpl_vars['item'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['item']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['order']->value['items']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['item']->key => $_smarty_tpl->tpl_vars['item']->value){
$_smarty_tpl->tpl_vars['item']->_loop = true;
?>
        <tr data-id="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
" <?php if ($_smarty_tpl->tpl_vars['item']->value['type']=='service'){?> class="small"<?php }?>>
            <td class="min-width valign-top">
                <?php if ($_smarty_tpl->tpl_vars['item']->value['type']!='service'){?>
                <?php if (!empty($_smarty_tpl->tpl_vars['item']->value['image_id'])){?>
                <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productImgHtml(array('id'=>$_smarty_tpl->tpl_vars['item']->value['product_id'],'image_id'=>$_smarty_tpl->tpl_vars['item']->value['image_id'],'ext'=>$_smarty_tpl->tpl_vars['item']->value['ext']),'48x48');?>

                <?php }else{ ?>
                <img src="<?php echo $_smarty_tpl->tpl_vars['wa_app_static_url']->value;?>
img/image-dummy-small.png" class="not-found" style="width: 48px; height: 48px;">
                <?php }?>
                <?php }?>
            </td>
            <td>
                <?php if ($_smarty_tpl->tpl_vars['item']->value['type']=='service'){?><span class="gray s-overhanging-plus">+</span> <?php }?>
                <a href="?action=products#/<?php if ($_smarty_tpl->tpl_vars['item']->value['type']=='product'){?>product/<?php echo $_smarty_tpl->tpl_vars['item']->value['product_id'];?>
<?php }else{ ?>services/<?php echo $_smarty_tpl->tpl_vars['item']->value['service_id'];?>
<?php }?>/"><?php echo $_smarty_tpl->tpl_vars['item']->value['name'];?>
</a>
                <?php if (!empty($_smarty_tpl->tpl_vars['item']->value['sku_code'])){?>
                <span class="hint"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['item']->value['sku_code'], ENT_QUOTES, 'UTF-8', true);?>
</span>
                <?php }?>
                <?php if (!empty($_smarty_tpl->tpl_vars['item']->value['stock'])){?>
                <span class="small">@<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['item']->value['stock']['name'], ENT_QUOTES, 'UTF-8', true);?>
</span>
                <?php }?>
                <?php if (!empty($_smarty_tpl->tpl_vars['item']->value['stock_icon'])){?>
                <?php echo $_smarty_tpl->tpl_vars['item']->value['stock_icon'];?>

                <?php }?>
            </td>
            <td class="align-right nowrap"><span class="gray"><?php echo wa_currency($_smarty_tpl->tpl_vars['item']->value['price'],$_smarty_tpl->tpl_vars['order']->value['currency']);?>
 &times;</span> <?php echo $_smarty_tpl->tpl_vars['item']->value['quantity'];?>
</td>
            <td class="align-right nowrap"><?php echo wa_currency($_smarty_tpl->tpl_vars['item']->value['price']*$_smarty_tpl->tpl_vars['item']->value['quantity'],$_smarty_tpl->tpl_vars['order']->value['currency']);?>
</td>
        </tr>
        <?php $_smarty_tpl->tpl_vars['subtotal'] = new Smarty_variable($_smarty_tpl->tpl_vars['subtotal']->value+$_smarty_tpl->tpl_vars['item']->value['price']*$_smarty_tpl->tpl_vars['item']->value['quantity'], null, 0);?>
        <?php } ?>
        <tr class="no-border">
            <td colspan="2"></td>
            <td class="align-right"><br>Подытог</td>
            <td class="align-right nowrap"><br><?php echo wa_currency($_smarty_tpl->tpl_vars['subtotal']->value,$_smarty_tpl->tpl_vars['order']->value['currency']);?>
</td>
        </tr>
        <tr class="no-border">
            <td colspan="2"></td>
            <td class="align-right">Скидка</td>
            <td class="align-right nowrap">&minus; <?php echo wa_currency($_smarty_tpl->tpl_vars['order']->value['discount'],$_smarty_tpl->tpl_vars['order']->value['currency']);?>
</td>
        </tr>
        <?php if (isset($_smarty_tpl->tpl_vars['params']->value['shipping_name'])||$_smarty_tpl->tpl_vars['order']->value['shipping']>0){?>
        <tr class="no-border">
            <td colspan="2"></td>
            <td class="align-right">Доставка</td>
            <td class="align-right nowrap"><?php echo wa_currency($_smarty_tpl->tpl_vars['order']->value['shipping'],$_smarty_tpl->tpl_vars['order']->value['currency']);?>
</td>
        </tr>
        <?php }?>
        <tr class="no-border">
            <td colspan="2"></td>
            <td class="align-right">Налог</td>
            <td class="align-right nowrap"><?php echo wa_currency($_smarty_tpl->tpl_vars['order']->value['tax'],$_smarty_tpl->tpl_vars['order']->value['currency']);?>
</td>
        </tr>
        <tr class="no-border bold large" style="font-size: 150%;">
            <td colspan="2"></td>
            <td class="align-right">Итого</td>
            <td class="align-right nowrap"><?php echo wa_currency($_smarty_tpl->tpl_vars['order']->value['total'],$_smarty_tpl->tpl_vars['order']->value['currency']);?>
</td>
        </tr>
    </table>
    <?php }?>

    <div id="s-order-items-edit" class="s-order-editable" style="display:none;"></div>

    

    <!-- order processing timeline -->
    <div class="s-order-readable s-order-timeline">
        <h3>История выполнения заказа</h3><br>
        <p class="workflow-actions">
            <?php  $_smarty_tpl->tpl_vars['b'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['b']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['bottom_buttons']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['b']->key => $_smarty_tpl->tpl_vars['b']->value){
$_smarty_tpl->tpl_vars['b']->_loop = true;
?>
            <?php echo $_smarty_tpl->tpl_vars['b']->value;?>

            <?php } ?>
        </p>
        <div class="workflow-content"></div>
        <div class="fields">
            <?php  $_smarty_tpl->tpl_vars['row'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['row']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['log']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['row']->key => $_smarty_tpl->tpl_vars['row']->value){
$_smarty_tpl->tpl_vars['row']->_loop = true;
?>
            <div class="field">
                <div class="name"><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['row']->value['datetime'],"humandatetime");?>
</div>
                <div class="value">
                    <?php if ($_smarty_tpl->tpl_vars['row']->value['action_id']){?>
                    <?php if ($_smarty_tpl->tpl_vars['row']->value['contact_id']){?>
                    <i class="icon16 userpic20" style="background-image: url('<?php echo waContact::getPhotoUrl($_smarty_tpl->tpl_vars['row']->value['contact_id'],$_smarty_tpl->tpl_vars['row']->value['contact_photo'],20);?>
');"></i>
                    <?php echo htmlspecialchars($_smarty_tpl->tpl_vars['row']->value['contact_name'], ENT_QUOTES, 'UTF-8', true);?>

                    <?php }elseif($_smarty_tpl->tpl_vars['row']->value['action_id']=='callback'&&$_smarty_tpl->tpl_vars['row']->value['text']){?>
                    <?php $_smarty_tpl->tpl_vars['_tmp'] = new Smarty_variable(explode(' ',$_smarty_tpl->tpl_vars['row']->value['text'],2), null, 0);?>
                    <?php $_smarty_tpl->tpl_vars['pl'] = new Smarty_variable(shopPayment::getPluginInfo($_smarty_tpl->tpl_vars['_tmp']->value[0]), null, 0);?>
                    <?php $_smarty_tpl->createLocalArrayVariable('row', null, 0);
$_smarty_tpl->tpl_vars['row']->value['text'] = $_smarty_tpl->tpl_vars['_tmp']->value[1];?>
                    <i class="icon16" style="background-image: url('<?php echo $_smarty_tpl->tpl_vars['pl']->value['icon'][16];?>
');"></i>
                    <?php echo htmlspecialchars($_smarty_tpl->tpl_vars['pl']->value['name'], ENT_QUOTES, 'UTF-8', true);?>

                    <?php }?>
                    <strong><?php if ($_smarty_tpl->tpl_vars['row']->value['action']){?><?php echo $_smarty_tpl->tpl_vars['row']->value['action']->getOption('log_record');?>
<?php }else{ ?><?php echo $_smarty_tpl->tpl_vars['row']->value['action_id'];?>
<?php }?></strong>
                    <?php if ($_smarty_tpl->tpl_vars['row']->value['text']){?><p><?php echo $_smarty_tpl->tpl_vars['row']->value['text'];?>
</p><?php }?>
                    <?php }else{ ?>
                    <?php if ($_smarty_tpl->tpl_vars['row']->value['text']){?> <?php echo $_smarty_tpl->tpl_vars['row']->value['text'];?>
<?php }?>
                    <?php }?>
                </div>
            </div>
            <?php } ?>
            <div class="clear-left"></div>
        </div>
        <div class="clear-left"></div>
    </div>

</div>
<div class="clear-both"></div>
<script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_app_static_url']->value;?>
js/order/order.js?v<?php echo $_smarty_tpl->tpl_vars['wa']->value->version();?>
"></script>
<script type="text/javascript">
                        (function() {
                        var view = "<?php echo $_smarty_tpl->tpl_vars['wa']->value->get('view');?>
";
                                var count_new = <?php if (!empty($_smarty_tpl->tpl_vars['count_new']->value)){?><?php echo $_smarty_tpl->tpl_vars['count_new']->value;?>
{ else}0<?php }?>;
                                var options = {
                                order: <?php echo json_encode($_smarty_tpl->tpl_vars['order']->value);?>
,
                                        currency: '<?php echo $_smarty_tpl->tpl_vars['currency']->value;?>
',
                                        view: view,
                                        offset: <?php echo json_encode($_smarty_tpl->tpl_vars['offset']->value);?>
,
                                };
                                // title has to be overridden in this cases
                                if (view == 'table') {
                        options.title = '<?php ob_start();?><?php echo $_smarty_tpl->tpl_vars['wa']->value->accountName(false);?>
<?php $_tmp1=ob_get_clean();?><?php echo strtr(($_smarty_tpl->tpl_vars['wa']->value->shop->orderId($_smarty_tpl->tpl_vars['order']->value['id'])).(" — ").($_tmp1), array("\\" => "\\\\", "'" => "\\'", "\"" => "\\\"", "\r" => "\\r", "\n" => "\\n", "</" => "<\/" ));?>
';
                                if (count_new) {
                        options.title = '(' + count_new + ') ' + options.title;
                        }
                        }

                        if (!$.order_list || view == 'table') {
                        if ($.order_list) {
                        $.order_list.finit(); // destructor
                        }
                        options.dependencies = options.dependencies || {};
                                options.dependencies.order_list = {
                                view: view,
                                        update_process: {
                                        timeout: <?php echo $_smarty_tpl->tpl_vars['timeout']->value;?>

                                        },
                                        count_new: <?php echo $_smarty_tpl->tpl_vars['count_new']->value;?>
,
                                        title_suffix: '<?php ob_start();?><?php echo $_smarty_tpl->tpl_vars['wa']->value->accountName(false);?>
<?php $_tmp2=ob_get_clean();?><?php echo strtr((' — ').($_tmp2), array("\\" => "\\\\", "'" => "\\'", "\"" => "\\\"", "\r" => "\\r", "\n" => "\\n", "</" => "<\/" ));?>
',
                                        filter_params: <?php echo json_encode($_smarty_tpl->tpl_vars['filter_params']->value);?>
,
                                        filter_params_str: '<?php echo $_smarty_tpl->tpl_vars['filter_params_str']->value;?>
'
                                };
                                $.order.init(options);
                        } else {
                        $.order.init(options);
                        }
                        })();
</script>

<?php }?>
<?php }} ?>