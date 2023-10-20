<?php

class photosFrontendAction extends photosFrontendCollectionViewAction
{
    public function execute()
    {
        $this->init();

        $type = waRequest::param('type');
        $this->hash = waRequest::param('hash');

        if ($type == 'tag') {
            $tag = waRequest::param('tag');
            $tag_model = new photosTagModel();
            $tag_id = $tag_model->getByName($tag, true);
            if (!$tag_id) {
                throw new waException(_w('Tag not found'), 404);
            }
            $this->view->assign('criteria', 'by-tag');
            $this->view->assign('tag', $tag);
        } else if ($type == 'favorites') {
            $this->view->assign('criteria', 'favorites');
        }

        if (in_array($type, array(
            'author',
            'search',
            'tag',
            'favorites',
            'id')))
        {
            waRequest::setParam('disable_sidebar', true);
            $template = 'search.html';
        } else {
            $template = 'home.html';
            if (!file_exists($this->getTheme()->getPath().'/'.$template)) {
                $template = 'view-thumbs.html'; // for backward compatibility reason
            }
        }

        if ($type != 'all' && $type != 'favorites') {
            waRequest::setParam('nofollow', true);
        }

        $layout = $this->getLayout();
        if ($layout) {
            $layout->assign('hash', $this->hash);
        }
        $this->setThemeTemplate($template);

        $this->finite();
    }

    public function display($clear_assign = false)
    {
        return parent::display($clear_assign);
    }
}