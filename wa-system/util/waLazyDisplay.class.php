<?php
class waLazyDisplay
{
    public $obj;
    public $app_id;
    public $force_ui_version;

    public function __construct($obj, $app_id = null)
    {
        if (!method_exists($obj, 'display')) {
            throw new waException('Must be a displayable object');
        }
        $this->obj = $obj;
        $this->app_id = ifempty($app_id, wa()->getApp());
        $this->force_ui_version = wa($this->app_id)->whichUI();
    }

    public function __toString()
    {
        $old_app = wa()->getApp();
        $is_template = waConfig::get('is_template');
        waConfig::set('is_template', null);

        $old_forced_ui_version = waRequest::param('force_ui_version', null, waRequest::TYPE_STRING_TRIM);
        waRequest::setParam('force_ui_version', $this->force_ui_version);

        try {
            wa($this->app_id, 1);
            $result = (string) $this->obj->display(false);
        } catch (Exception $e) {
            $result = (string) $e;
        }

        waRequest::setParam('force_ui_version', $old_forced_ui_version);
        waConfig::set('is_template', $is_template);
        wa($old_app, 1);
        return $result;
    }
}
