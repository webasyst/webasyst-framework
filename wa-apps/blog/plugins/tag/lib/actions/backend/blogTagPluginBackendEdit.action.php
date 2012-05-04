<?php

class blogTagPluginBackendEditAction extends waViewAction
{
    public function execute()
    {
        wa()->getResponse()
            //->addJs('wa-content/js/jquery-plugins/jquery-tagsinput/jquery.tagsinput.js')
            ->addJs('wa-content/js/jquery-plugins/jquery-tagsinput/jquery.tagsinput.min.js')
            ->addCss('wa-content/js/jquery-plugins/jquery-tagsinput/jquery.tagsinput.css')
            ->addJs('wa-content/js/jquery-plugins/jquery-autocomplete/jquery.autocomplete.min.js')
            ->addCss('wa-content/js/jquery-plugins/jquery-autocomplete/jquery.autocomplete.css')
        ;

        $tags = array();
        $tag_model = new blogTagPluginModel();
        $tag_records = $tag_model->getByPost($this->params['post_id']);

        foreach ($tag_records as $tag_info) {
            $tags[] = $tag_info['name'];
        }

        $this->view->assign('tags', implode(', ', $tags));
    }
}