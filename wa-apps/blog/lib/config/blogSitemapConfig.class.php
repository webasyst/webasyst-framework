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
            $main_url = wa()->getRouteUrl($app_id."/frontend", array(), true);
            $domain = $this->routing->getDomain(null, true);
            $sql = "SELECT full_url, url, create_datetime, update_datetime FROM ".$page_model->getTableName().'
                    WHERE status = 1 AND domain = s:domain AND route = s:route';
            $pages = $page_model->query($sql, array('domain' => $domain, 'route' => $route['url']))->fetchAll();
            foreach ($pages as $p) {
                $this->addUrl($main_url.$p['full_url'], $p['update_datetime'] ? $p['update_datetime'] : $p['create_datetime'], self::CHANGE_MONTHLY, 0.6);
            }


            $this->addUrl(wa()->getRouteUrl($app_id."/frontend", array(), true), $lastmod, self::CHANGE_DAILY, 1.0);
        }
    }
}