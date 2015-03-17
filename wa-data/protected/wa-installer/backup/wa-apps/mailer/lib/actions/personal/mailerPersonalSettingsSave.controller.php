<?php

class mailerPersonalSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $theme = new waTheme(waRequest::get('theme_id'), 'mailer');
        if ($theme['type'] == waTheme::ORIGINAL) {
            $theme->copy();
        }

        $files = waRequest::post('files');
        foreach ($files as $file => $content) {
            $file_path = $theme->getPath().'/'.$file;
            if (!file_exists($file_path) || is_writable($file_path)) {
                if ($content || file_exists($file_path)) {
                    $r = @file_put_contents($file_path, $content);
                    if ($r !== false) {
                        $r = true;
                    }
                } else {
                    $r = @touch($file_path);
                }
            }
        }
    }
}