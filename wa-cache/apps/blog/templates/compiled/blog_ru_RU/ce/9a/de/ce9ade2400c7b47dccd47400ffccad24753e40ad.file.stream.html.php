<?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:52:57
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/themes/default/stream.html" */ ?>
<?php /*%%SmartyHeaderCode:195588327254d8ada9422eb9-11510467%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'ce9ade2400c7b47dccd47400ffccad24753e40ad' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/themes/default/stream.html',
      1 => 1409656334,
      2 => 'file',
    ),
    '7419a4dcf9531b5820f47f1a6fea7ed2f1a9d5df' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/themes/default/stream_search.html',
      1 => 1416918038,
      2 => 'file',
    ),
    '952bf65c1a57c07068b5d054ae126840e8d88dd5' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/themes/default/stream_posts.html',
      1 => 1419597231,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '195588327254d8ada9422eb9-11510467',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'is_lazyloading' => 0,
    'wa' => 0,
    'stream_title' => 0,
    'page' => 0,
    'is_search' => 0,
    'pages' => 0,
    'posts' => 0,
    'posts_per_page' => 0,
    'loaded_post_count' => 0,
    'post_count' => 0,
    'p' => 0,
    'blog_query' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_54d8ada9607577_11824086',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54d8ada9607577_11824086')) {function content_54d8ada9607577_11824086($_smarty_tpl) {?>

<?php if (!$_smarty_tpl->tpl_vars['is_lazyloading']->value){?>
<div id="post-stream" role="main" class="lazyloading" <?php if ($_smarty_tpl->tpl_vars['wa']->value->param('blog_url')){?>itemscope itemtype="http://schema.org/Blog"<?php }?>> 
<?php }?>

    <?php if (!$_smarty_tpl->tpl_vars['is_lazyloading']->value&&!empty($_smarty_tpl->tpl_vars['stream_title']->value)){?><h1><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['stream_title']->value, ENT_QUOTES, 'UTF-8', true);?>
</h1><?php }?>
    
    <a name="page_<?php echo $_smarty_tpl->tpl_vars['page']->value;?>
"></a>

        <?php if ($_smarty_tpl->tpl_vars['is_search']->value){?>
            <?php /*  Call merged included template "stream_search.html" */
