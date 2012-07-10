<?php
/**
 * @version SVN: $Id$
 */
class waAppSettingsModel extends waModel
{
    protected static $cache = array();
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
        if (!isset(self::$cache[$app_id])) {
            $sql = "SELECT app_id, name, value
                    FROM ".$this->table."
                    WHERE app_id = '".$this->escape($app_id)."' OR app_id LIKE '".$this->escape($app_id).".%'";
            $this->setCache($this->getCache($app_id));
            //self::$cache[$app_id] =
            $data = $this->query($sql)->fetchAll();
            self::$cache[$app_id] = array();
            foreach ($data as $row) {
                self::$cache[$row['app_id']][$row['name']] = $row['value'];
            }
        }

        if (is_null($name)) {
            return  isset(self::$cache[$key]) ? self::$cache[$key] : array();
        }
        else {
            return isset(self::$cache[$key][$name]) ? self::$cache[$key][$name] : $default;
        }
    }

    protected function getCache($app_id)
    {
        // cache one day
        return new waSerializeCache('app_settings/'.$app_id, SystemConfig::isDebug() ? 600 : 86400, 'webasyst');
    }

    public function set($app_id, $name, $value)
    {
        $key = $this->getCacheKey($app_id);
        $this->addCacheCleaner($this->getCache($app_id));
        if ($this->getByField(array('app_id' => $key, 'name' => $name))) {
            $this->updateByField(array('app_id' => $key, 'name' => $name), array('value' => $value));
        } else {
            $this->insert(array('app_id' => $key, 'name' => $name, 'value' => $value));
        }
        self::$cache[$key][$name] = $value;
        return true;
    }

    public function del($app_id, $name = null)
    {
        $key = $this->getCacheKey($app_id);
        $this->addCacheCleaner($this->getCache($app_id));
        $params = array('app_id' => $key);
        if ($name === null) {
            if (isset(self::$cache[$key])) {
                unset(self::$cache[$key]);
            }
        } else {
            if (isset(self::$cache[$key][$name])) {
                unset(self::$cache[$key][$name]);
            }
            $params['name'] = $name;
        }
        return $this->deleteByField($params);
    }
}