<?php

class blogFavoritePlugin extends blogPlugin
{
    public function backendSidebar($param)
    {
        $output = array();
        $favorite_model = new blogFavoritePluginModel();
        $count = $favorite_model->countByField('contact_id',wa()->getUser()->getId());

        $output['menu'] = $this->renderMiscTemplate('SidebarItem.html', [
            'plugin_id' => $this->id,
            'count' => $count,
            'is_selected' => waRequest::get('search') == $this->id
        ]);

        return $output;
    }

    public function postDelete($post_id)
    {
        $favorite_model = new blogFavoritePluginModel();
        $favorite_model->deleteByField('post_id', $post_id);
    }

    public function postsPrepareView(&$posts)
    {
        if ($contact_id = wa()->getUser()->getId()) {
            $this->addJs('js/favorites-plugin.js', true);

            $favorite_model = new blogFavoritePluginModel();
            $favorite = $favorite_model->getByField(array('contact_id'=>$contact_id,'post_id'=>array_keys($posts)),'post_id');

            foreach ($posts as  $id => &$post) {
                $post['plugins']['post_title'][$this->id] = $this->renderMiscTemplate('PostStar.html', [
                    'is_marked' => isset($favorite[$id])
                ]);
                unset($post);
            }
        }
    }

    public function postSearch($options)
    {
        $result = null;
        if (is_array($options) && isset($options['plugin'])) {
            if (isset($options['plugin'][$this->id])) {
                $result = array();
                $result['join'] = array();
                $result['join']['blog_favorite'] = array(
                	'condition'=>'blog_favorite.post_id = blog_post.id AND blog_favorite.contact_id='.wa()->getUser()->getId(),
                    'values'=>array('favorite'=>1),
                );
                $response = wa()->getResponse();
                $title = _wp('Favorites');
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
