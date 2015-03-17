<?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 11:43:03
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/reviews.html" */ ?>
<?php /*%%SmartyHeaderCode:12045487665506979712b5d0-61738973%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '1c7c8f95d9c84169aa6b0852c31194045b268194' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/reviews.html',
      1 => 1423488546,
      2 => 'file',
    ),
    '88286da27cefb4f9cb904d9aac4510816a6f5225' => 
    array (
      0 => '/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/review.html',
      1 => 1423488545,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '12045487665506979712b5d0-61738973',
  'function' => 
  array (
    'review_reviews' => 
    array (
      'parameter' => 
      array (
      ),
      'compiled' => '',
    ),
  ),
  'variables' => 
  array (
    'wa_app_static_url' => 0,
    'wa_theme_url' => 0,
    'product' => 0,
    'wa' => 0,
    'reviews_count' => 0,
    'page' => 0,
    'reviews' => 0,
    'review' => 0,
    'depth' => 0,
    'loop' => 0,
    'reply_allowed' => 0,
    'current_user_id' => 0,
    'require_authorization' => 0,
    'auth_adapters' => 0,
    'current_auth_source' => 0,
    'adapter' => 0,
    'adapter_id' => 0,
    'current_auth' => 0,
    'request_captcha' => 0,
  ),
  'has_nocache_code' => 0,
  'version' => 'Smarty-3.1.14',
  'unifunc' => 'content_55069797408ea2_75872534',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_55069797408ea2_75872534')) {function content_55069797408ea2_75872534($_smarty_tpl) {?><script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_app_static_url']->value;?>
js/rate.widget.js"></script>
<script type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['wa_theme_url']->value;?>
reviews.js"></script>

<article>
    
    <?php echo $_smarty_tpl->tpl_vars['wa']->value->title(sprintf('%s отзывы',$_smarty_tpl->tpl_vars['product']->value['name']));?>

    <h1><?php echo sprintf('%s отзывы',htmlspecialchars($_smarty_tpl->tpl_vars['product']->value['name'], ENT_QUOTES, 'UTF-8', true));?>
</h1>
    
    <!-- product page navigation -->
    <ul class="product-nav">
        <li><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productUrl($_smarty_tpl->tpl_vars['product']->value);?>
">Обзор</a></li>
        <li class="selected">
            <a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productUrl($_smarty_tpl->tpl_vars['product']->value,'reviews');?>
">Отзывы</a>
            <span class="hint reviews-count" itemprop="reviewCount"><?php echo $_smarty_tpl->tpl_vars['reviews_count']->value;?>
</span>
        </li>
        <?php  $_smarty_tpl->tpl_vars['page'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['page']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['product']->value['pages']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['page']->key => $_smarty_tpl->tpl_vars['page']->value){
$_smarty_tpl->tpl_vars['page']->_loop = true;
?>
        <li><a href="<?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->productUrl($_smarty_tpl->tpl_vars['product']->value,'page',array('page_url'=>$_smarty_tpl->tpl_vars['page']->value['url']));?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['page']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</a></li>
        <?php } ?>
    </ul>
    
    <!-- reviews begin -->
    <?php if (!function_exists('smarty_template_function_review_reviews')) {
    function smarty_template_function_review_reviews($_smarty_tpl,$params) {
    $saved_tpl_vars = $_smarty_tpl->tpl_vars;
    foreach ($_smarty_tpl->smarty->template_functions['review_reviews']['parameter'] as $key => $value) {$_smarty_tpl->tpl_vars[$key] = new Smarty_variable($value);};
    foreach ($params as $key => $value) {$_smarty_tpl->tpl_vars[$key] = new Smarty_variable($value);}?>
        <?php $_smarty_tpl->tpl_vars['depth'] = new Smarty_variable(-1, null, 0);?>
        <?php  $_smarty_tpl->tpl_vars['review'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['review']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['reviews']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['review']->key => $_smarty_tpl->tpl_vars['review']->value){
$_smarty_tpl->tpl_vars['review']->_loop = true;
?>
          <?php if ($_smarty_tpl->tpl_vars['review']->value['depth']<$_smarty_tpl->tpl_vars['depth']->value){?>
          
            <?php $_smarty_tpl->tpl_vars['loop'] = new Smarty_variable(($_smarty_tpl->tpl_vars['depth']->value-$_smarty_tpl->tpl_vars['review']->value['depth']), null, 0);?>
            <?php if (isset($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"])) unset($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['name'] = "end-review";
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['loop'] = is_array($_loop=$_smarty_tpl->tpl_vars['loop']->value) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['show'] = true;
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['max'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['loop'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['step'] = 1;
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['start'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['step'] > 0 ? 0 : $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['loop']-1;
if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['show']) {
    $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['total'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['loop'];
    if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['total'] == 0)
        $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['show'] = false;
} else
    $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['total'] = 0;
if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['show']):

            for ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['start'], $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration'] = 1;
                 $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration'] <= $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['total'];
                 $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index'] += $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['step'], $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration']++):
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['rownum'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index_prev'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index'] - $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['step'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index_next'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index'] + $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['step'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['first']      = ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration'] == 1);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['last']       = ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration'] == $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['total']);
?>
                <ul class="reviews-branch"></ul>
                </li>
              </ul>
            <?php endfor; endif; ?>
            
            <?php $_smarty_tpl->tpl_vars['depth'] = new Smarty_variable($_smarty_tpl->tpl_vars['review']->value['depth'], null, 0);?>
          <?php }?>
          
          <?php if ($_smarty_tpl->tpl_vars['review']->value['depth']==$_smarty_tpl->tpl_vars['depth']->value){?>
            </li>
            <li data-id="<?php echo $_smarty_tpl->tpl_vars['review']->value['id'];?>
" data-parent-id="<?php echo $_smarty_tpl->tpl_vars['review']->value['parent_id'];?>
">
          <?php }?>
          
          <?php if ($_smarty_tpl->tpl_vars['review']->value['depth']>$_smarty_tpl->tpl_vars['depth']->value){?>
            <ul class="reviews-branch">
              <li data-id=<?php echo $_smarty_tpl->tpl_vars['review']->value['id'];?>
 data-parent-id="<?php echo $_smarty_tpl->tpl_vars['review']->value['parent_id'];?>
">
              <?php $_smarty_tpl->tpl_vars['depth'] = new Smarty_variable($_smarty_tpl->tpl_vars['review']->value['depth'], null, 0);?>
          <?php }?>
            <?php /*  Call merged included template "review.html" */
