<?php
/**
 * This replaces another block type during render if an error occurs.
 * It shows details of the error.
 */
class siteBrokenBlockType extends siteBlockType
{
    public $error_description;
    public function __construct($type, $error_text=null)
    {
        $this->error_description = $error_text;
    }

    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars=[])
    {
        return "<pre>{$this->error_description}</pre>";
    }

    protected function getDefaultRenderTemplatePath(bool $is_backend)
    {
        return false;
    }
}
