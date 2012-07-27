<?php

class photosFrontendAction extends photosFrontendCollectionViewAction
{
    public function execute()
    {
        $this->init();

        $type = waRequest::param('type');
        $this->hash = waRequest::param('hash');

        if ($type == 'tag') {
            $this->view->assign('criteria', 'by-tag');
            $this->view->assign('tag', waRequest::param('tag'));
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
            $template = 'search.html';
        } else {
            $template = 'view-thumbs.html';
        }

        $layout = $this->getLayout();
        if ($layout) {
            $layout->assign('hash', $this->hash);
        }
        $this->setThemeTemplate($template);

        $this->finite();
    }
}