$_tpl_stack[] = $_smarty_tpl;
 $_smarty_tpl = $_smarty_tpl->setupInlineSubTemplate("review.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('reply_allowed'=>$_smarty_tpl->tpl_vars['reply_allowed']->value,'single_view'=>true,'review'=>$_smarty_tpl->tpl_vars['review']->value), 0, '12045487665506979712b5d0-61738973');
content_55069797280f71_56337781($_smarty_tpl);
$_smarty_tpl = array_pop($_tpl_stack); /*  End of included template "review.html" */?>
            <!-- sub review placeholder -->
        <?php } ?>
        
        <?php if (isset($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"])) unset($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['name'] = "end-review";
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['loop'] = is_array($_loop=$_smarty_tpl->tpl_vars['depth']->value) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['show'] = true;
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['max'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['loop'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['step'] = 1;
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['start'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['step'] > 0 ? 0 : $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['loop']-1;
if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['show']) {
    $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['total'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['loop'];
    if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['total'] == 0)
        $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['show'] = false;
} else
    $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['total'] = 0;
if ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['show']):

            for ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['start'], $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration'] = 1;
                 $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration'] <= $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['total'];
                 $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index'] += $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['step'], $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration']++):
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['rownum'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index_prev'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index'] - $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['step'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index_next'] = $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['index'] + $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['step'];
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['first']      = ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration'] == 1);
$_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['last']       = ($_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['iteration'] == $_smarty_tpl->tpl_vars['smarty']->value['section']["end-review"]['total']);
?>
              <ul class="reviews-branch"></ul>
              </li>
            </ul>
        <?php endfor; endif; ?>
    <?php $_smarty_tpl->tpl_vars = $saved_tpl_vars;
foreach (Smarty::$global_tpl_vars as $key => $value) if(!isset($_smarty_tpl->tpl_vars[$key])) $_smarty_tpl->tpl_vars[$key] = $value;}}?>

    
    <section class="reviews">
    
        <a name="reviewheader"></a>
        <h3 class="reviews-count-text" <?php if ($_smarty_tpl->tpl_vars['reviews_count']->value==0){?>style="display: none;"<?php }?>>
            <?php echo htmlspecialchars((_w('%d review for ','%d reviews for ',$_smarty_tpl->tpl_vars['reviews_count']->value)).($_smarty_tpl->tpl_vars['product']->value['name']), ENT_QUOTES, 'UTF-8', true);?>

        </h3>
       
        <!-- add review form -->
        <h4 class="write-review">
            <a href="#" class="inline-link"><b><i>Написать отзыв</i></b></a>
        </h4>
        
        <div class="review-form" id="product-review-form" <?php if ($_smarty_tpl->tpl_vars['reviews_count']->value>0){?>style="display:none;"<?php }?>>
            
            <?php $_smarty_tpl->tpl_vars['current_user_id'] = new Smarty_variable($_smarty_tpl->tpl_vars['wa']->value->userId(), null, 0);?>
            
            <form method="post">
                <div class="review-form-fields">
                
                    <?php if (empty($_smarty_tpl->tpl_vars['current_user_id']->value)&&$_smarty_tpl->tpl_vars['require_authorization']->value){?>
                        <p class="review-field"><?php echo sprintf('Чтобы добавить отзыв, пожалуйста, <a href="%s">зарегистрируйтесь</a> или <a href="%s">войдите</a>',$_smarty_tpl->tpl_vars['wa']->value->signupUrl(),$_smarty_tpl->tpl_vars['wa']->value->loginUrl());?>
</p>
                    <?php }else{ ?>
                
                        <?php if (!empty($_smarty_tpl->tpl_vars['current_user_id']->value)){?>
                            <p class="review-field"><label>Ваше имя</label>
                                <strong><img src="<?php echo $_smarty_tpl->tpl_vars['wa']->value->user()->getPhoto(20);?>
" class="userpic" alt=""><?php echo $_smarty_tpl->tpl_vars['wa']->value->user('name');?>
</strong>
                                <a href="?logout">выйти</a>
                            </p>
                        <?php }else{ ?>
                            <?php if ($_smarty_tpl->tpl_vars['auth_adapters']->value){?>
                                <ul id="user-auth-provider" class="menu-h auth-type">
                                    <li data-provider="guest"  <?php if ($_smarty_tpl->tpl_vars['current_auth_source']->value==shopProductReviewsModel::AUTH_GUEST){?>class="selected"<?php }?>><a href="#">Гость</a></li>
                                    <?php  $_smarty_tpl->tpl_vars['adapter'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['adapter']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['auth_adapters']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['adapter']->key => $_smarty_tpl->tpl_vars['adapter']->value){
$_smarty_tpl->tpl_vars['adapter']->_loop = true;
?>
                                        <?php $_smarty_tpl->tpl_vars['adapter_id'] = new Smarty_variable($_smarty_tpl->tpl_vars['adapter']->value->getId(), null, 0);?>
                                        <li data-provider="<?php echo $_smarty_tpl->tpl_vars['adapter_id']->value;?>
" <?php if ($_smarty_tpl->tpl_vars['current_auth_source']->value==$_smarty_tpl->tpl_vars['adapter_id']->value){?>class="selected"<?php }?>>
                                            <a href="<?php echo $_smarty_tpl->tpl_vars['adapter']->value->getCallbackUrl(0);?>
&app=shop<?php if (!$_smarty_tpl->tpl_vars['require_authorization']->value){?>&guest=1<?php }?>">
                                            <img src="<?php echo $_smarty_tpl->tpl_vars['adapter']->value->getIcon();?>
" alt=""><?php echo $_smarty_tpl->tpl_vars['adapter']->value->getName();?>
</a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            <?php }?>
                            
                            <div class="provider-fields" data-provider="<?php echo shopProductReviewsModel::AUTH_GUEST;?>
" <?php if ($_smarty_tpl->tpl_vars['current_auth_source']->value!=shopProductReviewsModel::AUTH_GUEST){?>style="display:none"<?php }?>>
                                <p class="review-field">
                                    <label>Ваше имя</label>
                                    <input type="text" name="name" id="review-name" value="">
                                </p>
                                <p class="review-field">
                                    <label>Email</label>
                                    <input type="text" name="email" id="review-email">
                                </p>
                                <p class="review-field">
                                    <label>Сайт</label>
                                    <input type="text" name="site" id="review-site">
                                </p>
                            </div>
                            
                            <?php if (!empty($_smarty_tpl->tpl_vars['auth_adapters']->value[$_smarty_tpl->tpl_vars['current_auth_source']->value])){?>
                                <?php $_smarty_tpl->tpl_vars['adapter'] = new Smarty_variable($_smarty_tpl->tpl_vars['auth_adapters']->value[$_smarty_tpl->tpl_vars['current_auth_source']->value], null, 0);?>
                                <div class="provider-fields" data-provider="<?php echo $_smarty_tpl->tpl_vars['adapter']->value->getId();?>
">
                                    <p class="review-field"><label>Ваше имя</label>
                                        <strong><img src="<?php echo $_smarty_tpl->tpl_vars['adapter']->value->getIcon();?>
" class="userpic" /><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['current_auth']->value['name'], ENT_QUOTES, 'UTF-8', true);?>
</strong>
                                        <a href="?logout">выйти</a>
                                    </p>
                                </div>
                            <?php }?>
                        <?php }?>
                        
                    <br>
                    <p class="review-field">
                        <label for="review-title">Заголовок</label>
                        <input type="text" name="title" id="review-title" class="bold">
                    </p>
                    <p class="review-field">
                        <label>Оцените товар</label>
                        <a href="#" class="no-underline rate" data-rate="0" id="review-rate">
                            <?php $_smarty_tpl->tpl_vars['i'] = new Smarty_Variable;$_smarty_tpl->tpl_vars['i']->step = 1;$_smarty_tpl->tpl_vars['i']->total = (int)ceil(($_smarty_tpl->tpl_vars['i']->step > 0 ? 5+1 - (1) : 1-(5)+1)/abs($_smarty_tpl->tpl_vars['i']->step));
if ($_smarty_tpl->tpl_vars['i']->total > 0){
for ($_smarty_tpl->tpl_vars['i']->value = 1, $_smarty_tpl->tpl_vars['i']->iteration = 1;$_smarty_tpl->tpl_vars['i']->iteration <= $_smarty_tpl->tpl_vars['i']->total;$_smarty_tpl->tpl_vars['i']->value += $_smarty_tpl->tpl_vars['i']->step, $_smarty_tpl->tpl_vars['i']->iteration++){
$_smarty_tpl->tpl_vars['i']->first = $_smarty_tpl->tpl_vars['i']->iteration == 1;$_smarty_tpl->tpl_vars['i']->last = $_smarty_tpl->tpl_vars['i']->iteration == $_smarty_tpl->tpl_vars['i']->total;?><i class="icon16 star-empty"></i><?php }} ?>
                        </a>
                        <a href="javascript:void(0);" class="inline-link rate-clear" id="clear-review-rate" style="display: none;">
                            <b><i>очистить</i></b>
                        </a>
                        <input name="rate" type="hidden" value="0">
                    </p>
                    <p class="review-field">
                        <label for="review-text">Отзыв</label>
                        <textarea id="review-text" name="text" rows="10" cols="45"></textarea>
                    </p>
                
                    <div class="review-submit">
                        <?php if ($_smarty_tpl->tpl_vars['request_captcha']->value&&empty($_smarty_tpl->tpl_vars['current_user_id']->value)){?>
                            <?php echo $_smarty_tpl->tpl_vars['wa']->value->captcha();?>

                        <?php }?>
                        <input type="submit" class="save" value="Добавить отзыв">
                        <span class="review-add-form-status ajax-status" style="display: none;">
                            <i class="ajax-statuloading icon16 loading"><!--icon --></i>
                        </span>
                        
                        <em class="hint">Ctrl+Enter</em>
                        <input type="hidden" name="parent_id" value="0">
                        <input type="hidden" name="product_id" value="<?php if (isset($_smarty_tpl->tpl_vars['product']->value['id'])){?><?php echo $_smarty_tpl->tpl_vars['product']->value['id'];?>
<?php }else{ ?>0<?php }?>">
                        <input type="hidden" name="auth_provider" value="<?php echo (($tmp = @$_smarty_tpl->tpl_vars['current_auth_source']->value)===null||$tmp==='' ? shopProductReviewsModel::AUTH_GUEST : $tmp);?>
">
                        <input type="hidden" name="count" value="<?php echo $_smarty_tpl->tpl_vars['reviews_count']->value;?>
">
                    </div>
                <?php }?>
                
                </div>
            </form>
        </div>
        
        <!-- existing reviews list -->
        <ul class="reviews-branch">
        <?php  $_smarty_tpl->tpl_vars['review'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['review']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['reviews']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['review']->key => $_smarty_tpl->tpl_vars['review']->value){
$_smarty_tpl->tpl_vars['review']->_loop = true;
?>
            <li data-id=<?php echo $_smarty_tpl->tpl_vars['review']->value['id'];?>
 data-parent-id="0">
                <?php /*  Call merged included template "review.html" */
$_tpl_stack[] = $_smarty_tpl;
 $_smarty_tpl = $_smarty_tpl->setupInlineSubTemplate("review.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('reply_allowed'=>$_smarty_tpl->tpl_vars['reply_allowed']->value), 0, '12045487665506979712b5d0-61738973');
content_55069797280f71_56337781($_smarty_tpl);
$_smarty_tpl = array_pop($_tpl_stack); /*  End of included template "review.html" */?>
                <?php if (!empty($_smarty_tpl->tpl_vars['review']->value['comments'])){?>
                    <?php smarty_template_function_review_reviews($_smarty_tpl,array('reviews'=>$_smarty_tpl->tpl_vars['review']->value['comments']));?>

                <?php }else{ ?>
                    <ul class="reviews-branch"></ul>
                <?php }?>
            </li>
        <?php } ?>
        </ul>
        
    </section>
    <!-- reviews end -->

</article><?php }} ?><?php /* Smarty version Smarty-3.1.14, created on 2015-03-16 11:43:03
         compiled from "/var/www/admin/data/www/pchelkinazabava.ru/wa-data/public/shop/themes/default/review.html" */ ?>
