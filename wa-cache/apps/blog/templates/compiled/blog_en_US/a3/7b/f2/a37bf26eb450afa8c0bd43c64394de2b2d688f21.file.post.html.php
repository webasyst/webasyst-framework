<?php /* Smarty version Smarty-3.1.14, created on 2015-03-17 22:39:38
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/blog/themes/default/post.html" */ ?>
<?php /*%%SmartyHeaderCode:2124382571550882fa196985-30927104%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'a37bf26eb450afa8c0bd43c64394de2b2d688f21' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/blog/themes/default/post.html',
      1 => 1423490048,
      2 => 'file',
    ),
    '5eea933ad2892eac643b69b8e01930e31660b01c' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/blog/themes/default/post.album.html',
      1 => 1423490048,
      2 => 'file',
    ),
    'd1610b3394e2dae9940fa66a096411cf017f228b' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/blog/themes/default/comment.html',
      1 => 1423490048,
      2 => 'file',
    ),
    '049fff5df14b3c6d0b178e30ab8736c85b5fcb38' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/blog/themes/default/comments.html',
      1 => 1423490048,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '2124382571550882fa196985-30927104',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'post' => 0,
    'output' => 0,
    'frontend_post' => 0,
    'show_comments' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_550882fa6930b5_35939884',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_550882fa6930b5_35939884')) {function content_550882fa6930b5_35939884($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_date_format')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.date_format.php';
if (!is_callable('smarty_modifier_wa_datetime')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/modifier.wa_datetime.php';
?><div itemscope itemtype="http://schema.org/BlogPosting">

    <article class="post" role="main" id="post-<?php echo $_smarty_tpl->tpl_vars['post']->value['id'];?>
">
        
        <h1>
            <span itemprop="name"><?php echo $_smarty_tpl->tpl_vars['post']->value['title'];?>
</span>
            
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
        </h1>

        <div class="credentials">
            <?php if ($_smarty_tpl->tpl_vars['post']->value['contact_id']){?>
                <a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['user']['posts_link'];?>
">
                    <img src="<?php echo $_smarty_tpl->tpl_vars['post']->value['user']['photo_url_20'];?>
" class="userpic" alt="">
                </a>
                <a href="<?php echo $_smarty_tpl->tpl_vars['post']->value['user']['posts_link'];?>
" class="username" itemprop="author"><?php echo $_smarty_tpl->tpl_vars['post']->value['user']['name'];?>
</a>
            <?php }else{ ?>
                <img src="<?php echo $_smarty_tpl->tpl_vars['post']->value['user']['photo_url_20'];?>
" class="userpic" alt="">
                <span class="username" itemprop="author"><?php echo $_smarty_tpl->tpl_vars['post']->value['user']['name'];?>
</span>
            <?php }?>
            <meta itemprop="datePublished" content="<?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['post']->value['datetime'],'Y-m-d\TH:i');?>
">
            
            
            
            <span class="hint date" title="<?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['post']->value['datetime']);?>
"><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['post']->value['datetime'],"humandate");?>
</span>
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

        <div class="text" itemprop="articleBody">
            <?php echo $_smarty_tpl->tpl_vars['post']->value['text'];?>

        </div>

        <?php if ($_smarty_tpl->tpl_vars['post']->value['album_id']&&$_smarty_tpl->tpl_vars['post']->value['album']['id']&&$_smarty_tpl->tpl_vars['post']->value['album']['photos']){?>
            <?php /*  Call merged included template "post.album.html" */
$_tpl_stack[] = $_smarty_tpl;
 $_smarty_tpl = $_smarty_tpl->setupInlineSubTemplate('post.album.html', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('album'=>$_smarty_tpl->tpl_vars['post']->value['album']), 0, '2124382571550882fa196985-30927104');
