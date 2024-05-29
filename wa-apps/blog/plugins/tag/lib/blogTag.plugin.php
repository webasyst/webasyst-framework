<?php

class blogTagPlugin extends blogPlugin
{


    public function postSearch($options)
    {
        $result = null;
        if (is_array($options) && isset($options['plugin'])) {
            if (isset($options['plugin'][$this->id])) {
                $tag = preg_split('/,\s*/',$options['plugin'][$this->id]);
                $tag = array_unique(array_map('trim',$tag));
                $tag = array_filter($tag,'strlen');

                if ($tag) {

                    $result = array();
                    $result['join'] = array();
                    $tag_model = new blogTagPluginModel();
                    if ($tags = $tag_model->getByField('name',$tag,'id')) {

                        $result['join']['blog_post_tag'] = array(
                            'condition'=>'blog_post_tag.post_id = blog_post.id',
                        );
                        $result['where'] = array('blog_post_tag.tag_id IN ('.implode(', ',array_keys($tags)).')');
                    } else {
                        $tags = array();
                        foreach($tag as $tag_name) {
                            $tags[] = array('name'=>$tag_name);
                        }
                        $result['where'] = 'FALSE';
                    }



                    $tag = array();
                    foreach ($tags as $tag_data) {
                        $tag[] = sprintf(_wp("Tagged “%s”"),$tag_data['name']);
                    }
                    $response = wa()->getResponse();
                    $response->setTitle(implode(', ',$tag));
                }
            }
        }
        return $result;
    }


    public function backendSidebar($params)
    {
        $output = array();
        $action = new blogTagPluginBackendAction();
        $output['section'] = $action->display();
        return $output;
    }

    public function frontendSidebar($params)
    {
        $output = array();

        $options = include($this->path.'/lib/config/config.php');
        if (!empty($options['compare_blog'])) {
            $default_blog_id = ifset($params, 'blog_url_type', null);
            if ($default_blog_id !== null) {
                $default_blog_id = (int)$default_blog_id;
                if ($default_blog_id < 1) {
                    $blog_id = ifset($params, 'blog_id', null);
                    if ($blog_id && isset($params['blog_url']) && mb_strlen($params['blog_url'])) {
                        $options['blog_id'] = $blog_id;
                    }
                } else {
                    $options['blog_id'] = $default_blog_id;
                }
            }
        }

        $tag_model = new blogTagPluginModel();
        if ($tags = $tag_model->getAllTags($options)) {
            $output['sidebar'] = '<div class="tags cloud">';
            $wa = wa();
            foreach ($tags as $tag) {
                $tag['link'] = $wa->getRouteUrl('blog/frontend', array('tag'=>urlencode($tag['name'])), true);
                $tag['name'] = htmlentities($tag['name'],ENT_QUOTES,'utf-8');
                $output['sidebar'] .= <<<HTML

<a href="{$tag['link']}" style="font-size: {$tag['size']}%; opacity: {$tag['opacity']};">{$tag['name']}</a>
HTML;
            }
            $output['sidebar'] .= '</div>';
        }
        return ifempty($output, null);
    }


    private function getTagByPost($posts)
    {
        $post_tag_model = new blogTagPostTagPluginModel();
        $post_tags_raw = $post_tag_model->getByField('post_id',$posts,true);

        $tags = array();
        $post_tags = array();
        foreach ($post_tags_raw as $post_tag) {
            $tags[] = $post_tag['tag_id'];
            $id = $post_tag['post_id'];
            if(!isset($post_tags[$id])) {
                $post_tags[$id] = array();
            }
            $post_tags[$id][] = $post_tag['tag_id'];
        }
        return array(array_unique($tags),$post_tags);
    }


    public function prepareBackendView(&$posts)
    {
        $url = wa()->getConfig()->getBackendUrl(true);
        if ($posts) {
            list($tags, $post_tags) = $this->getTagByPost(array_keys($posts));
            if($tags) {

                $tag_model = new blogTagPluginModel();
                $tags_data = $tag_model->getByField('id',$tags,'id');
                foreach ($tags_data as &$tag) {
                    $tag['link'] = $url.'blog/?search=tag&amp;tag='.urlencode($tag['name']);
                }
                unset($tag);

                foreach ($post_tags as $id => $post_item_tags) {
                    $html = $this->renderMiscTemplate('PostTags.html', [
                        'tags_data' => $tags_data,
                        'post_item_tags' => $post_item_tags,
                    ]);
                    $posts[$id]['plugins']['after'][$this->id] = $html;
                }
            }
        }
    }


    public function prepareFrontendView(&$posts)
    {
        if ($posts) {
            list($tags,$post_tags) = $this->getTagByPost(array_keys($posts));
            if($tags) {

                $tag_model = new blogTagPluginModel();
                $tags_data = $tag_model->getByField('id',$tags,'id');
                $wa = wa();
                foreach ($tags_data as &$tag) {
                    $tag['link'] = $wa->getRouteUrl('blog/frontend', array('tag'=>urlencode($tag['name'])), true);
                    //$tag['name'] = htmlspecialchars($tag['name'],ENT_QUOTES,'utf-8');
                }
                unset($tag);

                foreach ($post_tags as $id => $post_item_tags) {
                    $html = $this->renderMiscTemplate('PostTags.html', [
                        'tags_data' => $tags_data,
                        'post_item_tags' => $post_item_tags,
                    ]);

                    $posts[$id]['plugins']['after'][$this->id] = $html;
                }
            }
        }
    }


    public function backendPostEdit($post)
    {
        $output = array();
        $action = new blogTagPluginBackendEditAction(array(
            'post_id' => $post['id']
        ));
        $output['sidebar'] = $action->display(false);
        return $output;
    }


    public function postSave($post)
    {
        $post_id = $post['id'];
        if (isset($post['plugin']) && isset($post['plugin'][$this->id])) {
            if ($post['plugin'][$this->id]) {
                $tags = preg_split('/(,[\s]*)+/', $post['plugin'][$this->id]);
            } else {
                $tags = array();
            }
            $tag_model = new blogTagPluginModel();
            $tag_model->addTag($post_id, $tags);
        }
    }

    public function postDelete($post_ids)
    {
        $post_tag_model = new blogTagPostTagPluginModel();
        $post_tag_model->deletePost($post_ids);
    }

    protected function renderMiscTemplate($template, $assign = [])
    {
        return $this->renderTemplate('misc', $template, $assign, true);
    }
}