$_tpl_stack[] = $_smarty_tpl;
 $_smarty_tpl = $_smarty_tpl->setupInlineSubTemplate("stream_search.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0, '195588327254d8ada9422eb9-11510467');
content_54d8ada947bed3_73427623($_smarty_tpl);
$_smarty_tpl = array_pop($_tpl_stack); /*  End of included template "stream_search.html" */?>
        <?php }else{ ?>
            <?php /*  Call merged included template "stream_posts.html" */
$_tpl_stack[] = $_smarty_tpl;
 $_smarty_tpl = $_smarty_tpl->setupInlineSubTemplate("stream_posts.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0, '195588327254d8ada9422eb9-11510467');
content_54d8ada94c5ed8_44167458($_smarty_tpl);
$_smarty_tpl = array_pop($_tpl_stack); /*  End of included template "stream_posts.html" */?>
        <?php }?>

    <?php if ($_smarty_tpl->tpl_vars['is_lazyloading']->value||($_smarty_tpl->tpl_vars['page']->value==1)){?>
        <div class="pageless-wrapper"<?php if (!$_smarty_tpl->tpl_vars['is_lazyloading']->value){?> style="display:none;"<?php }?>>

        <?php if ($_smarty_tpl->tpl_vars['page']->value<$_smarty_tpl->tpl_vars['pages']->value){?>
            <?php $_smarty_tpl->tpl_vars['loaded_post_count'] = new Smarty_variable((count($_smarty_tpl->tpl_vars['posts']->value)+$_smarty_tpl->tpl_vars['posts_per_page']->value*($_smarty_tpl->tpl_vars['page']->value-1)), null, 0);?>
            <?php echo _w('%d post','%d posts',$_smarty_tpl->tpl_vars['loaded_post_count']->value);?>
&nbsp;<?php echo _w('of %d post','of %d posts',$_smarty_tpl->tpl_vars['post_count']->value);?>

            <br>
            <a href="?page=<?php echo $_smarty_tpl->tpl_vars['page']->value+1;?>
" class="pageless-link">Показать более ранние записи</a>
            <div class="pageless-progress" style="display:none;"><i class="icon16 loading"></i>Загрузка...</div>
        <?php }elseif(isset($_smarty_tpl->tpl_vars['page']->value)&&$_smarty_tpl->tpl_vars['pages']->value){?>
            <?php echo _w('%d post','%d posts',$_smarty_tpl->tpl_vars['post_count']->value);?>

        <?php }?>

        </div>
    <?php }?>

<?php if (!$_smarty_tpl->tpl_vars['is_lazyloading']->value){?>
</div>
<ul class="menu-h" id="stream-paging">
    <?php $_smarty_tpl->tpl_vars['p'] = new Smarty_Variable;$_smarty_tpl->tpl_vars['p']->step = 1;$_smarty_tpl->tpl_vars['p']->total = (int)ceil(($_smarty_tpl->tpl_vars['p']->step > 0 ? $_smarty_tpl->tpl_vars['pages']->value+1 - (1) : 1-($_smarty_tpl->tpl_vars['pages']->value)+1)/abs($_smarty_tpl->tpl_vars['p']->step));
if ($_smarty_tpl->tpl_vars['p']->total > 0){
for ($_smarty_tpl->tpl_vars['p']->value = 1, $_smarty_tpl->tpl_vars['p']->iteration = 1;$_smarty_tpl->tpl_vars['p']->iteration <= $_smarty_tpl->tpl_vars['p']->total;$_smarty_tpl->tpl_vars['p']->value += $_smarty_tpl->tpl_vars['p']->step, $_smarty_tpl->tpl_vars['p']->iteration++){
$_smarty_tpl->tpl_vars['p']->first = $_smarty_tpl->tpl_vars['p']->iteration == 1;$_smarty_tpl->tpl_vars['p']->last = $_smarty_tpl->tpl_vars['p']->iteration == $_smarty_tpl->tpl_vars['p']->total;?>
        <li<?php if ($_smarty_tpl->tpl_vars['p']->value==$_smarty_tpl->tpl_vars['page']->value){?> class="selected"<?php }?>><a href="<?php if ($_smarty_tpl->tpl_vars['p']->value==$_smarty_tpl->tpl_vars['page']->value){?>#page_<?php echo $_smarty_tpl->tpl_vars['page']->value;?>
<?php }else{ ?>?page=<?php echo $_smarty_tpl->tpl_vars['p']->value;?>
<?php }?>"><?php echo $_smarty_tpl->tpl_vars['p']->value;?>
</a></li>
    <?php }} ?>
</ul>
<?php }?>