content_550882fa3834f1_10940211($_smarty_tpl);
$_smarty_tpl = array_pop($_tpl_stack); /*  End of included template "post.album.html" */?>
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

        

    </article>

    
    <?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['frontend_post']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?>
        <?php if (!empty($_smarty_tpl->tpl_vars['output']->value['footer'])){?><?php echo $_smarty_tpl->tpl_vars['output']->value['footer'];?>
<?php }?>
    <?php } ?>

    <?php if ($_smarty_tpl->tpl_vars['show_comments']->value&&$_smarty_tpl->tpl_vars['post']->value['comments_allowed']){?>
        <?php if ($_smarty_tpl->tpl_vars['post']->value['status']==blogPostModel::STATUS_PUBLISHED){?>
            <?php /*  Call merged included template "comments.html" */
$_tpl_stack[] = $_smarty_tpl;
 $_smarty_tpl = $_smarty_tpl->setupInlineSubTemplate('comments.html', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0, '2124382571550882fa196985-30927104');
content_550882fa49bd71_44479199($_smarty_tpl);
$_smarty_tpl = array_pop($_tpl_stack); /*  End of included template "comments.html" */?>
        <?php }else{ ?>
            <h4>Comments for unpublished posts are disabled.</h4>
        <?php }?>
    <?php }?>

