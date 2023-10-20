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
 * @subpackage webasyst
 */

class waAppSettingsModel extends waModel
{
    protected static $settings = array();
    protected $table = 'wa_app_settings';


    private function getCacheKey(&$app_id)
    {
        if (is_array($app_id)) {
            $key = $app_id[0].".".$app_id[1];
            $app_id = $app_id[0];
            return $key;
        } elseif (strpos($app_id, '.') !== false) {
            $key = $app_id;
            $app_id = substr($key, 0, strpos($key, '.'));
            return $key;
        } else {
            return $app_id;
        }
    }

    public function get($app_id, $name = null, $default = '')
    {
        $key = $this->getCacheKey($app_id);
        if (!isset(self::$settings[$app_id])) {
            $cache = $this->getCache($app_id);
            $data = $cache->get();
            if ($data === null || !is_array($data)) {
                $sql = "SELECT app_id, name, value
                        FROM ".$this->table."
                        WHERE app_id = '".$this->escape($app_id)."' OR app_id LIKE '".$this->escape($app_id).".%'";
                $data = $this->query($sql)->fetchAll();
                $cache->set($data);
            }
            self::$settings[$app_id] = array();
            foreach ($data as $row) {
                self::$settings[$row['app_id']][$row['name']] = $row['value'];
            }
        }

        if (is_null($name)) {
            return isset(self::$settings[$key]) ? self::$settings[$key] : array();
        } else {
            return isset(self::$settings[$key][$name]) ? self::$settings[$key][$name] : $default;
        }
    }

    protected function getCache($app_id)
    {
        if (is_array($app_id)) {
            $app_id = reset($app_id);
        }
        // cache one day
        return new waVarExportCache('app_settings/'.$app_id, SystemConfig::isDebug() ? 600 : 86400, 'webasyst');
    }

    public function set($app_id, $name, $value)
    {
        $key = $this->getCacheKey($app_id);
        $this->multipleInsert([
            'app_id' => $key,
            'name' => $name,
            'value' => $value,
        ], ['value']);

        $this->clearCache($app_id, true);

        // if settings loaded
        if (isset(self::$settings[$app_id])) {
            self::$settings[$key][$name] = $value;
        }
        return true;
    }

    public function del($app_id, $name = null)
    {
        $key = $this->getCacheKey($app_id);
        $this->clearCache($app_id, true);
        $params = array('app_id' => $key);
        if ($name === null) {
            if (isset(self::$settings[$key])) {
                unset(self::$settings[$key]);
            }
        } else {
            if (isset(self::$settings[$key][$name])) {
                unset(self::$settings[$key][$name]);
            }
            $params['name'] = $name;
        }
        return $this->deleteByField($params);
    }

    public function clearCache($app_id, $only_file = false)
    {
        $this->getCache($app_id)->delete();
        if (!$only_file) {
            self::$settings = array();
        }
    }

    public function describe($table = null, $keys = false)
    {
        $disable_exception_log = waConfig::get('disable_exception_log');
        waConfig::set('disable_exception_log', true);
        try {
            return parent::describe($table, $keys);
        } catch (waDbException $e) {
            if ($e->getCode() == 1146) {
                // Framework not installed?.. Initialize app to create all tables.
                wa('webasyst');
                waConfig::set('disable_exception_log', $disable_exception_log);
                return parent::describe($table, $keys);
            }
            throw $e;
        } finally {
            waConfig::set('disable_exception_log', $disable_exception_log);
        }
    }
}