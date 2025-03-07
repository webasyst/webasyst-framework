<?php
class siteEditorView extends waSmarty3View
{
    public function getHelper()
    {
        if (!isset($this->helper)) {
            $this->helper = new waViewHelper($this, [
                'is_frontend' => true,
            ]);
        }
        return $this->helper;
    }
}