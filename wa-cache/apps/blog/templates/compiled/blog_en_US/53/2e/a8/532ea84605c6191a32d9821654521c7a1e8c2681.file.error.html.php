<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 15:00:09
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/site/themes/default/error.html" */ ?>
<?php /*%%SmartyHeaderCode:7022502585506c5c9721892-68229684%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '532ea84605c6191a32d9821654521c7a1e8c2681' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/site/themes/default/error.html',
      1 => 1423486440,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '7022502585506c5c9721892-68229684',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'error_code' => 0,
    'error_message' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5506c5c9777a11_18997726',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5506c5c9777a11_18997726')) {function content_5506c5c9777a11_18997726($_smarty_tpl) {?><div class="content">
  <div id="page" role="main">
    <h1>
    	<?php if ($_smarty_tpl->tpl_vars['error_code']->value){?><?php echo $_smarty_tpl->tpl_vars['error_code']->value;?>
. <?php }?>
    	<?php if ($_smarty_tpl->tpl_vars['error_message']->value){?><?php echo $_smarty_tpl->tpl_vars['error_message']->value;?>
<?php }else{ ?>Error<?php }?>
    </h1>
    The requested resource is not available.
  </div>
</div>
<?php }} ?>