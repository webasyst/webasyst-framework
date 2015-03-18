<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 14:25:47
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-system/webasyst/templates/actions/login/Login.html" */ ?>
<?php /*%%SmartyHeaderCode:1286604315506bdbb4c3a05-37971762%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '0faf38f81a1dd05cc1e33f8d6cc6c8a5aa612492' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/webasyst/templates/actions/login/Login.html',
      1 => 1426250343,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '1286604315506bdbb4c3a05-37971762',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'title_style' => 0,
    'title' => 0,
    'error' => 0,
    'options' => 0,
    'login' => 0,
    'password' => 0,
    'remember' => 0,
    'wa' => 0,
    'back_on_cancel' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5506bdbb56c927_51914664',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5506bdbb56c927_51914664')) {function content_5506bdbb56c927_51914664($_smarty_tpl) {?><form method="post" action="">
<input type="hidden" name="wa_auth_login" value="1" />
<h1<?php if (!empty($_smarty_tpl->tpl_vars['title_style']->value)){?> style="<?php echo $_smarty_tpl->tpl_vars['title_style']->value;?>
"<?php }?>><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['title']->value, ENT_QUOTES, 'UTF-8', true);?>
</h1>
<?php if (!empty($_smarty_tpl->tpl_vars['error']->value)){?>
<div id="wa-login-error" class="error"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['error']->value, ENT_QUOTES, 'UTF-8', true);?>
</div>
<?php }elseif(!empty($_smarty_tpl->tpl_vars['options']->value['description'])){?>
<div class="wa-login-description"><span><?php echo _ws($_smarty_tpl->tpl_vars['options']->value['description']);?>
</span></div>
<?php }else{ ?>
<div id="wa-login-error" class="error"></div>
<?php }?>
<div class="fields form">
    <div class="field">
        <div class="name">
        <?php if ($_smarty_tpl->tpl_vars['options']->value['login']=='email'){?>Email<?php }else{ ?>Login<?php }?>:
        </div>
        <div class="value">
            <input type="text" class="wa-login-text-input" id="wa-login-input" value="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['login']->value, ENT_QUOTES, 'UTF-8', true);?>
" name="login" placeholder="<?php if ($_smarty_tpl->tpl_vars['options']->value['login']=='email'){?>Email<?php }else{ ?>Login name or email<?php }?>" />
        </div>
    </div>
    <div class="field">
        <div class="name">
            Password:
        </div>
        <div class="value">
            <input type="password" class="wa-login-text-input" name="password" value="<?php if (!empty($_smarty_tpl->tpl_vars['password']->value)){?><?php echo $_smarty_tpl->tpl_vars['password']->value;?>
<?php }?>" placeholder="Password" />
            <?php if ($_smarty_tpl->tpl_vars['options']->value['remember_enabled']){?>
            <div class="wa-remember-me">
                <input id="remember-me" name="remember" type="checkbox" <?php if (!empty($_smarty_tpl->tpl_vars['remember']->value)||$_smarty_tpl->tpl_vars['wa']->value->post('remember')){?>checked="checked" <?php }?> value="1" /> <label for="remember-me">Remember me</label>
            </div>
            <?php }else{ ?>
            <br><br>
            <?php }?>
        </div>
    </div>
    <div class="field">
        <div class="value submit">
            <input type="submit" value="Sign in" class="button wa-login-submit" id="wa-login-submit">
            <?php if ((!empty($_smarty_tpl->tpl_vars['back_on_cancel']->value))){?>
                or
                <a class="wa-logincancel" href="<?php echo $_smarty_tpl->tpl_vars['back_on_cancel']->value;?>
">cancel</a>
            <?php }?>
            <a href="?forgotpassword" class="wa-forgotpassword underline">Forgot password?</a>
        </div>
    </div>
</div>
</form>
<script type="text/javascript">
$(function() {
    // When user clicks "forgot password" link, send email (if already typed in)
    // to show on the next form by default.
    $('.wa-forgotpassword').click(function() {
        var val = $('#wa-login-input').val();
        if (!val) {
            return true;
        }
        var form = $('<form action="'+$(this).attr('href')+'" method="POST">'+
                        '<input type="hidden" name="ignore" value="1">'+
                        '<input type="hidden" name="login" value="">'+
                     '</form>');
        form.find('[name="login"]').val(val);
        form.appendTo('body');
        form.submit();
        return false;
    });
});
</script><?php }} ?>