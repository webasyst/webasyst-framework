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

        $this->setLayout(new blogPreviewLayout());

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

        //because historically all themes design waiting escaped user name
        $post["user"]["name"] = htmlspecialchars($post["user"]["name"]);

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

    public function getScripts()
    {
        $parent_url = json_encode(waRequest::get('parent_url', '', 'string'));
        $app_static_url = wa()->getAppStaticUrl('blog', 1);
        $version = wa()->getVersion('blog');
        $ui = wa()->whichUI('blog');

        return <<<EOF
            <script src="{$app_static_url}js{if $ui == '1.3'}-legacy{/if}/postmessage.js?{$version}"></script>
            <script>$(function() {
                // Make sure we're in an iframe
                if (window.top === window) {
                    return;
                }

                // Figure out parent window origin. It can be tricky because of same origin policy.
                var parent_url = {$parent_url} || document.referrer;
                try {
                    parent_url = parent_url || window.parent.location.href;
                } catch(e) {
                    console.log(e);
                }
                var parent_origin = parent_url && (function(a) {
                    a.href = parent_url;
                    return a.origin || a.protocol + '//' + (parent_url.indexOf(a.hostname+':') >= 0 ? a.host : a.hostname);
                })(document.createElement('a'));
                if (!parent_origin) {
                    console.log('Unable to initialize real-time preview: no parent URL.');
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

                // Tell parent window we're ready to accept data
                $.pm({
                    target: window.top,
                    type: 'updater_loaded',
                    data: true
                });

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

