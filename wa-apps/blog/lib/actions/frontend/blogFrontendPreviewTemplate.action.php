<?php
/**
 * Returns a preview HTML template for given blog id.
 * Post content is then injected into the HTML via javascript.
 */
class blogFrontendPreviewTemplateAction extends blogViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->setThemeTemplate('post.html', waRequest::param('theme', 'default'));
    }

    public function execute()
    {
        $blog_id = wa()->getRequest()->param('blog_url_type');
        if ($blog_id <= 0) {
            $blog_id = waRequest::request('blog_id', 0, 'int');
        }

        $this->setLayout(new blogFrontendLayout());

        // Get contact id and name as post author
        if (wa()->getUser()->get('is_user')) {
            $post_contact_id = wa()->getUser()->getId();
            $post_contact_name = wa()->getUser()->getName();
        } else {
            foreach(blogHelper::getAuthors($blog_id) as $post_contact_id => $post_contact_name) {
                break;
            }
        }

        // Prepare empty fake post data
        $post_model = new blogPostModel();
        $post = $post_model->prepareView(array(array(
            'id' => 0,
            'blog_id' => $blog_id,
            'contact_id' => $post_contact_id,
            'contact_name' => $post_contact_name,
            'datetime' => date('Y-m-d H:i:s'),
            'title' => '%replace-with-real-post-title%',
            'status' => 'published',
            'text' => '<div class="replace-with-real-post-text"></div>'.$this->getScripts(),
            'comments_allowed' => 0,
        ) + $post_model->getEmptyRow()));
        $post = array_merge($post[0], array(
            'comments' => array(),
            'comment_link' => '',
            'link' => '',
        ));

        $this->getResponse()->setTitle(_w('Preview'));
        $this->getResponse()->setMeta('keywords', '');
        $this->getResponse()->setMeta('description', '');

        $current_auth = wa()->getStorage()->read('auth_user_data');
        $current_auth_source = $current_auth ? $current_auth['source'] : null;

        $this->view->assign(array(
            'realtime_preview' => true,
            'frontend_post' => array(),
            'errors' => array(),
            'form' => array(),
            'show_comments' => false,
            'request_captcha' => false,
            'require_authorization' => false,
            'theme' => waRequest::param('theme', 'default'),
            'current_auth_source' => $current_auth_source,
            'current_auth' => $current_auth, true,
            'auth_adapters' => wa()->getAuthAdapters(),
            'post' => $post,
        ));
    }

    public function display($clear_assign = false)
    {
        $result = parent::display($clear_assign);
        $result = str_replace('%replace-with-real-post-title%', '<span class="replace-with-real-post-title"></span>', $result);
        return $result;
    }

    public function getScripts()
    {
        $app_static_url = wa()->getAppStaticUrl('blog', 1);
        $version = wa()->getVersion('blog');
        return <<<EOF
            <script src="{$app_static_url}js/postmessage.js?{$version}"></script>
            <script>$(function() {

                // Make sure we're in an iframe
                var parent_origin = window.top !== window && document.referrer && (function(a) {
                    a.href = document.referrer;
                    return a.origin || a.protocol + '//' + (document.referrer.indexOf(a.hostname+':') >= 0 ? a.host : a.hostname);
                })(document.createElement('a'));
                if (!parent_origin) {
                    return;
                }

                // Add css class on <html> so that the theme can customize its looks
                $('html').addClass('realtime-preview');

                // Ignore clicks on links and everything
                $('*').off().click(function() {
                    return false;
                });

                // Update title when parent tells us to
                $.pm.bind('update_title', function(data) {
                    $('.replace-with-real-post-title:first').text(data);
                    sendHeight();
                }, parent_origin);

                // Update text when parent tells us to
                var old_text = null;
                $.pm.bind('update_text', function(data) {
                    if (old_text != data) {
                        old_text = data;
                        $('.replace-with-real-post-text:first').html(data);
                        sendHeight();
                    }
                }, parent_origin);

                // Helper to send content height to parent so it can adjust iframe height
                function sendHeight() {
                    $.pm({
                        target: window.top,
                        type: 'update_height',
                        data: $('body').outerHeight()
                    });
                }

            });</script>
EOF;
    }
}

