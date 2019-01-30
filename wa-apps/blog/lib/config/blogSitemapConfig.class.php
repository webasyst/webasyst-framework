<?php
class blogSitemapConfig extends waSitemapConfig
{
    public function execute()
    {
        $routes = $this->getRoutes();
        $app_id = wa()->getApp();

        $blog_model = new blogBlogModel();
        $post_model = new blogPostModel();
        $page_model = new blogPageModel();

        $blogs = $blog_model->getAvailable(false,array('id','name','url'));

        $real_domain = $this->routing->getDomain(null, true, false);

        foreach ($routes as $route) {
            $lastmod = null;
            $this->routing->setRoute($route);
            $default_blog_id = isset($route['blog_url_type']) ? (int)$route['blog_url_type'] : 0;
            $default_blog_id = max(0, $default_blog_id);
            $extend_options = array(
            	'datetime'=>true,
            );
            $extend_data = array(
            	'blog'=>$blogs,
            );
            foreach ($blogs as $blog_id => $blog) {
                if (!$default_blog_id || ($blog_id == $default_blog_id) ) {
                    $search_options = array('blog_id'=>$blog_id);
                    $posts = $post_model->search($search_options, $extend_options, $extend_data)->fetchSearchAll('id,title,url,datetime,blog_id');
                    foreach ($posts as $post) {
                        $post['blog_url'] = $blog['url'];
                        $post_lastmod = strtotime($post['datetime']);
                        $lastmod = max($lastmod, $post_lastmod);
                        if(!empty($post['comment_datetime'])) {
                            $post_lastmod = max($post_lastmod, strtotime($post['comment_datetime']));
                        }
                        $this->addUrl($post['link'], $post_lastmod);
                    }
                }
            }

            // pages
            $this->addPages($page_model,$route);

            $this->addUrl(wa()->getRouteUrl($app_id."/frontend", array(), true, $real_domain), $lastmod, self::CHANGE_DAILY, 1.0);
        }
    }
}