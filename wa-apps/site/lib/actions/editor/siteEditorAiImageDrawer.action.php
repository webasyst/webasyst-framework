<?php

class siteEditorAiImageDrawerAction extends waViewAction
{
    public function execute()
    {
        $supported_aspect_ratios = siteAiImageOptions::getSupportedAspectRatios();
        $this->view->assign([
            'mode' => 'create',
            'supported_aspect_ratios' => $supported_aspect_ratios,
            'default_aspect_ratio' => $supported_aspect_ratios[0],
            'parameter_groups' => siteAiImageOptions::getParameterGroups(),
        ]);
    }
}
