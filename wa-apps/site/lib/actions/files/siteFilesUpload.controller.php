<?php

class siteFilesUploadController extends waUploadJsonController
{   
    protected function getPath()
    {
        $path = rtrim(waRequest::post('path'), ' /');
        return wa()->getDataPath($path, true);
    }    
}