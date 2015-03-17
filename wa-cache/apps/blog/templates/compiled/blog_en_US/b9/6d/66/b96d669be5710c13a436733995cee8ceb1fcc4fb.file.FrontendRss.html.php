<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 11:29:32
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/templates/actions/frontend/FrontendRss.html" */ ?>
<?php /*%%SmartyHeaderCode:17227062635506946ca9e8c6-40589806%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'b96d669be5710c13a436733995cee8ceb1fcc4fb' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/templates/actions/frontend/FrontendRss.html',
      1 => 1419597237,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '17227062635506946ca9e8c6-40589806',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'info' => 0,
    'posts' => 0,
    'row' => 0,
    'rss_author_tag' => 0,
    'p' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_5506946cba7832_07117344',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5506946cba7832_07117344')) {function content_5506946cba7832_07117344($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_truncate')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.truncate.php';
if (!is_callable('smarty_modifier_wa_datetime')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/modifier.wa_datetime.php';
?><?php echo '<?xml';?> version="1.0" encoding="utf-8" <?php echo '?>';?>

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['info']->value['title'], ENT_QUOTES, 'UTF-8', true);?>
</title>
    <link><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['info']->value['link'], ENT_QUOTES, 'UTF-8', true);?>
</link>
    <description><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['info']->value['description'], ENT_QUOTES, 'UTF-8', true);?>
</description>
    <language><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['info']->value['language'], ENT_QUOTES, 'UTF-8', true);?>
</language>
    <pubDate><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['info']->value['pubDate'], ENT_QUOTES, 'UTF-8', true);?>
</pubDate>
    <lastBuildDate><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['info']->value['lastBuildDate'], ENT_QUOTES, 'UTF-8', true);?>
</lastBuildDate>
    <atom:link href="<?php echo $_smarty_tpl->tpl_vars['info']->value['self'];?>
" rel="self" type="application/rss+xml" />
<?php  $_smarty_tpl->tpl_vars['row'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['row']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['posts']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['row']->key => $_smarty_tpl->tpl_vars['row']->value){
$_smarty_tpl->tpl_vars['row']->_loop = true;
?>

    <item>
      <title><?php if ($_smarty_tpl->tpl_vars['row']->value['title']){?><?php echo htmlspecialchars(preg_replace('!<[^>]*?>!', ' ', $_smarty_tpl->tpl_vars['row']->value['title']), ENT_QUOTES, 'UTF-8', true);?>
<?php }else{ ?><?php echo htmlspecialchars(smarty_modifier_truncate(preg_replace('!<[^>]*?>!', ' ', $_smarty_tpl->tpl_vars['row']->value['text']),60,'…'), ENT_QUOTES, 'UTF-8', true);?>
<?php }?></title>
      <?php if ($_smarty_tpl->tpl_vars['rss_author_tag']->value=='author'&&$_smarty_tpl->tpl_vars['row']->value['user']['email']){?>
          <author><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['row']->value['user']['email'], ENT_QUOTES, 'UTF-8', true);?>
 (<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['row']->value['user']['name'], ENT_QUOTES, 'UTF-8', true);?>
)</author>
      <?php }?>
      <link><?php echo $_smarty_tpl->tpl_vars['row']->value['link'];?>
</link>
      <description><![CDATA[<?php echo $_smarty_tpl->tpl_vars['row']->value['text'];?>
<?php if ($_smarty_tpl->tpl_vars['row']->value['album_id']&&$_smarty_tpl->tpl_vars['row']->value['album']['id']){?><?php  $_smarty_tpl->tpl_vars['p'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['p']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['row']->value['album']['photos']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['p']->key => $_smarty_tpl->tpl_vars['p']->value){
$_smarty_tpl->tpl_vars['p']->_loop = true;
?><?php if ($_smarty_tpl->tpl_vars['p']->value['description']){?><?php if (strstr($_smarty_tpl->tpl_vars['p']->value['description'],'<p>')){?><?php echo $_smarty_tpl->tpl_vars['p']->value['description'];?>
<?php }else{ ?><p><?php echo $_smarty_tpl->tpl_vars['p']->value['description'];?>
</p><?php }?><?php }?><p><img src="<?php echo $_smarty_tpl->tpl_vars['p']->value['thumb_big']['url'];?>
" alt="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['p']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
"></p><?php } ?><?php }?>]]></description>
      <pubDate><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['row']->value['datetime'],'DATE_RSS');?>
</pubDate>
      <guid isPermaLink="true"><?php echo $_smarty_tpl->tpl_vars['row']->value['link'];?>
</guid>
    </item>
<?php } ?>

  </channel>
</rss><?php }} ?>