<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 14:25:47
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-system/webasyst/templates/layouts/Login.html" */ ?>
<?php /*%%SmartyHeaderCode:13254336805506bdbb5776c3-04270871%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'b9b38185e5d44b37caebd0cd98e73430e9549914' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/webasyst/templates/layouts/Login.html',
      1 => 1416918117,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '13254336805506bdbb5776c3-04270871',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'wa' => 0,
    'env' => 0,
    'wa_url' => 0,
    'background' => 0,
    'stretch' => 0,
    'dialog_class' => 0,
    'error' => 0,
    'dialog_style' => 0,
    'content' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5506bdbb5d58d9_03998140',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5506bdbb5d58d9_03998140')) {function content_5506bdbb5d58d9_03998140($_smarty_tpl) {?><!DOCTYPE html><html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $_smarty_tpl->tpl_vars['wa']->value->accountName();?>
</title>
    <?php if ($_smarty_tpl->tpl_vars['env']->value=='backend'){?><meta name="robots" content="noindex, nofollow"/><?php }?>
    <?php echo $_smarty_tpl->tpl_vars['wa']->value->css();?>

    <style type="text/css">
        .dialog.newpassword .dialog-window {
            width: 615px;
            min-width: 615px;
        }
        #wa-login .newpassword h1 { margin-top: 10px}
        #wa-login .newpassword .field .name { width: auto}
        #wa-login .newpassword .field .value { margin-left: 250px; margin-bottom: 5px}
        #wa-login p.i-error { margin: 6px 0 10px; color: red }
    </style>
    <script src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>
    <script src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery-wa/wa.dialog.js" type="text/javascript"></script>
    <script src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery-plugins/blur/blur.min.js" type="text/javascript"></script>
</head>
<body>
<div id="wa-login"<?php if ($_smarty_tpl->tpl_vars['background']->value){?> style="background-image: url('<?php if ($_smarty_tpl->tpl_vars['background']->value&&!$_smarty_tpl->tpl_vars['stretch']->value){?><?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
<?php echo $_smarty_tpl->tpl_vars['background']->value;?>
<?php }?><?php if (!$_smarty_tpl->tpl_vars['background']->value){?><?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/img/cloth-background.jpg<?php }?>')"<?php }?>>
    <?php if ($_smarty_tpl->tpl_vars['background']->value&&$_smarty_tpl->tpl_vars['stretch']->value){?>
    <img id="wa-login-stretched-background" src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
<?php echo $_smarty_tpl->tpl_vars['background']->value;?>
" />
    <?php }?>
    <div class="dialog<?php if (!empty($_smarty_tpl->tpl_vars['dialog_class']->value)){?> <?php echo $_smarty_tpl->tpl_vars['dialog_class']->value;?>
<?php }?> width500px height300px<?php if (!empty($_smarty_tpl->tpl_vars['error']->value)){?> error<?php }?>" id="wa-login-dialog">
        <div class="dialog-background"></div>
        <div class="dialog-window"<?php if (!empty($_smarty_tpl->tpl_vars['dialog_style']->value)){?> style="<?php echo $_smarty_tpl->tpl_vars['dialog_style']->value;?>
"<?php }?>>
        <div class="dialog-content">
            <div class="dialog-content-indent">
                <?php echo $_smarty_tpl->tpl_vars['content']->value;?>

            </div>
        </div>
        <div class="dialog-buttons">
            <div class="wa-poweredby">
                <a href="http://www.webasyst.com/" title="Webasyst">
                    <span class="wa-dots"></span>
                </a>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $('#wa-login-dialog').waDialog({ 'esc': false});
        $('#wa-login-input').focus();
        var input = $('input:text')[0] || $('input:password')[0];
        if (input) {
            input.focus();
        }
    });
</script>
</body>
</html><?php }} ?>