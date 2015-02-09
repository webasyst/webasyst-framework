<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:37:07
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-system/design/templates/Design.html" */ ?>
<?php /*%%SmartyHeaderCode:52874069054d8a9f39d11c7-99953873%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'bdd7981f4e9d04ca74aec7dab00d6a32c7399624' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/design/templates/Design.html',
      1 => 1416918117,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '52874069054d8a9f39d11c7-99953873',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'options' => 0,
    'wa_url' => 0,
    'wa' => 0,
    'app' => 0,
    'route' => 0,
    'theme' => 0,
    '_route_label' => 0,
    'themes_routes' => 0,
    'r' => 0,
    'themes' => 0,
    'design_url' => 0,
    'current_url' => 0,
    '_url' => 0,
    '_r_label' => 0,
    '_t' => 0,
    'themes_url' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8a9f3b777b5_22334695',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8a9f3b777b5_22334695')) {function content_54d8a9f3b777b5_22334695($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_truncate')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.truncate.php';
?><?php if ($_smarty_tpl->tpl_vars['options']->value['js']['ace']){?><script src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/ace/ace.js"></script><?php }?>
<?php if ($_smarty_tpl->tpl_vars['options']->value['js']['editor']){?><script src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery-wa/wa.elrte.ace.js?v<?php echo $_smarty_tpl->tpl_vars['wa']->value->version();?>
"></script><?php }?>

<link rel="stylesheet" href="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/css/wa/design.css?<?php echo $_smarty_tpl->tpl_vars['wa']->value->version(true);?>
">

<?php if ($_smarty_tpl->tpl_vars['options']->value['container']){?>
<div class="shadowed wa-design">
<?php }?>
    <div>
        <!-- theme navigation -->
        <ul class="menu-h dropdown wa-theme-globalnav" id="wa-design-menu">
            <li class="wa-theme-globalnav-switch">
                <!-- active app settlement / theme selector -->
                <a href="#" class="inline-link" id="wa-theme-active">
                    <img src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
<?php echo $_smarty_tpl->tpl_vars['app']->value['icon'][48];?>
">
                    <b><i><span class="url"><?php if ($_smarty_tpl->tpl_vars['route']->value){?><?php $_smarty_tpl->tpl_vars['_route_label'] = new Smarty_variable(($_smarty_tpl->tpl_vars['route']->value['_domain']).('/').($_smarty_tpl->tpl_vars['route']->value['url']), null, 0);?><?php }else{ ?><?php $_smarty_tpl->tpl_vars['_route_label'] = new Smarty_variable($_smarty_tpl->tpl_vars['theme']->value['name'], null, 0);?><?php }?><?php echo smarty_modifier_truncate(str_replace('www.','',$_smarty_tpl->tpl_vars['_route_label']->value),23,'...',false,true);?>
</span></i></b>
                    <i class="icon10 darr"></i>
                    <br>
                    <span class="hint"><?php if ($_smarty_tpl->tpl_vars['route']->value){?><?php echo $_smarty_tpl->tpl_vars['theme']->value['name'];?>
<?php }?></span>
                </a>
                <ul id="wa-theme-list" class="menu-v">
                    <?php  $_smarty_tpl->tpl_vars['r'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['r']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['themes_routes']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['r']->key => $_smarty_tpl->tpl_vars['r']->value){
$_smarty_tpl->tpl_vars['r']->_loop = true;
?>
                        <?php if (is_array($_smarty_tpl->tpl_vars['r']->value)){?>
                            <?php $_smarty_tpl->tpl_vars['_t'] = new Smarty_variable($_smarty_tpl->tpl_vars['themes']->value[$_smarty_tpl->tpl_vars['r']->value['theme']], null, 0);?>
                            <?php ob_start();?><?php echo urlencode($_smarty_tpl->tpl_vars['r']->value['_domain']);?>
<?php $_tmp1=ob_get_clean();?><?php $_smarty_tpl->tpl_vars['_url'] = new Smarty_variable(((string)$_smarty_tpl->tpl_vars['design_url']->value)."theme=".((string)$_smarty_tpl->tpl_vars['r']->value['theme'])."&domain=".$_tmp1."&route=".((string)$_smarty_tpl->tpl_vars['r']->value['_id']), null, 0);?>
                            <li class="no-icon 2<?php if ($_smarty_tpl->tpl_vars['current_url']->value==$_smarty_tpl->tpl_vars['_url']->value){?> selected<?php }?>" data-routing="<?php echo $_smarty_tpl->tpl_vars['r']->value['_routing_url'];?>
">
                                <a class="bold" href="<?php echo $_smarty_tpl->tpl_vars['design_url']->value;?>
theme=<?php echo $_smarty_tpl->tpl_vars['r']->value['theme'];?>
&domain=<?php echo urlencode($_smarty_tpl->tpl_vars['r']->value['_domain']);?>
&route=<?php echo $_smarty_tpl->tpl_vars['r']->value['_id'];?>
">
                                    <span data-url="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['r']->value['_preview_url'], ENT_QUOTES, 'UTF-8', true);?>
" class="url"><?php $_smarty_tpl->tpl_vars['_r_label'] = new Smarty_variable(($_smarty_tpl->tpl_vars['r']->value['_domain']).('/').($_smarty_tpl->tpl_vars['r']->value['url']), null, 0);?><?php echo smarty_modifier_truncate(str_replace('www.','',$_smarty_tpl->tpl_vars['_r_label']->value),23,'...',false,true);?>
</span>
                                    <span class="hint"><?php echo $_smarty_tpl->tpl_vars['_t']->value['name'];?>
</span>
                                </a>
                            </li>
                            <?php if (!empty($_smarty_tpl->tpl_vars['r']->value['theme_mobile'])&&($_smarty_tpl->tpl_vars['r']->value['theme_mobile']!=$_smarty_tpl->tpl_vars['r']->value['theme'])){?>
                                <?php $_smarty_tpl->tpl_vars['_t'] = new Smarty_variable($_smarty_tpl->tpl_vars['themes']->value[$_smarty_tpl->tpl_vars['r']->value['theme_mobile']], null, 0);?>
                                <?php ob_start();?><?php echo urlencode($_smarty_tpl->tpl_vars['r']->value['_domain']);?>
<?php $_tmp2=ob_get_clean();?><?php $_smarty_tpl->tpl_vars['_url'] = new Smarty_variable(((string)$_smarty_tpl->tpl_vars['design_url']->value)."theme=".((string)$_smarty_tpl->tpl_vars['r']->value['theme_mobile'])."&domain=".$_tmp2."&route=".((string)$_smarty_tpl->tpl_vars['r']->value['_id']), null, 0);?>
                                <li class="no-icon 3<?php if ($_smarty_tpl->tpl_vars['current_url']->value==$_smarty_tpl->tpl_vars['_url']->value){?> selected<?php }?>" data-routing="<?php echo $_smarty_tpl->tpl_vars['r']->value['_routing_url'];?>
">
                                    <a class="bold" href="<?php echo $_smarty_tpl->tpl_vars['design_url']->value;?>
theme=<?php echo $_smarty_tpl->tpl_vars['r']->value['theme_mobile'];?>
&domain=<?php echo urlencode($_smarty_tpl->tpl_vars['r']->value['_domain']);?>
&route=<?php echo $_smarty_tpl->tpl_vars['r']->value['_id'];?>
">
                                        <i class="icon16 mobile float-right"></i>
                                        <span class="url"><?php $_smarty_tpl->tpl_vars['_r_label'] = new Smarty_variable(($_smarty_tpl->tpl_vars['r']->value['_domain']).('/').($_smarty_tpl->tpl_vars['r']->value['url']), null, 0);?><?php echo smarty_modifier_truncate(str_replace('www.','',$_smarty_tpl->tpl_vars['_r_label']->value),23,'...',false,true);?>
</span>
                                        <span class="hint"><?php echo $_smarty_tpl->tpl_vars['_t']->value['name'];?>
</span>
                                    </a>
                                </li>
                            <?php }?>
                        <?php }else{ ?>
                            <?php $_smarty_tpl->tpl_vars['_t'] = new Smarty_variable($_smarty_tpl->tpl_vars['themes']->value[$_smarty_tpl->tpl_vars['r']->value], null, 0);?>
                            <?php $_smarty_tpl->tpl_vars['_url'] = new Smarty_variable(((string)$_smarty_tpl->tpl_vars['design_url']->value)."theme=".((string)$_smarty_tpl->tpl_vars['_t']->value['id']), null, 0);?>
                            <li class="no-icon 1<?php if ($_smarty_tpl->tpl_vars['current_url']->value==$_smarty_tpl->tpl_vars['_url']->value){?> selected<?php }?>">
                                <a href="<?php echo $_smarty_tpl->tpl_vars['design_url']->value;?>
theme=<?php echo $_smarty_tpl->tpl_vars['_t']->value['id'];?>
">
                                    <span class="url"><?php echo $_smarty_tpl->tpl_vars['_t']->value['name'];?>
</span>
                                </a>
                            </li>
                        <?php }?>
                    <?php } ?>
                </ul>
            </li>

            <?php if ($_smarty_tpl->tpl_vars['theme']->value['preview_url']){?>
                <li class="float-right">
                    <a data-url="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['theme']->value['preview_url'], ENT_QUOTES, 'UTF-8', true);?>
" href="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['theme']->value['preview_url'], ENT_QUOTES, 'UTF-8', true);?>
" target="_blank" class="wa-theme-preview" title="Сайт"><i class="icon48 preview"></i><span data-used="Сайт" data-preview="Предпросмотр"><?php if ($_smarty_tpl->tpl_vars['theme']->value['is_used']){?>Сайт<?php }else{ ?>Предпросмотр<?php }?></span></a>
                </li>
            <?php }?>    
            
            <li data-action="theme" class="info selected"><a class="wa-themes-link" href="<?php echo $_smarty_tpl->tpl_vars['design_url']->value;?>
theme=<?php echo $_smarty_tpl->tpl_vars['theme']->value['id'];?>
&action=theme"><i class="icon48 appearance"></i>Внешний вид</a></li>
            <?php if (!empty($_smarty_tpl->tpl_vars['options']->value['pages'])){?>
            <li><a class="wa-themes-link" href="<?php echo $_smarty_tpl->tpl_vars['design_url']->value;?>
pages/"><i class="icon48 pages"></i>Страницы</a></li>
            <?php }?>
            <li data-action="edit" class="templates"><a href="<?php echo $_smarty_tpl->tpl_vars['design_url']->value;?>
theme=<?php echo $_smarty_tpl->tpl_vars['theme']->value['id'];?>
&action=edit"><i class="icon48 templates"></i>Шаблоны</a></li>
            <li class="themes"><a class="wa-themes-link" href="<?php echo $_smarty_tpl->tpl_vars['themes_url']->value;?>
"><i class="icon48 store"></i>Темы дизайна</a></li>
            <li data-action="settings" class="settings"><a href="<?php echo $_smarty_tpl->tpl_vars['design_url']->value;?>
action=settings"><i class="icon48 settings"></i>Настройки</a></li>
        </ul>
    </div>

    <div id="wa-design-container" class="blank">
        <div class="block double-padded">
            Загрузка... <i class="icon16 loading"></i>
            <div class="clear"></div>
        </div>
    </div>
    <div class="dialog" id="wa-design-preview">
        <div class="dialog-background"> </div>
        <div class="dialog-window" style="height: 150px; min-height: 150px; width: 400px; min-width: 400px">
            <form>
                <input type="hidden" id="wa-preview-key" value="" />
                <div class="dialog-content">
                    <div class="dialog-content-indent">
                        <h1>Предварительный просмотр</h1>
                        <p>Тема дизайна «<strong id="wa-current-theme"></strong>» временно активирована для вашего пользователя. Вы можете полноценно просматривать сайт в выбранной теме дизайна, и другие посетители сайта этого не увидят.</p>
                    </div>
                </div>
                <div class="dialog-buttons">
                    <div class="dialog-buttons-gradient">
                        <input type="submit" class="button" value="Завершить предварительный просмотр">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        var wa_url = "<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
";
        function waDesignConfirm() {
            if ($("#wa-design-button").length && $('#wa-design-button').hasClass('yellow')) {
                if (!confirm("Несохраненные изменения будут потеряны. Продолжить?")) {
                    return false;
                }
            }
            return true;
        }
        $(window).on('beforeunload', function () {
            if ($("#wa-design-button").length && $('#wa-design-button').hasClass('yellow')) {
                return "Несохраненные изменения будут потеряны. Продолжить?";
            }
        });

        $('.wa-theme-globalnav').on('mouseover', function () {
            $('#wa-theme-list').css('display', '');
        });

        var waDesignHash = function (url) {
            if (!url || !url.length) {
                return '';
            }
            url = url.replace(/\/$/, '').replace(/^#/, '')
            var urls = url.split('/');
            var n = urls.length;
            url = urls[n - 1];
            var i = n - 2;
            while (i >= 0 && urls[i].indexOf('=') != -1) {
                url = urls[i] + '/' + url;
                i--;
            }
            return url;
        }

        var waDesignParams = function (url) {
            url = waDesignHash(url);
            var params_ar = url.split('&');
            var params = {};
            for (var i = 0; i < params_ar.length; i++) {
                var tmp = params_ar[i].split('=');
                params[tmp[0]] = tmp[1];
            }
            return params;
        }

        $("#wa-theme-list li a").click(function (e, set_active_only) {
            $('#wa-theme-list').hide();
            $('#wa-theme-list li.selected').removeClass('selected');
            $(this).parent().addClass('selected');
            var href = $(this).attr('href');
            var params = waDesignParams(href);
            if (params['theme']) {
                $("#wa-theme-active span.url").html($(this).find('span.url').html());
                if ($(this).find('span.hint').length) {
                    $("#wa-theme-active span.hint").html($(this).find('span.hint').html());
                } else {
                    $("#wa-theme-active span.hint").empty();
                }
                if ($(this).find('.url').data('url')) {
                    $('.wa-theme-preview').attr('href', $(this).find('.url').data('url'));
                    $('.wa-theme-preview').find('span').html($('.wa-theme-preview').find('span').data('used'));
                } else {
                    $('.wa-theme-preview').attr('href', $('.wa-theme-preview').data('url'));
                    $('.wa-theme-preview').find('span').html($('.wa-theme-preview').find('span').data('preview'));
                }
                $('#wa-design-menu > li > a').each(function () {
                    if ($(this).parent().data('action')) {
                        $(this).attr('href', href + '&action=' + $(this).parent().data('action'));
                    } else if ($(this).attr('href').indexOf('theme=') !== -1) {
                        $(this).attr('href', $(this).attr('href').replace(/theme=[^&]+/, 'theme=' + params['theme']));
                    }
                });
                if (set_active_only) {
                    return false;
                }
                var hash = location.hash;
                if (hash.indexOf('theme=') !== -1) {
                    if (hash.indexOf('action=settings') !== -1) {
                        if (!params['route']) {
                            location.href = $(this).attr('href');
                        } else {
                            hash = hash.replace(/route=[^&]/, 'route=' + params['route']);
                            $.wa.setHash(hash.replace(/theme=[^&]+/, 'theme=' + params['theme']));
                        }
                    } else {
                        if (!params['route']) {
                            hash = hash.replace(/&route=[^&]+/, '');
                        } else if (hash.indexOf('route=') != -1) {
                            hash = hash.replace(/route=[^&]+/, 'route=' + params['route']);
                        } else {
                            hash = hash + '&route=' + params['route'];
                        }
                        $.wa.setHash(hash.replace(/theme=[^&]+/, 'theme=' + params['theme']));
                    }
                } else {
                    location.href = $(this).attr('href');
                }
                <?php if (!$_smarty_tpl->tpl_vars['options']->value['is_ajax']){?>waDesignLoad();<?php }?>
                return false;
            }
        });

        $(document).on('click', ".wa-theme-preview", function () {
            var url = $(this).attr('href');
            var tmp_params = waDesignParams($('#wa-theme-list li.selected a').attr('href'));
            if (!tmp_params['route']) {
                $('#wa-current-theme').html($('#wa-theme-active .url').html());
                $("#wa-design-preview").waDialog({
                    esc: false,
                    onSubmit: function () {
                        $("#wa-design-preview").hide();
                        $("body").append($('<iframe style="display:none" src="' + url.replace(/(set_force_theme=).*$/, '$1') + '" />').load(function () {
                            $(this).remove();
                        }));
                        return false;
                    }
                });
            }
        });

        $("#wa-design-sidebar h2 a").click(function () {
            if (!waDesignConfirm()) return false;
        });

        <?php if (!$_smarty_tpl->tpl_vars['options']->value['is_ajax']){?>
            $('#wa-design-menu > li > a').not('.wa-theme-preview').click(function () {
                waDesignLoad($(this).attr('href').replace(/.*?#\//, '').replace(/\/$/, ''));
            });
        <?php }?>

        var site_routing_full = function () {};
        var wa_design_not_load = false;
        function waDesignLoad(hash) {
            var hash_params = waDesignParams(hash || location.hash);
            if (hash_params['theme']) {
                var current_theme_url = 'theme=' + hash_params['theme'];
                if (hash_params['domain']) {
                    current_theme_url += '&domain=' + hash_params['domain'];
                }
                if (hash_params['route']) {
                    current_theme_url += '&route=' + hash_params['route'];
                }
                if ($('#wa-theme-list a[href="<?php echo $_smarty_tpl->tpl_vars['design_url']->value;?>
' + current_theme_url + '"]').length) {
                    $('#wa-theme-list a[href="<?php echo $_smarty_tpl->tpl_vars['design_url']->value;?>
' + current_theme_url + '"]').trigger('click', [true]);
                } else {
                    $('#wa-theme-list a[href^="<?php echo $_smarty_tpl->tpl_vars['design_url']->value;?>
theme=' + hash_params['theme'] + '"]:first').trigger('click', [true]);
                }
            }

            var a = $('#wa-design-menu li.selected a');
            if (a.length) {
                var a_params = waDesignParams(a.attr('href'));
                if (a_params['route']) {
                    $('#wa-design-menu li.settings a').attr('href', a.attr('href') + '&action=settings');
                    $('#wa-design-menu li.settings').show();
                } else {
                    $('#wa-design-menu li.settings').hide();
                }
            }

            if (wa_design_not_load) {
                wa_design_not_load = false;
                return;
            }
            if (hash  === undefined) {
                hash = waDesignHash(location.hash);
            }
            if (hash.length) {
                if (hash == 'themes') {
                    $('#wa-design-menu > .selected').removeClass('selected');
                    $('#wa-design-menu .themes').addClass('selected');
                    $("#wa-design-container").load("?module=design&action=themes", function () {
                        if ($(".s-scrollable-part").length) {
                            $(".s-scrollable-part").scrollTop(0);
                        } else {
                            $(document).scrollTop(0);
                        }
                    });
                } else if (hash.substr(0, 5) == 'pages') {

                } else if (hash.indexOf('action=settings') != -1) {
                    $('#wa-design-menu > .selected').removeClass('selected');
                    $('#wa-design-menu .settings ').addClass('selected');
                    if ($('#wa-theme-list li.selected').length) {
                        var li = $('#wa-theme-list li.selected');
                    } else {
                        var li = $('#wa-theme-list li:first');
                    }
                    if (li.data('routing')) {
                        $("#wa-design-container").load(li.data('routing') + '&reload_on_change=1', function () {
                            $("#wa-design-container .back").remove();
                            $(".s-route-core").hide();
                            $('.s-route-details .fields').
                                    prepend($('<div class="field-group"></div>').
                                            append($('<div class="field"><div class="name">URL</div></div>').
                                                    append($('<div class="value"></div>').html($('#s-route-where').html()))
                                    )
                            );
                            $("#wa-design-container .s-route-delete").closest('div').remove();
                        });
                    } else {
                        $.wa.setHash(location.hash.replace(/&?action=settings/, ''));
                    }
                } else {
                    if (hash.indexOf('action=') == -1) {
                        if (hash.indexOf('file=') == -1) {
                            hash = 'action=theme&' + hash;
                        } else {
                            hash = 'action=edit&' + hash;
                        }
                    }
                    if (hash.indexOf('theme=') != -1) {
                        var params = hash.split('&');
                        for (var j = 0; j < params.length; j++) {
                            var tmp = params[j].split('=');
                            //console.log(tmp);
                            if (tmp[0] == 'theme') {
                                $('#wa-design-sidebar a[href="' + location.hash + '"]').parent().addClass('selected');
                            }
                        }
                    }
                    if (hash.indexOf('action=edit') !== -1) {
                        $('#wa-design-menu > .selected').removeClass('selected');
                        $('#wa-design-menu .templates').addClass('selected');
                    } else if (hash.indexOf('action=theme') !== -1) {
                        $('#wa-design-menu > .selected').removeClass('selected');
                        $('#wa-design-menu .info').addClass('selected');
                    }
                    $("#wa-design-container").load("?module=design&" + hash, function (responseText, textStatus, XMLHttpRequest) {
                        if ($(".s-scrollable-part").length) {
                            $(".s-scrollable-part").scrollTop(0);
                        } else {
                            $(document).scrollTop(0);
                        }
                        try{
                            if (response = $.parseJSON(responseText)) {
                                if(response.data && response.data.redirect) {
                                    var href = location.href.replace(/#.*$/,'') + response.data.redirect;
                                    location.replace(href);
                                }
                            }
                        }catch(e) {}
                    });
                }
            } else {
                $("#wa-theme-list li a:first").click();
            }
        }
        $(document).ready(function () {
            var a = $('#wa-design-sidebar .wa-theme-block a[href="' + location.hash + '"]:first');
            if (a.length) {
                a.parent().addClass('selected');
                var t = a.closest('.wa-theme-block').show().attr('id').replace(/wa-theme-block-/, '');
                $("#wa-theme-active").text($('#wa-theme-list-' + t).find('a span.wa-theme-name').text());
            }
            <?php if (!$_smarty_tpl->tpl_vars['options']->value['is_ajax']){?>
                waDesignLoad();
            <?php }?>
        });
    </script>
<?php if ($_smarty_tpl->tpl_vars['options']->value['container']){?>
</div>
<?php }?>

<?php }} ?>