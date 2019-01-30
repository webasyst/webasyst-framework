<?php
class waLazyDisplay
{
    public $obj;
    public $app_id;

    public function __construct($obj, $app_id = null)
    {
        if (!method_exists($obj, 'display')) {
            throw new waException('Must be a displayable object');
        }
        $this->obj = $obj;
        $this->app_id = ifempty($app_id, wa()->getApp());
    }

    public function __toString()
    {
        $old_app = wa()->getApp();
        $is_template = waConfig::get('is_template');
        waConfig::set('is_template', null);
        try {
            wa($this->app_id, 1);
            $result = (string) $this->obj->display(false);
        } catch (Exception $e) {
            $result = (string) $e;
        }
        waConfig::set('is_template', $is_template);
        wa($old_app, 1);
        return $result;
    }
}
