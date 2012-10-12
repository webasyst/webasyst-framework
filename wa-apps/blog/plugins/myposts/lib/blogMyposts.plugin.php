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

        $selected = '';
        if ( waRequest::get('search') == $this->id ) {
            $selected = ' class="selected"';
        }
        $img_url = wa()->getUser()->getPhoto(20);
        $title = _wp('Posts by me');
        $output['menu'] = <<<HTML
<li{$selected}>
	<span class="count my_count">{$count}</span>
	<a href="?search={$this->id}">
		<i class="icon16 userpic20" style="background-image: url('{$img_url}');"></i>{$title}
	</a>
</li>
HTML;
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
}