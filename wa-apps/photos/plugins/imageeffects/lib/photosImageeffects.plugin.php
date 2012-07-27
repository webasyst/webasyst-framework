<?php

class photosImageeffectsPlugin extends photosPlugin
{
    public function photoToolbar()
    {
        $view = wa()->getView();
        $static_url = $this->getPluginStaticUrl();
        $view->assign('static_url', $static_url);
        return array(
            'edit_menu' => $view->fetch($this->path.'/templates/PhotoToolbar.html')
        );
    }
}