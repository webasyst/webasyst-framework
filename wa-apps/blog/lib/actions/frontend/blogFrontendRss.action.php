<?php

class blogFrontendRssAction extends blogViewAction
{
    public function execute()
    {
        $rss_author_tag = null;
        if ($blog_id = $this->getRequest()->param('blog_id')) {
            $rss_posts_number = max(1,$this->appSettings('rss_posts_number',10));
            $rss_author_tag = $this->appSettings('rss_author_tag');

            $options = array();
            $data = array();
            switch($rss_author_tag) {
                case 'blog':{
                    $blog_model = new blogBlogModel();
                    $data['blog'] = $blog_model->getByField(array('id'=>$blog_id),'id');
                    break;
                }
                default: {
                    $data['blog'] = blogHelper::getAvailable();
                    break;
                }
            }
            $options['params'] = true;

            $post_model = new blogPostModel();
            $posts = $post_model
            ->search(array('blog_id' => $blog_id), $options,$data)
            ->fetchSearchPage(1, $rss_posts_number);
        } else {
            $posts = array();
        }

        $link = wa()->getRouteUrl('blog/frontend', array(), true);

        $this->view->assign('info',array(
				'title' => wa()->accountName(),
				'link' => $link,
				'description' =>'',
				'language' => 'ru',
				'pubDate' => date(DATE_RSS),
				'lastBuildDate' => date(DATE_RSS),
				'self' => $link,
        ));
        $this->view->assign('blog_name',$this->getResponse()->getTitle());
        $this->view->assign('rss_author_tag',$rss_author_tag);

        if ($this->getConfig()->getOption('can_use_smarty')) {
            foreach ($posts as &$post) {
                try {
                    $post['text'] = $this->view->fetch("string:{$post['text']}",$this->cache_id);
                } catch (SmartyException $ex) {
                    $post['text'] = blogPost::handleTemplateException($ex, $post);
                }
            }
            unset($post);
        }
        $this->view->assign('posts', $posts);
        $this->getResponse()->addHeader('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}