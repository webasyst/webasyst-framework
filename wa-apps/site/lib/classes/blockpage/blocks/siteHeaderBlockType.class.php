<?php
/**
 * Site header (topmost menu)
 */
class siteHeaderBlockType extends siteBlockType
{
    protected function getDefaultRenderTemplatePath(bool $is_backend)
    {
        //if ($is_backend) {
            return parent::getDefaultRenderTemplatePath($is_backend);
        //}
        $filename = dirname(__FILE__).'/templates/Header.front.html';
        return $filename;
    }
}