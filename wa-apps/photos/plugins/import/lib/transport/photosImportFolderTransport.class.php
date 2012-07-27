<?php

class photosImportFolderTransport extends photosImportTransport
{
    public function initOptions()
    {
        $this->options['path'] = array(
            'title' => _wp('Path to folder'),
            'value'=> wa()->getConfig()->getRootPath(),
            'settings_html_function'=>waHtmlControl::INPUT,
        );
    }
}