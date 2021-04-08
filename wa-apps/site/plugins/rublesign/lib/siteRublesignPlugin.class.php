<?php

class siteRublesignPlugin extends sitePlugin
{
    const FILE_PATH = 'wa-content/font/ruble/arial/fontface.css';

    public function frontendHead()
    {
        if ($this->isEnabled()) {
            wa()->getResponse()->addCss(self::FILE_PATH);
        }
    }

    public function frontendPage($params)
    {
        if ($this->isEnabled()) {
            $file_link = $this->getFileLink();
            if (!empty($params['page']['content'])) {
               $params['page']['content'] .= '<link href="' . $file_link . '" rel="stylesheet" type="text/css">';
            }
        }
    }

    public function backendHeader()
    {
        if ($this->isEnabled()) {
            $file_link = $this->getFileLink();
            return '<link href="' . $file_link . '" rel="stylesheet" type="text/css">';
        }
    }

    protected function getFileLink()
    {
        return wa()->getRootUrl() . self::FILE_PATH;
    }

    protected function isEnabled()
    {
        $app_settings = new waAppSettingsModel();
        return $app_settings->get('site.rublesign', 'status', 0);
    }
}
