<?php
/**
 * Block containing HTML content.
 * prop 'preview_html' for backend
 * prop 'html' for frontend
 */
class siteAppsHtmlBlockType extends siteBlockType
{
    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars=[])
    {
        if ($is_backend) {
            return ifset($data->data, 'preview_html', '');
        } else {
            return ifset($data->data, 'html', '');
        }
    }

    protected function getDefaultRenderTemplatePath(bool $is_backend)
    {
        return false;
    }

    public function fillBlockData($data)
    {
        $result = $this->getEmptyBlockData();
        $result->data = $data;
        return $result;
    }
}
