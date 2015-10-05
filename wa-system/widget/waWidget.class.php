<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage controller
 */
class waWidget extends waActions
{
    protected $id;
    protected $info;
    protected $widget;
    protected $app_id;
    protected $path;
    protected $settings_config;
    protected $settings;

    private static $settings_model;

    public function __construct($info)
    {
        $this->info = $info;
        $this->id = $this->info['id'];
        $this->widget = $this->info['widget'];
        $this->app_id = $this->info['app_id'];
        $this->path = wa($this->app_id)->getConfig()->getWidgetPath($this->widget);
    }

    public function isAllowed()
    {
        if ($this->getInfo('dashboard_id')) {
            return wa()->getUser()->isAdmin('webasyst');
        } else {
            return $this->getInfo('contact_id') == wa()->getUser()->getId()
                    && self::checkRights($this->getInfo('rights', array()));
        }
    }

    public static function checkRights($rights)
    {
        foreach ($rights as $r_app_id => $r_app_rights) {
            foreach ($r_app_rights as $r_name => $r_value) {
                $u_value = wa()->getUser()->getRights($r_app_id, $r_name);
                if ($r_value === true) {
                    if (!$u_value) {
                        return false;
                    }
                } else {
                    if ($u_value < $r_value) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function getInfo($name = null, $default = null)
    {
        return $name ? ifset($this->info[$name], $default) : $this->info;
    }

    public function loadLocale($set_current = false)
    {
        $locale_path = $this->path.'/locale';
        $domain = ($this->app_id == 'webasyst' ? '' : $this->app_id.'_').'widget_'.$this->widget;

        if (file_exists($locale_path)) {
            waLocale::load(wa()->getLocale(), $locale_path, $domain, $set_current);
        }
    }

    /**
     * @param array $params Control items params (see waHtmlControl::getControl for details)
     * @return string[string] Html code of control
     */
    public function getControls($params = array())
    {
        $controls = array();
        $settings_config = $this->getSettingsConfig();
        foreach ($settings_config as $name => $row) {
            if (!is_array($row)) {
                continue;
            }
            if (!empty($params['subject']) && !empty($row['subject']) && !in_array($row['subject'], (array)$params['subject'])) {
                continue;
            }
            $row = array_merge($row, $params);
            $row['value'] = $this->getSettings($name);
            if (!empty($row['control_type'])) {
                $controls[$name] = waHtmlControl::getControl($row['control_type'], $name, $row);
            }
        }
        return $controls;
    }

    /**
     * Get raw settings config
     * @return array
     */
    protected function getSettingsConfig()
    {
        if (is_null($this->settings_config)) {
            $path = $this->path.'/lib/config/settings.php';
            if (file_exists($path)) {
                $settings_config = include($path);
                if (!is_array($settings_config)) {
                    $settings_config = array();
                }
            } else {
                $settings_config = array();
            }
            $this->settings_config = $settings_config;
        }
        return $this->settings_config;
    }

    /**
     * @param null $name
     * @return array|mixed|null|string
     */
    public function getSettings($name = null, $default = null)
    {
        if ($this->settings === null) {
            $this->settings = self::getSettingsModel()->get($this->id);
            foreach ($this->settings as $key => $value) {
                #decode non string values
                $json = json_decode($value, true);
                if (is_array($json)) {
                    $this->settings[$key] = $json;
                }
            }
            #merge user settings from database with raw default settings
            if ($settings_config = $this->getSettingsConfig()) {
                foreach ($settings_config as $key => $row) {
                    if (!isset($this->settings[$key])) {
                        $this->settings[$key] = is_array($row) ? (isset($row['value']) ? $row['value'] : null) : $row;
                    }
                }
            }
        }
        if ($name === null) {
            return $this->settings;
        } else {
            return isset($this->settings[$name]) ? $this->settings[$name] : $default;
        }
    }

    /**
     * @param mixed [string] $settings Array of settings key=>value
     */
    public function setSettings($settings = array())
    {
        $settings_config = $this->getSettingsConfig();
        foreach ($settings_config as $name => $row) {
            if (!isset($settings[$name])) {
                if ((ifset($row['control_type']) == waHtmlControl::CHECKBOX) && !empty($row['value'])) {
                    $settings[$name] = false;
                } elseif ((ifset($row['control_type']) == waHtmlControl::GROUPBOX) && !empty($row['value'])) {
                    $settings[$name] = array();
                } elseif (!empty($row['control_type']) || isset($row['value'])) {
                    $this->settings[$name] = isset($row['value']) ? $row['value'] : null;
                    self::getSettingsModel()->del($this->id, $name);
                }
            }
        }
        foreach ($settings as $name => $value) {
            $this->settings[$name] = $value;
            // save to db
            self::getSettingsModel()->set($this->id, $name, is_array($value) ? json_encode($value) : $value);
        }
    }

    private static function getSettingsModel()
    {
        if (!self::$settings_model) {
            self::$settings_model = new waWidgetSettingsModel();
        }
        return self::$settings_model;
    }

    public function getStaticUrl($absolute = false)
    {
        if ($this->app_id == 'webasyst') {
            return wa()->getRootUrl().'wa-widgets/'.$this->widget.'/';
        } else {
            return wa()->getAppStaticUrl($this->app_id, $absolute) . 'widgets/' . $this->widget . '/';
        }
    }

    protected function getTemplatePath($template)
    {
        return $this->getPluginRoot().'templates/'.$template;
    }

    protected function getTemplate()
    {
        $template = ucfirst($this->action);

        if (strpbrk($template, '/:') === false) {
            $match = array();
            preg_match("/[A-Z][^A-Z]+/", get_class($this), $match);
            $template = $this->getPluginRoot().'templates/'.$template.$this->getView()->getPostfix();
        }

        return $template;
    }

    public function getPluginRoot()
    {
        if ($this->app_id == 'webasyst') {
            return $this->path.parent::getPluginRoot().'/';
        } else {
            return parent::getPluginRoot();
        }
    }
}