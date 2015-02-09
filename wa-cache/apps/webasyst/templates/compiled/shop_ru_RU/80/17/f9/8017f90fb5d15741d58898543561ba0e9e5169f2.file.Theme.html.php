<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:37:08
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-system/design/templates/Theme.html" */ ?>
<?php /*%%SmartyHeaderCode:181659573554d8a9f442b818-18250292%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '8017f90fb5d15741d58898543561ba0e9e5169f2' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/design/templates/Theme.html',
      1 => 1416918117,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '181659573554d8a9f442b818-18250292',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'theme' => 0,
    'theme_routes' => 0,
    'wa' => 0,
    'cover' => 0,
    'preview_url' => 0,
    'settings' => 0,
    'wa_url' => 0,
    's' => 0,
    's_var' => 0,
    'v' => 0,
    'o' => 0,
    'k' => 0,
    '_url' => 0,
    '_r' => 0,
    't' => 0,
    'app' => 0,
    'routes' => 0,
    'r' => 0,
    'domains' => 0,
    'd' => 0,
    'path' => 0,
    'parent_themes' => 0,
    'info' => 0,
    'app_id' => 0,
    'parent_theme_id' => 0,
    'theme_name' => 0,
    'design_url' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8a9f474da49_98598681',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8a9f474da49_98598681')) {function content_54d8a9f474da49_98598681($_smarty_tpl) {?><?php if (!is_callable('smarty_function_html_options')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/function.html_options.php';
if (!is_callable('smarty_modifier_wa_datetime')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/modifier.wa_datetime.php';
if (!is_callable('smarty_modifier_truncate')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.truncate.php';
?><div class="block double-padded blank wa-theme" id="wa-theme-<?php echo $_smarty_tpl->tpl_vars['theme']->value['id'];?>
">

    <h1 class="wa-theme-name"><?php echo sprintf('Тема дизайна «%s»',htmlspecialchars($_smarty_tpl->tpl_vars['theme']->value['name'], ENT_QUOTES, 'UTF-8', true));?>
</h1>

    <!-- THEME ACTIONS -->
    <div class="sidebar right250px">
        <div class="block double-padded wa-theme-actions">
            <ul class="menu-v with-icons">
                <li class="bottom-padded"><a href="?module=design&amp;action=themeDownload&amp;theme=<?php echo $_smarty_tpl->tpl_vars['theme']->value['id'];?>
" class="bold"><i class="icon16 download"></i>Скачать архив с темой дизайна <span class="hint nowrap">.tar.gz</span></a></li>
                <li><a class="theme-copy" href="#"><i class="icon16 split"></i>Создать клон темы</a></li>
                <li><a class="theme-rename" href="#"><i class="icon16 edit"></i>Переименовать тему</a></li>
                <li><i class="icon16 link"></i>Родительская тема дизайна: <strong><?php if ($_smarty_tpl->tpl_vars['theme']->value['parent_theme_id']){?><?php echo $_smarty_tpl->tpl_vars['theme']->value['parent_theme_id'];?>
<?php }else{ ?>не задана<?php }?></strong>  <a class="theme-parent inline" href="#">Сменить</a></li>
                <li class="top-padded">
                    <a class="theme-reset<?php if ($_smarty_tpl->tpl_vars['theme']->value['type']!=waTheme::OVERRIDDEN){?> disabled<?php }?>" href="#" title="Все изменения, которые вы вносили в тему дизайна, будут потеряны. Сбросить все изменения?"<?php if ($_smarty_tpl->tpl_vars['theme']->value['type']!=waTheme::OVERRIDDEN){?> onClick="window.alert('Вы еще не вносили изменения в эту тему дизайна, поэтому сбрасывать пока нечего.');"<?php }?>><i class="icon16 broom"></i>Сбросить все изменения</a>
                    <?php if ($_smarty_tpl->tpl_vars['theme']->value['type']!=waTheme::OVERRIDDEN){?>
                        <span class="hint">Вы используете исходную версию темы дизайна. Изменения в шаблоны темы дизайна еще не вносились, поэтому пока еще нет изменений, которые можно было бы сбросить.</span>
                    <?php }else{ ?>
                        <span class="hint">Очистить все изменения, которые вы вносили в тему дизайна с помощью редактора дизайна, и вернуть тему дизайна к исходному состоянию</span>
                    <?php }?>
                </li>
                <li class="top-padded">
                    <a class="theme-delete<?php if ($_smarty_tpl->tpl_vars['theme']->value['system']){?> disabled<?php }?>" href="#" title="Тема будет удалена без возможности восстановления. Удалить?"><i class="icon16 delete"></i>Удалить тему</a>
                    <?php if ($_smarty_tpl->tpl_vars['theme']->value['system']){?><span class="hint">Эта тема дизайна не может быть удалена</span><?php }?>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="content right250px">
    
        <?php if (!$_smarty_tpl->tpl_vars['theme_routes']->value){?>
                       
            <!-- THEME IS NOT IN USE -->
            
            <?php if ($_smarty_tpl->tpl_vars['wa']->value->get('just_installed')){?>
                <p class="bold successmsg"><i class="icon16 yes"></i><?php echo sprintf(_ws('%s design theme has been successfully installed. Start using this theme by clicking the button below.'),htmlspecialchars($_smarty_tpl->tpl_vars['theme']->value['name'], ENT_QUOTES, 'UTF-8', true));?>
</p>
            <?php }else{ ?>
                <p>
                    <?php echo sprintf(_ws('%s design theme is currently not in use on your websites.'),htmlspecialchars($_smarty_tpl->tpl_vars['theme']->value['name'], ENT_QUOTES, 'UTF-8', true));?>

                </p>
            <?php }?>            

            <?php if ($_smarty_tpl->tpl_vars['cover']->value){?>
            <p>
                <img src="<?php echo $_smarty_tpl->tpl_vars['cover']->value;?>
" />
            </p>
            <?php }?>
            
            <p style="line-height: 2.2em;">
                <a id="theme-start-using" href="#" class="button green"><?php echo sprintf('Начать использовать тему «%s»',htmlspecialchars($_smarty_tpl->tpl_vars['theme']->value['name'], ENT_QUOTES, 'UTF-8', true));?>
</a>
                <?php if (!empty($_smarty_tpl->tpl_vars['preview_url']->value)){?>
                или
                <span class="nowrap"><a class="wa-theme-preview" target="_blank" href="<?php echo $_smarty_tpl->tpl_vars['preview_url']->value;?>
">открыть предпросмотр</a> <i class="icon10 new-window" style="margin-top: 0.8em;"></i></span>
                <?php }?>
            </p>
        <?php }else{ ?>                        
            
        
            <!-- THEME SETTINGS FORM -->
        
            <?php if ($_smarty_tpl->tpl_vars['settings']->value){?>
            
                <script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/farbtastic/farbtastic.js"></script>
                <link rel="stylesheet" href="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/farbtastic/farbtastic.css" type="text/css" />
            
                <iframe style="display: none" id="theme-settings-iframe" name="theme-settings-iframe"></iframe>
                <form id="theme-settings" method="post" action="?module=design&action=themeSettings&theme=<?php echo $_smarty_tpl->tpl_vars['theme']->value['id'];?>
" enctype="multipart/form-data" target="theme-settings-iframe">
                    <?php echo $_smarty_tpl->tpl_vars['wa']->value->csrf();?>

                    <br>
                    <div class="fields form">
                        <?php  $_smarty_tpl->tpl_vars['s'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['s']->_loop = false;
 $_smarty_tpl->tpl_vars['s_var'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['settings']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
 $_smarty_tpl->tpl_vars['s']->index=-1;
foreach ($_from as $_smarty_tpl->tpl_vars['s']->key => $_smarty_tpl->tpl_vars['s']->value){
$_smarty_tpl->tpl_vars['s']->_loop = true;
 $_smarty_tpl->tpl_vars['s_var']->value = $_smarty_tpl->tpl_vars['s']->key;
 $_smarty_tpl->tpl_vars['s']->index++;
?>
                        <div class="field">
                            <div class="name"><?php echo $_smarty_tpl->tpl_vars['s']->value['name'];?>
</div>
                            <div class="value">
                                <?php if ($_smarty_tpl->tpl_vars['s']->value['control_type']=='select'){?>
                                    <select name="<?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>parent_<?php }?>settings[<?php echo $_smarty_tpl->tpl_vars['s_var']->value;?>
]">
                                        <?php echo smarty_function_html_options(array('options'=>$_smarty_tpl->tpl_vars['s']->value['options'],'selected'=>ifset($_smarty_tpl->tpl_vars['s']->value['value'])),$_smarty_tpl);?>

                                    </select>
                                <?php }elseif($_smarty_tpl->tpl_vars['s']->value['control_type']=='radio'){?>
                                <?php  $_smarty_tpl->tpl_vars['o'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['o']->_loop = false;
 $_smarty_tpl->tpl_vars['v'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['s']->value['options']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['o']->key => $_smarty_tpl->tpl_vars['o']->value){
$_smarty_tpl->tpl_vars['o']->_loop = true;
 $_smarty_tpl->tpl_vars['v']->value = $_smarty_tpl->tpl_vars['o']->key;
?>
                                    <label>
                                        <input <?php if (ifset($_smarty_tpl->tpl_vars['s']->value['value'])==$_smarty_tpl->tpl_vars['v']->value){?>checked<?php }?> type="radio" value="<?php echo $_smarty_tpl->tpl_vars['v']->value;?>
" name="<?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>parent_<?php }?>settings[<?php echo $_smarty_tpl->tpl_vars['s_var']->value;?>
]" > <?php echo $_smarty_tpl->tpl_vars['o']->value['name'];?>

                                        <?php if (!empty($_smarty_tpl->tpl_vars['o']->value['description'])){?>
                                        <p class="hint"><?php echo $_smarty_tpl->tpl_vars['o']->value['description'];?>
</p>
                                        <?php }?>
                                    </label>
                                <?php } ?>
                                <?php }elseif($_smarty_tpl->tpl_vars['s']->value['control_type']=='color'){?>
                                    <input class="color" type="text" name="<?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>parent_<?php }?>settings[<?php echo $_smarty_tpl->tpl_vars['s_var']->value;?>
]" value="<?php echo $_smarty_tpl->tpl_vars['s']->value['value'];?>
">
                                <?php }elseif($_smarty_tpl->tpl_vars['s']->value['control_type']=='checkbox'){?>
                                    <input type="hidden" name="<?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>parent_<?php }?>settings[<?php echo $_smarty_tpl->tpl_vars['s_var']->value;?>
]" value="">
                                    <input type="checkbox" name="<?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>parent_<?php }?>settings[<?php echo $_smarty_tpl->tpl_vars['s_var']->value;?>
]" <?php if ($_smarty_tpl->tpl_vars['s']->value['value']){?>checked<?php }?> value="1">
                                <?php }elseif($_smarty_tpl->tpl_vars['s']->value['control_type']=='image_select'){?>
                                    <ul class="wa-theme-image-select">
                                        <?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>
                                            <?php $_smarty_tpl->tpl_vars['_url'] = new Smarty_variable($_smarty_tpl->tpl_vars['theme']->value['parent_theme']->getUrl(), null, 0);?>
                                        <?php }else{ ?>
                                            <?php $_smarty_tpl->tpl_vars['_url'] = new Smarty_variable($_smarty_tpl->tpl_vars['theme']->value->getUrl(), null, 0);?>
                                        <?php }?>
                                        <?php  $_smarty_tpl->tpl_vars['v'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['v']->_loop = false;
 $_smarty_tpl->tpl_vars['k'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['s']->value['options']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['v']->key => $_smarty_tpl->tpl_vars['v']->value){
$_smarty_tpl->tpl_vars['v']->_loop = true;
 $_smarty_tpl->tpl_vars['k']->value = $_smarty_tpl->tpl_vars['v']->key;
?>
                                            <li<?php if ($_smarty_tpl->tpl_vars['s']->value['value']==$_smarty_tpl->tpl_vars['k']->value){?> class="selected"<?php }?> data-value="<?php echo $_smarty_tpl->tpl_vars['k']->value;?>
"><a href="#"><img src="<?php echo $_smarty_tpl->tpl_vars['_url']->value;?>
<?php echo $_smarty_tpl->tpl_vars['k']->value;?>
"></a></li>
                                        <?php } ?>
                                    </ul>
                                    <input type="hidden" name="<?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>parent_<?php }?>settings[<?php echo $_smarty_tpl->tpl_vars['s_var']->value;?>
]" value="<?php echo $_smarty_tpl->tpl_vars['s']->value['value'];?>
">
                                <?php }elseif($_smarty_tpl->tpl_vars['s']->value['control_type']=='image'){?>
                                    <input type="hidden" name="<?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>parent_<?php }?>settings[<?php echo $_smarty_tpl->tpl_vars['s_var']->value;?>
]" value="<?php echo ifset($_smarty_tpl->tpl_vars['s']->value['value']);?>
">
                                    <input type="file" name="<?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>parent_<?php }?>image[<?php echo $_smarty_tpl->tpl_vars['s_var']->value;?>
]">
                                    <?php if (!empty($_smarty_tpl->tpl_vars['s']->value['value'])){?>
                                        <?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>
                                        <?php $_smarty_tpl->tpl_vars['_url'] = new Smarty_variable($_smarty_tpl->tpl_vars['theme']->value['parent_theme']->getUrl(), null, 0);?>
                                        <?php }else{ ?>
                                        <?php $_smarty_tpl->tpl_vars['_url'] = new Smarty_variable($_smarty_tpl->tpl_vars['theme']->value->getUrl(), null, 0);?>
                                        <?php }?>
                                        <div class="image"><br>
                                            <img src="<?php echo $_smarty_tpl->tpl_vars['_url']->value;?>
<?php echo $_smarty_tpl->tpl_vars['s']->value['value'];?>
?v<?php echo time();?>
">
                                            <br>
                                            <a class="small delete-image" href="#">Удалить</a>
                                        </div>
                                    <?php }?>
                                <?php }else{ ?>
                                    <?php if (!$_smarty_tpl->tpl_vars['s']->value['value']||strlen($_smarty_tpl->tpl_vars['s']->value['value'])<=50){?>
                                    <input class="flexible" id="flex-settings-<?php echo $_smarty_tpl->tpl_vars['s']->index;?>
" type="text" name="<?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>parent_<?php }?>settings[<?php echo $_smarty_tpl->tpl_vars['s_var']->value;?>
]" value="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['s']->value['value'], ENT_QUOTES, 'UTF-8', true);?>
">
                                    <?php }else{ ?>
                                    <textarea class="flexible" id="flex-settings-<?php echo $_smarty_tpl->tpl_vars['s']->index;?>
" name="<?php if (!empty($_smarty_tpl->tpl_vars['s']->value['parent'])){?>parent_<?php }?>settings[<?php echo $_smarty_tpl->tpl_vars['s_var']->value;?>
]"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['s']->value['value'], ENT_QUOTES, 'UTF-8', true);?>
</textarea>
                                    <?php }?>
                                <?php }?>
                            </div>                
                        </div>
                        <?php } ?>
                        
                        <div class="wa-design-save-panel block bordered-top">
                            <input type="submit" class="button green" value="Сохранить">
                            <span id="theme-settings-message" style="display: none"><i class="icon16 yes" style="margin-top: 0.5em;"></i> Сохранено</span>
                            <span id="theme-settings-error" class="errormsg" style="display: inline"></span></div>
                    </div>
                </form>
            <?php }else{ ?>
                <p>
                    <br>
                    <em><?php echo sprintf('Тема дизайна «%s» не предоставляет опций для настройки внешнего вида. Используйте редактор шаблонов дизайна для настройки оформления.',htmlspecialchars($_smarty_tpl->tpl_vars['theme']->value['name'], ENT_QUOTES, 'UTF-8', true));?>
</em>
                </p>
            <?php }?>    

        <?php }?>
        
        <!-- THEME USAGE -->
        <div class="fields" style="width: 100%">
        
            <h4 class="heading">Использование темы</h4>
        
            <div class="field">
                <div class="name">
                    Путь к папке темы
                </div>
                <div class="value">
                    <?php if ($_smarty_tpl->tpl_vars['theme']->value['type']==waTheme::ORIGINAL){?><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['theme']->value['original'], ENT_QUOTES, 'UTF-8', true);?>
<?php }else{ ?><strong><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['theme']->value['custom'], ENT_QUOTES, 'UTF-8', true);?>
</strong><?php }?>
                </div>
                <?php if ($_smarty_tpl->tpl_vars['theme']->value['type']!=waTheme::ORIGINAL){?>
                    <div class="value hint">
                        Последнее изменение: <strong><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['theme']->value['mtime'],"humandatetime");?>
</strong>
                    </div>
                <?php }?>                
            </div>
            
            <div class="field">
                <div class="name">
                    Использование темы
                </div>
                <div class="value no-shift">
                    
                    <p class="bold">
                        <?php $_smarty_tpl->tpl_vars['_theme_usages'] = new Smarty_variable(array(), null, 0);?>
                        <?php  $_smarty_tpl->tpl_vars['_r'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['_r']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['theme_routes']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['_r']->key => $_smarty_tpl->tpl_vars['_r']->value){
$_smarty_tpl->tpl_vars['_r']->_loop = true;
?>
                            <?php if ($_smarty_tpl->tpl_vars['_r']->value['_domain']!=$_smarty_tpl->tpl_vars['wa']->value->get('domain')&&$_smarty_tpl->tpl_vars['_r']->value['_id']!=$_smarty_tpl->tpl_vars['wa']->value->get('route')){?>
                                <?php $_smarty_tpl->createLocalArrayVariable('_theme_usages', null, 0);
$_smarty_tpl->tpl_vars['_theme_usages']->value[] = htmlspecialchars(((string)$_smarty_tpl->tpl_vars['_r']->value['_domain'])."/".((string)$_smarty_tpl->tpl_vars['_r']->value['url']));?>
                            <?php }?>
                            <a target="_blank" href="<?php echo $_smarty_tpl->tpl_vars['_r']->value['_url'];?>
" class="no-underline"><?php echo $_smarty_tpl->tpl_vars['_r']->value['_domain'];?>
/<?php echo $_smarty_tpl->tpl_vars['_r']->value['url'];?>
 <i class="icon10 new-window"></i></a><br>
                        <?php } ?>
                    </p>
                    <?php if (count($_smarty_tpl->tpl_vars['theme_routes']->value)>1){?>
                        <p class="gray">
                            <i class="icon10 exclamation"></i> <?php echo sprintf('Изменение настроек темы дизайна «%s» затронет все перечисленные поселения приложения. Если вы хотите сохранить индивидуальный дизайн для каждого поселения, используйте отдельные копии (дубликаты) темы дизайна.',htmlspecialchars($_smarty_tpl->tpl_vars['theme']->value['name'], ENT_QUOTES, 'UTF-8', true));?>

                        </p>
                    <?php }?>
                    
                </div>
            </div>

        </div>
        
        <?php if ($_smarty_tpl->tpl_vars['theme']->value['about']){?>
            <!-- ABOUT -->
            <h4 class="heading">Информация о теме</h4>
            <?php echo $_smarty_tpl->tpl_vars['theme']->value['about'];?>

        <?php }?>
        
        <?php if (!empty($_smarty_tpl->tpl_vars['theme']->value['thumbs'])){?>
            <!-- THUMB SIZES -->
            <h4 class="heading">Эскизы</h4>
            <p>Эта тема дизайна использует эскизы изображений перечисленных ниже размеров. Если в настройках вашего приложения возможность автоматического создания эскизов «на лету» отключена, убедитесь, что все указанные размеры добавлены в список разрешенных. Если создание эскизов «на лету» разрешено, изображения указанных ниже размеров будут создаваться автоматически.</p>
            <ul class="bold" style="line-height: 1.5em;">
                <?php  $_smarty_tpl->tpl_vars['t'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['t']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['theme']->value['thumbs']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['t']->key => $_smarty_tpl->tpl_vars['t']->value){
$_smarty_tpl->tpl_vars['t']->_loop = true;
?>
                    <li><?php echo $_smarty_tpl->tpl_vars['t']->value;?>
</li>
                <?php } ?>
            </ul>
        <?php }?>
        
        <div class="clear-both"></div>
        
    </div>
    
</div>

<div class="dialog width500px height150px" id="wa-theme-reset-dialog">
    <div class="dialog-background"> </div>
    <div class="dialog-window">
        <form>
            <div class="dialog-content">
                <div class="dialog-content-indent">
                    <h1>Восстановить из исходной версии</h1>
                    <p><i class="icon10 exclamation"></i> Все изменения, которые вы вносили в тему дизайна, будут потеряны. Сбросить все изменения?</p>
                    <?php if ($_smarty_tpl->tpl_vars['theme']->value['parent_theme']&&$_smarty_tpl->tpl_vars['theme']->value['parent_theme']['type']==waTheme::OVERRIDDEN){?>
                    <label>
                        <input type="checkbox" name="parent" value="1"> <?php echo sprintf("Также сбросить все изменения, которые вносились в родительскую тему дизайна <strong>%s</strong>",$_smarty_tpl->tpl_vars['theme']->value['parent_theme_id']);?>

                    </label>
                    <?php }?>
                </div>
                <div class="clear"></div>
            </div>
            <div class="dialog-buttons">
                <div class="dialog-buttons-gradient">
                    <input type="hidden" name="theme" value="<?php echo $_smarty_tpl->tpl_vars['theme']->value['id'];?>
">
                    <input type="submit" class="button red" value="Восстановить из исходной версии">
                    или <a href="#/design/themes/" class="cancel">отмена</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="dialog width600px height300px" id="wa-theme-start-using-dialog">
    <div class="dialog-background"> </div>
    <div class="dialog-window">
        <form>
            <div class="dialog-content">
                <div class="dialog-content-indent">
                    <h1><?php echo $_smarty_tpl->tpl_vars['theme']->value['name'];?>
</h1>
                    <p><?php echo sprintf("Подключите тему дизайна к одному из существующих поселений приложения «%s» или создайте новое правило маршрутизации:",$_smarty_tpl->tpl_vars['app']->value['name']);?>
</p>
                    
                    <div class="fields width100px form">
                        <div class="field">
                            <div class="name">Выберите правило</div>
                            <?php  $_smarty_tpl->tpl_vars['r'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['r']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['routes']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
 $_smarty_tpl->tpl_vars['r']->index=-1;
foreach ($_from as $_smarty_tpl->tpl_vars['r']->key => $_smarty_tpl->tpl_vars['r']->value){
$_smarty_tpl->tpl_vars['r']->_loop = true;
 $_smarty_tpl->tpl_vars['r']->index++;
 $_smarty_tpl->tpl_vars['r']->first = $_smarty_tpl->tpl_vars['r']->index === 0;
?>
                                <div class="value no-shift">
                                    <label>
                                        <input name="route" value="<?php echo $_smarty_tpl->tpl_vars['r']->value['_domain'];?>
|<?php echo $_smarty_tpl->tpl_vars['r']->value['_id'];?>
" type="radio" <?php if ($_smarty_tpl->tpl_vars['r']->first){?>checked<?php }?>>
                                        <?php echo $_smarty_tpl->tpl_vars['r']->value['_domain'];?>
/<?php echo $_smarty_tpl->tpl_vars['r']->value['url'];?>

                                        <span class="hint"><?php if (isset($_smarty_tpl->tpl_vars['r']->value['theme'])){?><?php echo $_smarty_tpl->tpl_vars['r']->value['theme'];?>
<?php }else{ ?>default<?php }?></span>
                                    </label>
                                </div>
                            <?php }
if (!$_smarty_tpl->tpl_vars['r']->_loop) {
?>
                                <div class="value gray">
                                    <?php echo sprintf('На этом сайте нет поселений приложения «%s».',$_smarty_tpl->tpl_vars['app']->value['name']);?>

                                </div>
                            <?php } ?>
                        </div>
                        <div class="field">
                            <div class="name">Новое правило</div>
                            <div class="value">
                                <input name="route" id="create-new-route-choice" value="new" type="radio"<?php if (!count($_smarty_tpl->tpl_vars['routes']->value)){?> checked<?php }?>>
                            
                                <?php if (count($_smarty_tpl->tpl_vars['domains']->value)==1){?>
                                    <input name="domain" type="hidden" value="<?php echo current($_smarty_tpl->tpl_vars['domains']->value);?>
">
                                <?php echo current($_smarty_tpl->tpl_vars['domains']->value);?>
/<?php }else{ ?>
                                    <select name="domain" class="create-new-route-control">
                                        <?php  $_smarty_tpl->tpl_vars['d'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['d']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['domains']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['d']->key => $_smarty_tpl->tpl_vars['d']->value){
$_smarty_tpl->tpl_vars['d']->_loop = true;
?>
                                        <option value="<?php echo $_smarty_tpl->tpl_vars['d']->value;?>
"><?php echo smarty_modifier_truncate(str_replace('www.','',$_smarty_tpl->tpl_vars['d']->value),23,'...',false,true);?>
</option>
                                        <?php } ?>
                                    </select>/
                                <?php }?>
                                <input type="text" name="url" value="" placeholder="*" class="short create-new-route-control">
                            </div>
                        </div>
                    </div>
                    
                    
                </div>
                <div class="clear"></div>
            </div>
            <div class="dialog-buttons">
                <div class="dialog-buttons-gradient">
                    <input type="hidden" name="theme" value="<?php echo $_smarty_tpl->tpl_vars['theme']->value['id'];?>
">
                    <input type="submit" class="button green" value="<?php echo sprintf("Начать использовать тему «%s»",$_smarty_tpl->tpl_vars['theme']->value['name']);?>
">
                    или <a href="#" class="cancel">отмена</a>
                </div>
            </div>
        </form>
    </div>
</div>


<div class="dialog height250px" id="wa-theme-name-dialog">
    <div class="dialog-background"> </div>
    <div class="dialog-window">
        <form>
            <div class="dialog-content">
                <div class="dialog-content-indent">
                    <h1>Переименовать тему</h1>
                    <span class="wa-theme-dialog-error" style="color: red;font-weight: bold;"></span>
                    <div class="fields form">
                        <div class="field">
                            <div class="name">
                                Название темы
                            </div>
                            <div class="value">
                                <input type="text" id="wa-theme-rename-name" name="name" value="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['theme']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
" >
                            </div>
                        </div>
                        <div class="field">
                            <div class="name">
                                ID темы
                            </div>
                            <div class="value">
                                <?php echo $_smarty_tpl->tpl_vars['path']->value;?>
/<input type="text" id="wa-theme-rename-id" name="id" value="<?php echo $_smarty_tpl->tpl_vars['theme']->value['id'];?>
"  class="bold">
                            </div>
                            <div class="value">
                                <p class="small"><i class="icon10 exclamation"></i> ВАЖНО: изменяйте ID темы, только если вы полностью уверены в своих действиях. Если эта тема используется в ваших сайтах, вам придется вручную обновить настройки этих сайтов для использования этой или другой темы после изменения ID темы. В противном случае ваши сайты могут оказаться нерабочими из-за ошибки «Невозможно загрузить файл шаблона».</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
            <div class="dialog-buttons">
                <div class="dialog-buttons-gradient">
                    <input type="submit" class="button green" value="Сохранить">
                    или <a href="#/design/themes/" class="cancel">отмена</a>
                </div>
            </div>
        </form>
    </div>
</div>


<div class="dialog width600px height250px" id="wa-theme-parent-dialog">
    <div class="dialog-background"> </div>
    <div class="dialog-window">
        <form>
            <div class="dialog-content">
                <div class="dialog-content-indent">
                    <h1>Родительская тема дизайна</h1>
                    <span class="wa-theme-dialog-error" style="color: red;font-weight: bold;"></span>
                    <div class="fields form">
                        <div class="field">
                            <div class="name">
                                Сменить родительскую тему
                            </div>
                            <div class="value">
                                <input type="hidden" name="id" value="<?php echo $_smarty_tpl->tpl_vars['theme']->value['id'];?>
">
                                <select name="parent_theme_id">
                                    <option value=""<?php if (!$_smarty_tpl->tpl_vars['theme']->value['parent_theme_id']){?> selected="selected"<?php }?>>Не задана</option>
                                <?php  $_smarty_tpl->tpl_vars['info'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['info']->_loop = false;
 $_smarty_tpl->tpl_vars['app_id'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['parent_themes']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['info']->key => $_smarty_tpl->tpl_vars['info']->value){
$_smarty_tpl->tpl_vars['info']->_loop = true;
 $_smarty_tpl->tpl_vars['app_id']->value = $_smarty_tpl->tpl_vars['info']->key;
?>
                                    <optgroup label="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['info']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
" title="<?php echo $_smarty_tpl->tpl_vars['app_id']->value;?>
">
                                        <?php  $_smarty_tpl->tpl_vars['theme_name'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['theme_name']->_loop = false;
 $_smarty_tpl->tpl_vars['parent_theme_id'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['info']->value['themes']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['theme_name']->key => $_smarty_tpl->tpl_vars['theme_name']->value){
$_smarty_tpl->tpl_vars['theme_name']->_loop = true;
 $_smarty_tpl->tpl_vars['parent_theme_id']->value = $_smarty_tpl->tpl_vars['theme_name']->key;
?>
                                            <?php $_smarty_tpl->tpl_vars['parent_theme_id'] = new Smarty_variable(((string)$_smarty_tpl->tpl_vars['app_id']->value).":".((string)$_smarty_tpl->tpl_vars['parent_theme_id']->value), null, 0);?>
                                            <option value="<?php echo $_smarty_tpl->tpl_vars['parent_theme_id']->value;?>
" title="<?php echo $_smarty_tpl->tpl_vars['parent_theme_id']->value;?>
"<?php if ($_smarty_tpl->tpl_vars['parent_theme_id']->value==$_smarty_tpl->tpl_vars['theme']->value['parent_theme_id']){?> selected="selected"<?php }elseif(($_smarty_tpl->tpl_vars['parent_theme_id']->value==((string)$_smarty_tpl->tpl_vars['theme']->value['app_id']).":".((string)$_smarty_tpl->tpl_vars['theme']->value['id']))||($_smarty_tpl->tpl_vars['parent_theme_id']->value==$_smarty_tpl->tpl_vars['theme']->value['id'])){?> disabled="disabled"<?php }?>><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['theme_name']->value, ENT_QUOTES, 'UTF-8', true);?>
 (<?php echo $_smarty_tpl->tpl_vars['parent_theme_id']->value;?>
)</option>
                                        <?php } ?>
                                    </optgroup>
                                <?php } ?>
                                </select>
                            </div>
                            <div class="value">
                                <p class="small"><i class="icon10 exclamation"></i>ВАЖНО: Подключение родительской темы позволит использовать в данной теме дизайна HTML-шаблоны, CSS-стили и изображения из родительской темы. Если родительская тема уже задана, то имейте ввиду, что ее смена может привести к ошибкам доступа к уже используемым файлам родительской темы. Для любой темы дизайна можно задать не более одной родительской темы.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
            <div class="dialog-buttons">
                <div class="dialog-buttons-gradient">
                    <input type="submit" class="button green" value="Сохранить">
                    или <a href="#/design/themes/" class="cancel">отмена</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    var theme_id = "<?php echo $_smarty_tpl->tpl_vars['theme']->value['id'];?>
";
    $(".theme-rename").on('click', function () {
        $("#wa-theme-name-dialog").waDialog({
            disableButtonsOnSubmit: true,
            onSubmit: function () {
                $.post("?module=design&action=themeRename", {
                    theme: theme_id, id: $("#wa-theme-rename-id").val(), name: $("#wa-theme-rename-name").val()
                }, function (response) {
                    $("#wa-theme-name-dialog").hide();
                    if (response.status == 'ok') {
                        if(response.data.redirect) {
                            location.href = location.href.replace(/(\?|#).*$/,'') + response.data.redirect;
                            location.reload();
                        } else {
                            location.reload();
                        }
                    } else {
                        alert(response.errors);
                    }
                }, "json");
                return false;
            }
        });
        return false;
    });

    $('#theme-settings').submit(function () {
        $("#theme-settings-iframe").one('load', function () {
            var response = $.parseJSON($(this).contents().find('body').html());
            if (response.status == 'ok') {
                $("#theme-settings-error").hide().empty();
                $("#theme-settings-message").fadeIn('slow', function () { $(this).fadeOut('slow');});
                waDesignLoad();
            } else {
                $("#theme-settings-error").html(response.errors ? response.errors : response);
                $("#theme-settings-error").fadeIn("slow");
            }
        });
    });

    $(function () {

        $('a.delete-image').click(function () {
            $(this).closest('div.value').find("input:hidden").val('');
            $(this).parent().remove();
            return false;
        });

        $('.wa-theme-image-select a').click(function () {
            var li = $(this).parent();
            var ul = li.parent();
            ul.find('li.selected').removeClass('selected');
            li.addClass('selected');
            ul.next('input').val(li.data('value'));
            return false;
        });
        var input2textarea = function(input) {
            var p = input.parent();
            var rm = false;
            if (!p.length) {
                p = $('<div></div>');
                p.append(input);
                rm = true;
            }
            var val = input.val();

            var html = p.html();
            html = html.replace(/value(\s*?=\s*?['"][\s\S]*?['"])*/, '');
            html = html.replace(/type\s*?=\s*?['"]text['"]/, '');
            html = html.replace('input', 'textarea');
            html = html.replace(/(\/\s*?<?php ?>>|>)/, '>' + val  + '</textarea>');

            if (rm) {
                p.remove();
            }

            return $(html);

        };

        var textarea2input = function(textarea) {
            var p = textarea.parent();
            var rm = false;
            if (!p.length) {
                p = $('<div></div>');
                p.append(textarea);
                rm = true;
            }
            var val = textarea.val();

            var html = p.html();
            html = html.replace('textarea', 'input type="text" value="' + val + '"');
            html = html.replace('</textarea>', '');

            if (rm) {
                p.remove();
            }

            return $(html);
        };
    $('.flexible').each(function () {
        var timeout = 250;
        var threshold = 50;
        var height = 45;
        var timer_id = null;
        var field = $(this);

        var onFocus = function() {
            this.selectionStart = this.selectionEnd = this.value.length;
        };
        var handler = function() {
            if (timer_id) {
                clearTimeout(timer_id);
                timer_id = null;
            }
            timer_id = setTimeout(function() {
                var val = field.val();
                if (val.length > threshold && field.is('input')) {
                    var textarea = input2textarea(field);
                    textarea.css('height', height);
                    field.replaceWith(textarea);
                    field = textarea;
                    field.focus();
                } else if (val.length <= threshold && field.is('textarea')) {
                    var input = textarea2input(field);
                    input.css('height', '');
                    field.replaceWith(input);
                    field = input;
                    field.focus();
                }
            }, timeout);
        };

        var p = field.parent();
        p.off('keydown', '#' + field.attr('id')).
          on('keydown',  '#' + field.attr('id'), handler);
        p.off('focus',   '#' + field.attr('id')).
          on('focus',    '#' + field.attr('id'), onFocus);

        // initial shot
        //handler();

    });
    });

    $(".theme-parent").on('click', function () {
        $("#wa-theme-parent-dialog").waDialog({
            disableButtonsOnSubmit: true,
            onSubmit: function () {
                var data = $(this).serialize();
                $.post("?module=design&action=themeParent", data, function (response) {
                    $("#wa-theme-parent-dialog").hide();
                    if (response.status == 'ok') {
                        location.reload();
                    } else {
                        alert(response.errors);
                    }
                }, "json");
                return false;
            }
        });
        return false;
    });

    $(".theme-copy").on('click', function () {
        $.post("?module=design&action=themeCopy", { theme: theme_id}, function (response) {
            if (response.status == 'ok') {
                if(response.data.redirect) {
                    location.href = location.href.replace(/#.*$/,'') + response.data.redirect;
                    location.reload();
                } else {
                    location.reload(true);
                }
            } else {
                alert(response.errors);
            }
        }, "json");
        return false;
    });

    $(".theme-reset").on('click', function () {
        if (!$(this).hasClass('disabled'))  {
            $('#wa-theme-reset-dialog').waDialog({
                disableButtonsOnSubmit: true,
                onSubmit: function () {
                    $.post("?module=design&action=themeReset", $(this).serialize(), function (response) {
                        if (response.status == 'ok') {
                            if(response.data.redirect) {
                                location.href = location.href.replace(/(\?|#).*$/,'') + response.data.redirect;
                                location.reload();
                            } else {
                                location.reload();
                            }
                        } else {
                            alert(response.errors);
                        }
                    }, "json");
                    return false;
                }
            });
        }
        return false;
    });

    $("#theme-start-using").on('click', function () {
        if (!$(this).hasClass('disabled'))  {
            $('#wa-theme-start-using-dialog').waDialog({
                disableButtonsOnSubmit: true,
                onSubmit: function () {
                    $.post("?module=design&action=themeUse", $(this).serialize(), function (response) {
                        if (response.status == 'ok') {
                            location.href = '<?php echo $_smarty_tpl->tpl_vars['design_url']->value;?>
theme=' + response.data.theme + '&domain=' + response.data.domain + '&route=' + response.data.route;
                            location.reload();
                        } else {
                            alert(response.errors);
                        }
                    }, "json");
                    return false;
                }
            });
        }
        return false;
    });


    $(".theme-delete").on('click', function () {
        if (!$(this).hasClass('disabled') && confirm($(this).attr('title'))) {
            $.post("?module=design&action=themeDelete", { theme: theme_id}, function (response) {
                if (response.status == 'ok') {
                    if(response.data.theme_id) {
                        $('#wa-theme-block-' + response.data.theme_id).remove();
                        $('#wa-theme-list-' + response.data.theme_id).remove();
                    }
                    $('#wa-theme-list a').each(function () {
                        if ($(this).attr('href').indexOf('theme=' + theme_id) != -1) {
                            $(this).parent().remove();
                        }
                    })
                    location.href = $('#wa-theme-list li:first a').attr('href');
                } else {
                    alert(response.errors);
                }
            }, "json");

        }
        return false;

    });
    var url = $('#wa-theme-routing-url');
    if(url.length) {
        $('#wa-theme-<?php echo $_smarty_tpl->tpl_vars['theme']->value['id'];?>
 .js-theme-routing-url').removeClass('js-theme-routing-url').wrap('<a href="'+url.attr('href')+'"></a>');
    }
    
    // colorpickers
    $('.color').each(function() {
        var input = $(this);
        var replacer = $('<span class="color-replacer">' + 
            '<i class="icon16 color" style="background: #'+input.val().substr(1)+'"></i>' + 
            '</span>').insertAfter(input);
        var picker = $('<div style="display:none;" class="color-picker"></div>').
            insertAfter(replacer);
        var farbtastic = $.farbtastic(picker, function(color) {
            replacer.find('i').css('background', color);
            input.val(color);
        });
        farbtastic.setColor('#'+input.val());
        replacer.click(function() {
            picker.slideToggle(200);
            return false;
        });
        
        var timer_id;
        input.unbind('keydown').bind('keydown', function() {
            if (timer_id) {
                clearTimeout(timer_id);
            }
            timer_id = setTimeout(function() {
                farbtastic.setColor(input.val());
            }, 250);
        });
        
    });
    
    $('.create-new-route-control').focus(function(){
        $('#create-new-route-choice').attr('checked', 'checked');
    });

</script><?php }} ?>