<?php

class blogPostGetPostUrlController extends waJsonController
{
    public function execute()
    {
        $post_title = waRequest::post('post_title', '', waRequest::TYPE_STRING_TRIM);
        $blog_id = waRequest::post('blog_id', 0, waRequest::TYPE_INT);
        $slug = waRequest::post('slug', '', waRequest::TYPE_STRING_TRIM);

        $blog_model = new blogBlogModel();
        $blog = $blog_model->getById($blog_id);

        if (!$blog) {
            throw new waException(_w("Can't find corresponding blog"));
        }

        $this->response['is_private_blog'] = $blog['status'] == blogBlogModel::STATUS_PRIVATE;

        $post_id = waRequest::post('post_id', 0, waRequest::TYPE_INT);

        $post_model = new blogPostModel();

        if ($post_id) {

            $post = $post_model->getById($post_id, array('text', 'text_before_cut'));
            if (!$post) {
                throw new waException(_w("Can't find corresponding post"));
            }

            if ($post['status'] != blogPostModel::STATUS_PUBLISHED) {
                $options = array(
                        'contact_id' => $post['contact_id'],
                        'blog_id' => $blog_id,
                        'post_id' => $post['id'],
                        'user_id' => wa()->getUser()->getId()
                );
                $this->response['preview_hash'] = blogPostModel::getPreviewHash($options);
                $this->response['preview_hash'] = base64_encode($this->response['preview_hash'].$options['user_id']);
            }

            $this->response['slug'] = $post['url'];
            $this->response['is_published'] = $post['status'] == blogPostModel::STATUS_PUBLISHED;
            $this->response['is_adding'] = false;

        } else {

            $post = array();

            $this->response['slug'] = $slug ? $slug : blogHelper::transliterate($post_title);
            $this->response['is_published'] = false;
            $this->response['is_adding'] = true;
        }

        $post['blog_id'] = $blog_id;
        $post['album_link_type'] = 'blog';
        $other_links = blogPostModel::getPureUrls($post);
        $this->response['link'] = array_shift($other_links);
        $this->response['other_preview_links'] = blogPost::getUrl($post, 'realtime_preview');
        $this->response['preview_link'] = array_shift($this->response['other_preview_links']);

        if (!$this->response['link']) {
            $this->response['is_private_blog'] = true;
        }
        $this->response['other_links'] = $other_links;

        foreach ($this->response as $k => &$item) {
            if (!$item || (!is_string($item) && !is_array($item))) {
                continue;
            }
            if (is_array($item)) {
                $item = array_map('htmlspecialchars', $item, array_fill(0, count($item), ENT_QUOTES));
                continue;
            }
            $item = htmlspecialchars($item, ENT_QUOTES);
        }
        unset($item);
        $this->getResponse()->addHeader('Content-type', 'application/json');
    }
}
//EOF