<?php if ($_valid && !is_callable('content_55069797280f71_56337781')) {function content_55069797280f71_56337781($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_date_format')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty3/plugins/modifier.date_format.php';
if (!is_callable('smarty_modifier_wa_datetime')) include '/var/www/admin/data/www/pchelkinazabava.ru/wa-system/vendors/smarty-plugins/modifier.wa_datetime.php';
?>

<?php if (!empty($_smarty_tpl->tpl_vars['ajax_append']->value)){?><li data-id=<?php echo $_smarty_tpl->tpl_vars['review']->value['id'];?>
 data-parent-id="<?php echo $_smarty_tpl->tpl_vars['review']->value['parent_id'];?>
"><?php }?>

<figure class="review" itemprop="review" itemscope itemtype="http://schema.org/Review">
    <div class="summary">
        <h6>
            <?php if (!$_smarty_tpl->tpl_vars['review']->value['parent_id']&&!empty($_smarty_tpl->tpl_vars['review']->value['rate'])){?>
            <span itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">
                <?php $_smarty_tpl->tpl_vars['rate'] = new Smarty_variable(round($_smarty_tpl->tpl_vars['review']->value['rate']), null, 0);?>
                <meta itemprop="worstRating" content = "1">
                <meta itemprop="ratingValue" content="<?php echo $_smarty_tpl->tpl_vars['rate']->value;?>
">
                <meta itemprop="bestRating" content = "5">
                <?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->ratingHtml($_smarty_tpl->tpl_vars['rate']->value);?>

            </span>
            <?php }?>
            <span itemprop="name"><?php if ($_smarty_tpl->tpl_vars['review']->value['title']){?><?php echo $_smarty_tpl->tpl_vars['review']->value['title'];?>
<?php }?></span>
        </h6>
         
        <?php if (empty($_smarty_tpl->tpl_vars['review']->value['site'])){?>
            <span class="username" itemprop="author"><?php echo $_smarty_tpl->tpl_vars['review']->value['author']['name'];?>
</span>
        <?php }else{ ?>
            <a href="<?php echo $_smarty_tpl->tpl_vars['review']->value['site'];?>
" class="username" itemprop="author"><?php echo $_smarty_tpl->tpl_vars['review']->value['author']['name'];?>
</a>
        <?php }?>
        
        <?php if (!empty($_smarty_tpl->tpl_vars['review']->value['author']['is_user'])){?>
            <span class="staff"><?php echo $_smarty_tpl->tpl_vars['wa']->value->shop->settings('name');?>
</span>
        <?php }?>
        
        <meta itemprop="datePublished" content="<?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['review']->value['datetime'],'Y-m-d');?>
">
        <span class="date" title="<?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['review']->value['datetime']);?>
"><?php echo smarty_modifier_wa_datetime($_smarty_tpl->tpl_vars['review']->value['datetime'],"humandatetime");?>
</span>
    </div>
    <?php if ($_smarty_tpl->tpl_vars['review']->value['text']){?>
        <p itemprop="description"><?php echo $_smarty_tpl->tpl_vars['review']->value['text'];?>
</p>
    <?php }?>
    
    <?php if ($_smarty_tpl->tpl_vars['reply_allowed']->value){?>
        <div class="actions">
            <a href="<?php if (isset($_smarty_tpl->tpl_vars['reply_link']->value)){?><?php echo $_smarty_tpl->tpl_vars['reply_link']->value;?>
<?php }else{ ?>#<?php }?>" class="review-reply inline-link"><b><i>ответить</i></b></a>
        </div>
    <?php }?>
</figure>

<?php if (!empty($_smarty_tpl->tpl_vars['ajax_append']->value)){?><ul class="reviews-branch"></ul></li><?php }?><?php }} ?>