<?php

class blogMypostsPlugin extends blogPlugin
{
    public function backendSidebar($params)
    {
        $output = array();
        $post_model = new blogPostModel();
        $blogs = blogHelper::getAvailable(false);
        $search_options = array(
        	'contact_id' => wa()->getUser()->getId(),
        	'status'     => blogPostModel::STATUS_PUBLISHED,
        	'blog_id'    => array_keys($blogs),
        );
        $count = $post_model->countByField($search_options);

        $img_url = wa()->getUser()->getPhoto(20);

        $output['menu'] = $this->renderMiscTemplate('MenuItem.html', [
            'is_selected' => waRequest::get('search') == $this->id,
            'count' => $count,
            'img_url' => $img_url,
            'plugin_id' => $this->id
        ]);

        return $output;
    }

    public function postSearch($options)
    {
        $result = null;
        if (is_array($options) && isset($options['plugin'])) {
            if (isset($options['plugin'][$this->id])) {
                $result = array();
                $result['where'][] = 'contact_id = '.wa()->getUser()->getId();
                $response = wa()->getResponse();
                $title = $response->getTitle();
                $title = _wp('My posts');
                $response->setTitle($title);
            }
        }
        return $result;
    }

    protected function renderMiscTemplate($template, $assign = [])
    {
        return $this->renderTemplate('misc', $template, $assign, true);
    }
}