<?php if ($_smarty_tpl->tpl_vars['page']->value==1&&!$_smarty_tpl->tpl_vars['wa']->value->globals('disable_pageless')){?>
<script type="text/javascript">
$.pageless({
        auto: true, // auto load next pages
        url: '?layout=lazyloading<?php if ($_smarty_tpl->tpl_vars['blog_query']->value){?>&query=<?php echo urlencode($_smarty_tpl->tpl_vars['blog_query']->value);?>
<?php }?>',
        times: 2,
        target: '.lazyloading:first',
        scroll: function(response){
            var progress = '';
            if (response) {
                progress = '<i class="icon16 loading"><'+'/i> <em>Загрузка...<'+'/em>';
            }
        },
        count: <?php echo $_smarty_tpl->tpl_vars['pages']->value;?>
,
        paging_selector:'#stream-paging'
        
        <?php if ($_smarty_tpl->tpl_vars['blog_query']->value){?>
            
            ,prepareContent: function(html) {
                // hightlight search query in content text after lazyloading
                var tmp = $('<div></div>').append(html);
                $.blog_utils.highlight(tmp);
                var html = tmp.html();
                tmp.remove();
                return html;
            }
            
        <?php }?>
});
</script>
<?php }?>
<?php }} ?><?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:52:57
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/themes/default/stream_search.html" */ ?>
<?php if ($_valid && !is_callable('content_54d8ada947bed3_73427623')) {function content_54d8ada947bed3_73427623($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_wa_datetime')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/modifier.wa_datetime.php';
if (!is_callable('smarty_modifier_truncate')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.truncate.php';
?>

<?php  $_smarty_tpl->tpl_vars['post'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['post']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['posts']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['post']->key => $_smarty_tpl->tpl_vars['post']->value){
$_smarty_tpl->tpl_vars['post']->_loop = true;
?>
    <section class="post search-match" id="post-<?php echo $_smarty_tpl->tpl_vars['post']->value['id'];?>
" <?php if ($_smarty_tpl->tpl_vars['wa']->value->param('blog_url')){?>itemprop="blogPosts"<?php }?> itemscope itemtype="http://schema.org/BlogPosting">
        <h3><a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['link'];?>
" itemprop="url"><?php echo $_smarty_tpl->tpl_vars['post']->value['title'];?>
</a></h3>
        <span class="hint">
            <?php if ($_smarty_tpl->tpl_vars['post']->value['user']['posts_link']){?>
                <a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['user']['posts_link'];?>
" class="username"><?php echo $_smarty_tpl->tpl_vars['post']->value['user']['name'];?>
</a>
            <?php }else{ ?>
                <span class="username"><?php echo $_smarty_tpl->tpl_vars['post']->value['user']['name'];?>
</span>
            <?php }?>
            <?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['post']->value['datetime'],"humandate");?>

        </span>
        <p>
            <?php echo smarty_modifier_truncate(preg_replace('!<[^>]*?>!', ' ', $_smarty_tpl->tpl_vars['post']->value['text']),400);?>

        </p>
    </section>
<?php }
if (!$_smarty_tpl->tpl_vars['post']->_loop) {
?>
    <?php if (!isset($_smarty_tpl->tpl_vars['page']->value)||$_smarty_tpl->tpl_vars['page']->value<2){?>
        <?php echo _w('%d post','%d posts',0);?>

    <?php }?>
<?php } ?><?php }} ?><?php /* Smarty version Smarty-3.1.14, created on 2015-02-09 16:52:57
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-apps/blog/themes/default/stream_posts.html" */ ?>
<?php if ($_valid && !is_callable('content_54d8ada94c5ed8_44167458')) {function content_54d8ada94c5ed8_44167458($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_wa_datetime')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/modifier.wa_datetime.php';
?>

<?php  $_smarty_tpl->tpl_vars['post'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['post']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['posts']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['post']->key => $_smarty_tpl->tpl_vars['post']->value){
$_smarty_tpl->tpl_vars['post']->_loop = true;
?>
    <section class="post" id="post-<?php echo $_smarty_tpl->tpl_vars['post']->value['id'];?>
" <?php if ($_smarty_tpl->tpl_vars['wa']->value->param('blog_url')){?>itemprop="blogPosts" <?php }?>itemscope itemtype="http://schema.org/BlogPosting">
        <h3>
            <a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['link'];?>
" itemprop="url"><?php echo $_smarty_tpl->tpl_vars['post']->value['title'];?>
</a>

            
            <?php if (!empty($_smarty_tpl->tpl_vars['post']->value['plugins']['post_title'])){?>
                <?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['post']->value['plugins']['post_title']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?><?php echo $_smarty_tpl->tpl_vars['output']->value;?>
<?php } ?>
            <?php }?>

        </h3>
        <div class="credentials">

            <?php if (isset($_smarty_tpl->tpl_vars['post']->value['user']['photo_url_20'])){?>
                <?php if ($_smarty_tpl->tpl_vars['post']->value['user']['posts_link']){?>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['user']['posts_link'];?>
">
                        <img src="<?php echo $_smarty_tpl->tpl_vars['post']->value['user']['photo_url_20'];?>
" class="userpic" alt="">
                    </a>
                <?php }else{ ?>
                    <img src="<?php echo $_smarty_tpl->tpl_vars['post']->value['user']['photo_url_20'];?>
" class="userpic" alt="">
                <?php }?>
            <?php }?>

            <?php if ($_smarty_tpl->tpl_vars['post']->value['user']['posts_link']){?>
                <a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['user']['posts_link'];?>
" class="username"><?php echo $_smarty_tpl->tpl_vars['post']->value['user']['name'];?>
</a>
            <?php }else{ ?>
                <span class="username"><?php echo $_smarty_tpl->tpl_vars['post']->value['user']['name'];?>
</span>
            <?php }?>
            <span class="hint date"><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['post']->value['datetime'],"humandate");?>
</span>
            <?php if ($_smarty_tpl->tpl_vars['show_comments']->value&&$_smarty_tpl->tpl_vars['post']->value['comments_allowed']){?>
                <?php if (!empty($_smarty_tpl->tpl_vars['post']->value['comment_count'])){?>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['link'];?>
#comments" class="small">
                        <?php echo _w('%d comment','%d comments',$_smarty_tpl->tpl_vars['post']->value['comment_count']);?>

                    </a>
                <?php }else{ ?>
                    
                <?php }?>
            <?php }?>

        </div>

        
        <?php if (!empty($_smarty_tpl->tpl_vars['post']->value['plugins']['before'])){?>
            <div class="text_before">
                <?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['post']->value['plugins']['before']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?><?php echo $_smarty_tpl->tpl_vars['output']->value;?>
<?php } ?>
            </div>
        <?php }?>

        <div class="text">
            <?php echo $_smarty_tpl->tpl_vars['post']->value['text'];?>

            <?php if ($_smarty_tpl->tpl_vars['post']->value['cutted']){?>
                <a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['link'];?>
"><?php echo (($tmp = @$_smarty_tpl->tpl_vars['post']->value['cut_link_label'])===null||$tmp==='' ? 'Читать далее →' : $tmp);?>
</a>
            <?php }?>
        </div>

        
        <?php if ($_smarty_tpl->tpl_vars['post']->value['album_id']&&$_smarty_tpl->tpl_vars['post']->value['album']['id']&&$_smarty_tpl->tpl_vars['post']->value['album']['photos']){?>
            <?php $_smarty_tpl->tpl_vars['photos_loaded'] = new Smarty_variable(1, null, 0);?> 
            <div class="photo-album-attachment">
                <ul class="thumbs">
                    <?php  $_smarty_tpl->tpl_vars['p'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['p']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['post']->value['album']['photos']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['p']->key => $_smarty_tpl->tpl_vars['p']->value){
$_smarty_tpl->tpl_vars['p']->_loop = true;
?>
                        <li><a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['link'];?>
#photo<?php echo $_smarty_tpl->tpl_vars['p']->value['id'];?>
"><img src="<?php echo $_smarty_tpl->tpl_vars['p']->value['thumb_crop']['url'];?>
" class="retinify" width="96" height="96" alt="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['p']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
"></a></li>
                    <?php } ?>
                </ul>
            </div>
        <?php }?>

        
        <?php if (!empty($_smarty_tpl->tpl_vars['post']->value['plugins']['after'])){?>
            <div class="text_after">
                <?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['post']->value['plugins']['after']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?><?php echo $_smarty_tpl->tpl_vars['output']->value;?>
<?php } ?>
            </div>
        <?php }?>

    </section>
<?php }
if (!$_smarty_tpl->tpl_vars['post']->_loop) {
?>
    <?php if ($_smarty_tpl->tpl_vars['page']->value<2){?>
        <?php echo _w('%d post','%d posts',0);?>

    <?php }?>
<?php } ?>

<?php if (!empty($_smarty_tpl->tpl_vars['photos_loaded']->value)&&blogPhotosBridge::is2xEnabled()){?>
    <script src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery-plugins/jquery.retina.min.js"></script>
    <script>$(function() { "use strict";
        $.Retina && $('img.retinify').retina();
    });</script>
<?php }?>
<?php }} ?>