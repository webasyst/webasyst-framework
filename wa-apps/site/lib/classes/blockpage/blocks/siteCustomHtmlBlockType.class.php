<?php
/**
 * Block containing custom HTML content.
 */
class siteCustomHtmlBlockType extends siteBlockType
{
    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars=[])
    {
        if ($is_backend) {
            return '<pre data-block-id="'.($data->getId()).'"data-page-id="'.($data->getPageId()).'" contenteditable="true">'.htmlspecialchars(ifset($data->data, 'html', '')).'</pre>';
        } else {
            return ifset($data->data, 'html', '');
        }
    }

    protected function getDefaultRenderTemplatePath(bool $is_backend)
    {
        return false;
    }

    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $result->data = ['html' => 'Custom <strong>HTML</strong> <u>content</u>.'];
        return $result;
    }
}