</div><?php }} ?><?php /* Smarty version Smarty-3.1.14, created on 2015-03-17 22:39:38
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/blog/themes/default/post.album.html" */ ?>
<?php if ($_valid && !is_callable('content_550882fa3834f1_10940211')) {function content_550882fa3834f1_10940211($_smarty_tpl) {?>

<?php if ($_smarty_tpl->tpl_vars['post']->value['album']['photos']){?>
    <div class="photo-album-attachment">
        <?php  $_smarty_tpl->tpl_vars['p'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['p']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['post']->value['album']['photos']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['p']->key => $_smarty_tpl->tpl_vars['p']->value){
$_smarty_tpl->tpl_vars['p']->_loop = true;
?>
            <figure itemscope itemtype ="http://schema.org/Photograph" class="photo">
                <?php if ($_smarty_tpl->tpl_vars['p']->value['description']){?>
                    <?php if (strstr($_smarty_tpl->tpl_vars['p']->value['description'],'<p>')){?>
                        <div itemprop="description"><?php echo $_smarty_tpl->tpl_vars['p']->value['description'];?>
</div>
                    <?php }else{ ?>
                        <p itemprop="description"><?php echo $_smarty_tpl->tpl_vars['p']->value['description'];?>
</p>
                    <?php }?>
                <?php }?>
                <a name="photo<?php echo $_smarty_tpl->tpl_vars['p']->value['id'];?>
"></a>
                <img src="<?php echo $_smarty_tpl->tpl_vars['p']->value['thumb_big']['url'];?>
" class="retinify" alt="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['p']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
">
            </figure>
        <?php } ?>
    </div>

    <?php if (blogPhotosBridge::is2xEnabled()){?>
        <script>$(function() { "use strict";
            if ($.Retina) {
                retinify();
            } else {
                $.ajax({ // Load JS script, making sure it WILL get cached in non-debug mode
                      dataType: "script",
                      url: "<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/js/jquery-plugins/jquery.retina.min.js?<?php echo $_smarty_tpl->tpl_vars['wa']->value->version(true);?>
",
                      cache: true
                }).done(retinify);
            }

            function retinify() {
                $.Retina && $('img.retinify').retina();
            }
        });</script>
    <?php }?>
<?php }?>

<?php }} ?><?php /* Smarty version Smarty-3.1.14, created on 2015-03-17 22:39:38
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/blog/themes/default/comments.html" */ ?>
<?php if ($_valid && !is_callable('content_550882fa49bd71_44479199')) {function content_550882fa49bd71_44479199($_smarty_tpl) {?><script type="text/javascript">
$(document).ready(function() {
    var provider_list = $('#user-auth-provider');
    
    provider_list.find('a').click(function () {
        var self = $(this);
        var provider = self.parents('li:first').attr('data-provider');
        
        if (provider != 'guest' && provider != 'signup') {
            var left = (screen.width-600)/2;
            var top = (screen.height-400)/2;
            window.open($(this).attr('href')<?php if (!$_smarty_tpl->tpl_vars['require_authorization']->value){?> + "&guest=1"<?php }?>,'oauth', "width=600,height=400,left="+left+",top="+top+",status=no,toolbar=no,menubar=no");
            return false;
        }
    })
});
</script>

<section class="comments" itemscope itemtype="http://schema.org/UserComments">
    <a name="comments"></a>
        <h3 class="comment-count" <?php if ($_smarty_tpl->tpl_vars['post']->value['comment_count']==0){?>style="display: none;"<?php }?>><?php echo _w('%d comment','%d comments',$_smarty_tpl->tpl_vars['post']->value['comment_count']);?>
</h3>

        <?php if ($_smarty_tpl->tpl_vars['post']->value['comment_count']==0){?>
            <ul>
                <!-- placeholder for new comments -->
            </ul>
        <?php }?>

        <?php $_smarty_tpl->tpl_vars['depth'] = new Smarty_variable(-1, null, 0);?>
        <?php  $_smarty_tpl->tpl_vars['comment'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['comment']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['post']->value['comments']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['comment']->key => $_smarty_tpl->tpl_vars['comment']->value){
$_smarty_tpl->tpl_vars['comment']->_loop = true;
?>
            <?php if ($_smarty_tpl->tpl_vars['comment']->value['depth']<$_smarty_tpl->tpl_vars['depth']->value){?>
                <?php $_smarty_tpl->tpl_vars['loop'] = new Smarty_variable($_smarty_tpl->tpl_vars['depth']->value-$_smarty_tpl->tpl_vars['comment']->value['depth'], null, 0);?>
                <?php if (isset($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"])) unset($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['name'] = "end-comment";
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['loop'] = is_array($_loop=$_smarty_tpl->tpl_vars['loop']->value) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['show'] = true;
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['max'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['loop'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['step'] = 1;
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['start'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['step'] > 0 ? 0 : $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['loop']-1;
if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['show']) {
    $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['total'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['loop'];
    if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['total'] == 0)
        $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['show'] = false;
} else
    $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['total'] = 0;
if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['show']):

            for ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['start'], $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration'] = 1;
                 $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration'] <= $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['total'];
                 $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index'] += $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['step'], $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration']++):
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['rownum'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index_prev'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index'] - $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['step'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index_next'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index'] + $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['step'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['first']      = ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration'] == 1);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['last']       = ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration'] == $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['total']);
?>

                        </li>
                    </ul>
                <?php endfor; endif; ?>
                <?php $_smarty_tpl->tpl_vars['depth'] = new Smarty_variable($_smarty_tpl->tpl_vars['comment']->value['depth'], null, 0);?>
            <?php }?>
            <?php if ($_smarty_tpl->tpl_vars['comment']->value['depth']==$_smarty_tpl->tpl_vars['depth']->value){?>

                </li>
                <li>
            <?php }?>
            <?php if ($_smarty_tpl->tpl_vars['comment']->value['depth']>$_smarty_tpl->tpl_vars['depth']->value){?>

            <ul>
                <li>
                <?php $_smarty_tpl->tpl_vars['depth'] = new Smarty_variable($_smarty_tpl->tpl_vars['comment']->value['depth'], null, 0);?>
            <?php }?>

            <?php /*  Call merged included template "comment.html" */
$_tpl_stack[] = $_smarty_tpl;
 $_smarty_tpl = $_smarty_tpl->setupInlineSubTemplate("comment.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0, '2124382571550882fa196985-30927104');
content_550882fa50be72_21272138($_smarty_tpl);
$_smarty_tpl = array_pop($_tpl_stack); /*  End of included template "comment.html" */?>
            
        <?php } ?>
        <?php if (isset($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"])) unset($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['name'] = "end-comment";
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['loop'] = is_array($_loop=$_smarty_tpl->tpl_vars['depth']->value) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['show'] = true;
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['max'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['loop'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['step'] = 1;
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['start'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['step'] > 0 ? 0 : $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['loop']-1;
if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['show']) {
    $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['total'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['loop'];
    if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['total'] == 0)
        $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['show'] = false;
} else
    $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['total'] = 0;
if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['show']):

            for ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['start'], $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration'] = 1;
                 $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration'] <= $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['total'];
                 $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index'] += $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['step'], $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration']++):
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['rownum'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index_prev'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index'] - $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['step'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index_next'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['index'] + $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['step'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['first']      = ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration'] == 1);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['last']       = ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['iteration'] == $_smarty_tpl->tpl_vars['smarty']->value['section']["end-comment"]['total']);
?>

                    </li>
                </ul>
        <?php endfor; endif; ?>
        </li>
    </ul>

</section>

<section class="comment-form">
    <a name="comment0"></a>
    <form action="<?php echo $_smarty_tpl->tpl_vars['post']->value['comment_link'];?>
" method="post">
        <h4>Add comment</h4>
        <?php $_smarty_tpl->tpl_vars['current_user_id'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->userId(), null, 0);?>

        <div class="comment-form-fields">
            <?php if (empty($_smarty_tpl->tpl_vars['current_user_id']->value)){?>
                <?php if ($_smarty_tpl->tpl_vars['require_authorization']->value){?>
                <div id="user-auth-provider" data-provider="signup" <?php if ($_smarty_tpl->tpl_vars['current_auth_source']->value!='signup'){?>style="display:none"<?php }?>>
                    <?php echo sprintf('To add a comment, please <a href="%s">sign up</a> or <a href="%s">log in</a>.',$_smarty_tpl->tpl_vars['wa']->value->signupUrl(),$_smarty_tpl->tpl_vars['wa']->value->loginUrl());?>

                </div>
                <?php }elseif($_smarty_tpl->tpl_vars['auth_adapters']->value){?>
                    <ul class="auth-type" id="user-auth-provider">
                        <?php if ($_smarty_tpl->tpl_vars['require_authorization']->value){?>
                        <li data-provider="signup" <?php if (!$_smarty_tpl->tpl_vars['current_auth_source']->value){?>class="selected"<?php }?>><a href="#">Sign up</a></li>
                        <?php }else{ ?>
                        <li data-provider="guest" <?php if (!$_smarty_tpl->tpl_vars['current_auth_source']->value){?>class="selected"<?php }?>><a href="#">Guest</a></li>
                        <?php }?>
                        <?php  $_smarty_tpl->tpl_vars['adapter'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['adapter']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['auth_adapters']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['adapter']->key => $_smarty_tpl->tpl_vars['adapter']->value){
$_smarty_tpl->tpl_vars['adapter']->_loop = true;
?>
                        <?php $_smarty_tpl->tpl_vars['adapter_id'] = new Smarty_variable($_smarty_tpl->tpl_vars['adapter']->value->getId(), null, 0);?>
                        <li data-provider="<?php echo $_smarty_tpl->tpl_vars['adapter_id']->value;?>
" <?php if ($_smarty_tpl->tpl_vars['current_auth_source']->value==$_smarty_tpl->tpl_vars['adapter_id']->value){?>class="selected"<?php }?>><a href="<?php echo $_smarty_tpl->tpl_vars['adapter']->value->getCallbackUrl(0);?>
&app=blog"><img src="<?php echo $_smarty_tpl->tpl_vars['adapter']->value->getIcon();?>
" alt=""><?php echo $_smarty_tpl->tpl_vars['adapter']->value->getName();?>
</a></li>
                        <?php } ?>
                    </ul>
                <?php }?>
                <div id="user-auth-provider" class="tab clear-left" data-provider="guest" <?php if ($_smarty_tpl->tpl_vars['current_auth_source']->value){?>style="display:none"<?php }?>>
                    <p>
                        <label for="comment_name">Your name</label>
                        <input type="text" name="name" id="comment_name" class="bold" value="">
                    </p>
                    <p>
                        <label for="comment_email">Email</label>
                        <input type="text" name="email" id="comment_email">
                    </p>
                    <p>
                        <label for="comment_site">Website</label>
                        <input type="text" name="site" id="comment_site">
                    </p>
                </div>
                <?php if ($_smarty_tpl->tpl_vars['require_authorization']->value){?>
                <div class="tab" data-provider="signup" <?php if ($_smarty_tpl->tpl_vars['current_auth_source']->value!='signup'){?>style="display:none"<?php }?>>
                    <?php echo sprintf('To add a comment, please <a href="%s">sign up</a> or <a href="%s">log in</a>.',$_smarty_tpl->tpl_vars['wa']->value->signupUrl(),$_smarty_tpl->tpl_vars['wa']->value->loginUrl());?>

                </div>
                <?php }?>
                <?php  $_smarty_tpl->tpl_vars['adapter'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['adapter']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['auth_adapters']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['adapter']->key => $_smarty_tpl->tpl_vars['adapter']->value){
$_smarty_tpl->tpl_vars['adapter']->_loop = true;
?>
                    <div class="tab" data-provider="<?php echo $_smarty_tpl->tpl_vars['adapter']->value->getId();?>
" <?php if ($_smarty_tpl->tpl_vars['current_auth_source']->value!=$_smarty_tpl->tpl_vars['adapter']->value->getId()){?>style="display:none"<?php }?>>
                        <p>
                            <label for="name">Your name</label>
                            <strong><img src="<?php echo $_smarty_tpl->tpl_vars['adapter']->value->getIcon();?>
" class="userpic" /><?php echo $_smarty_tpl->tpl_vars['current_auth']->value['name'];?>
</strong>
                            <a href="<?php echo $_smarty_tpl->tpl_vars['wa_app_url']->value;?>
logout">log out</a>
                        </p>
                    </div>
                <?php } ?>
            <?php }else{ ?>
                <p>
                    <label>Your name</label>
                    <img src="<?php echo $_smarty_tpl->tpl_vars['wa']->value->user()->getPhoto(20);?>
" class="userpic" alt=""> <strong><?php echo $_smarty_tpl->tpl_vars['wa']->value->user('name');?>
</strong> <a href="<?php echo $_smarty_tpl->tpl_vars['wa_app_url']->value;?>
logout">log out</a>
                </p>
            <?php }?>

            <p class="comment-body">
                <label for="comment_text">Comment</label>
                <textarea id="comment_text" name="text" rows="10" cols="45"></textarea>
            </p>
        </div>
        <div class="comment-submit">
            <?php if ($_smarty_tpl->tpl_vars['request_captcha']->value&&empty($_smarty_tpl->tpl_vars['current_user_id']->value)){?>
                <?php echo $_smarty_tpl->tpl_vars['wa']->value->captcha();?>

            <?php }?>
            <input type="hidden" name="auth_provider" value="<?php echo (($tmp = @$_smarty_tpl->tpl_vars['current_auth_source']->value)===null||$tmp==='' ? blogCommentModel::AUTH_GUEST : $tmp);?>
">
            <input type="hidden" name="parent" value="0">
            <input type="submit" value="Add comment">
            <img src="<?php echo $_smarty_tpl->tpl_vars['wa_url']->value;?>
wa-content/img/loading16.gif" style="display: none;" alt="">
        </div>
    </form>
</section>

<?php }} ?><?php /* Smarty version Smarty-3.1.14, created on 2015-03-17 22:39:38
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/blog/themes/default/comment.html" */ ?>
<?php if ($_valid && !is_callable('content_550882fa50be72_21272138')) {function content_550882fa50be72_21272138($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_date_format')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.date_format.php';
if (!is_callable('smarty_modifier_wa_datetime')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/modifier.wa_datetime.php';
?><div class="comment" id="comment-<?php echo $_smarty_tpl->tpl_vars['comment']->value['id'];?>
">
    <div class="credentials">
        <a name="comment<?php echo $_smarty_tpl->tpl_vars['comment']->value['id'];?>
"<?php if ($_smarty_tpl->tpl_vars['comment']->value['site']){?> href="<?php echo $_smarty_tpl->tpl_vars['comment']->value['site'];?>
"<?php }?>>
            <img src="<?php echo (($tmp = @$_smarty_tpl->tpl_vars['comment']->value['user']['photo_url_20'])===null||$tmp==='' ? $_smarty_tpl->tpl_vars['comment']->value['user']['photo_url'] : $tmp);?>
" class="userpic<?php if ($_smarty_tpl->tpl_vars['comment']->value['auth_provider']&&($_smarty_tpl->tpl_vars['comment']->value['auth_provider']!=blogCommentModel::AUTH_GUEST)&&($_smarty_tpl->tpl_vars['comment']->value['auth_provider']!=blogCommentModel::AUTH_USER)){?> icon16<?php }?>" alt="">
        </a>
        <?php if (empty($_smarty_tpl->tpl_vars['comment']->value['site'])){?>
            <span class="username" itemprop="creator"><?php echo $_smarty_tpl->tpl_vars['comment']->value['name'];?>
</span>
        <?php }else{ ?>
            <a href="<?php echo $_smarty_tpl->tpl_vars['comment']->value['site'];?>
" class="username" itemprop="creator"><?php echo $_smarty_tpl->tpl_vars['comment']->value['name'];?>
</a>
        <?php }?>

        <?php if ($_smarty_tpl->tpl_vars['comment']->value['user']['id']){?>
            <span class="staff"><?php echo $_smarty_tpl->tpl_vars['wa']->value->accountName();?>
</span>
        <?php }?>

        
        <?php if (!empty($_smarty_tpl->tpl_vars['comment']->value['plugins']['authorname_suffix'])){?>
            <?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['comment']->value['plugins']['authorname_suffix']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?><?php echo $_smarty_tpl->tpl_vars['output']->value;?>
<?php } ?>
        <?php }?>

        <meta itemprop="commentTime" content="<?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['comment']->value['datetime'],'Y-m-d\TH:i');?>
">
        <span class="hint date" title="<?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['comment']->value['datetime']);?>
" datetime="<?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['comment']->value['datetime'],'Y-m-d\TH:i');?>
"><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['comment']->value['datetime'],"humandatetime");?>
</span>
    </div>

    
    <?php if (!empty($_smarty_tpl->tpl_vars['comment']->value['plugins']['before'])){?>
        <?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['comment']->value['plugins']['before']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?><?php echo $_smarty_tpl->tpl_vars['output']->value;?>
<?php } ?>
    <?php }?>

    <div class="text" itemprop="commentText"><?php echo nl2br($_smarty_tpl->tpl_vars['comment']->value['text']);?>
</div>

    
    <?php if (!empty($_smarty_tpl->tpl_vars['comment']->value['plugins']['after'])){?>
        <?php  $_smarty_tpl->tpl_vars['output'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['output']->_loop = false;
 $_smarty_tpl->tpl_vars['plugin'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['comment']->value['plugins']['after']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['output']->key => $_smarty_tpl->tpl_vars['output']->value){
$_smarty_tpl->tpl_vars['output']->_loop = true;
 $_smarty_tpl->tpl_vars['plugin']->value = $_smarty_tpl->tpl_vars['output']->key;
?><?php echo $_smarty_tpl->tpl_vars['output']->value;?>
<?php } ?>
    <?php }?>

    <div class="actions">
        <a class="inline-link comment-reply" href="#"><b><i>reply</i></b></a>
    </div>
</div>
<?php }} ?>