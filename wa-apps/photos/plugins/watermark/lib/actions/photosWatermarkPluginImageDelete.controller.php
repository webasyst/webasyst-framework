<?php

class photosWatermarkPluginImageDeleteController extends waJsonController
{
    public function execute()
    {
        $plugin = wa()->getPlugin('watermark');
        $plugin->saveSettings(array(
            'delete_image' => 1
        ));
    }
}