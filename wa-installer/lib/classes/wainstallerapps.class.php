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
 * @package wa-installer
 */


class waInstallerApps
{
    private $installed_apps = array();
    private $installed_extras = array();
    private $sources;
    private $app_list;
    private static $root_path;
    private static $locale;
    private static $cache_ttl;
    private $license;
    private $identity_hash;
    private static $force;

    private $extras_list = array();

    const CONFIG_GENERIC		 = 'wa-config/config.php';
    const CONFIG_DB				 = 'wa-config/db.php';
    const CONFIG_APPS			 = 'wa-config/apps.php';
    const CONFIG_APP_PLUGINS	 = 'wa-config/apps/%s/plugins.php';
    const CONFIG_ROUTING		 = 'wa-config/routing.php';
    const CONFIG_SOURCES		 = 'wa-installer/lib/config/sources.php';

    const ITEM_CONFIG		 = 'wa-apps/%s/lib/config/app.php';
    const ITEM_REQUIREMENTS	 = 'wa-apps/%s/lib/config/requirements.php';
    const ITEM_BUILD		 = 'wa-apps/%s/lib/config/build.php';
    const ITEM_EXTRAS		 = 'wa-apps/%s/%s/%s/%s%s.php';
    const ITEM_EXTRAS_PATH	 = 'wa-apps/%s/%s/';
    const ITEM_ICON			 = 'img/%s.png';

    const VENDOR_SELF		 = 'webasyst';
    const VENDOR_UNKNOWN	 = 'local';

    const LIST_APPS			 = 'apps';
    const LIST_SYSTEM		 = 'system';

    const ACTION_UPDATE				 = 'update';
    const ACTION_CRITICAL_UPDATE	 = 'critical';
    const ACTION_REPAIR				 = 'repair';
    const ACTION_INSTALL			 = 'install';
    const ACTION_NONE				 = 'none';

    private static function init()
    {
        mb_internal_encoding('UTF-8');
        @ini_set("magic_quotes_runtime",0);
        if(version_compare('5.4', PHP_VERSION, '>') && function_exists('set_magic_quotes_runtime') && get_magic_quotes_runtime()){
            @set_magic_quotes_runtime(false);
        }
        @ini_set('register_globals', 'off');
        if(!isset(self::$root_path)){
            self::$root_path =  preg_replace('@([/\\\\]+)@','/',dirname(__FILE__).'/');
            //XXX fix root path definition
            self::$root_path = preg_replace('@(/)wa-installer/lib/classes/?$@','$1',self::$root_path);
        }

    }

    public static function setLocale($locale = null)
    {
        if(!is_null($locale)&&$locale) {
            self::$locale = $locale;
        }
        self::init();
    }

    private static function getCacheValue($key, $default = null, $path = null)
    {
        //TODO use $path as max actual time
        $key .= '.'.self::$locale;
        if($path) {
            $key .= '.'.$path;
        }
        $key = md5($key);
        $value = $default;
        if(!self::$force && self::$cache_ttl && class_exists('waSerializeCache')) {
            $cacher = new waSerializeCache($key, self::$cache_ttl, 'installer');
            if($cacher->isCached()) {
                $value = $cacher->get();
            }
        }
        return $value;
    }

    private static function setCacheValue($key,$value, $path = null)
    {
        $key .= '.'.self::$locale;
        if($path) {
            $key .= '.'.md5($path);
        }
        $key = md5($key);
        if(class_exists('waSerializeCache')) {
            $cacher = new waSerializeCache($key, self::$cache_ttl, 'installer');
            $cacher->set($value);
        }
        return $value;
    }

    public function __construct($license = null,$locale = null, $ttl = 600, $force = false)
    {
        $this->license = $license;
        //TODO build valid hash
        $host = $_SERVER['HTTP_HOST'];
        $host = preg_replace('@(^www\.|:\d+$)@', '', $host);
        $this->identity_hash = md5(__FILE__);
        //.'-'.urlencode(base64_encode($host));
        self::setLocale($locale);
        self::$cache_ttl = max(0,$ttl);
        self::$force = $force;
        if(file_exists(self::$root_path.self::CONFIG_APPS)){
            $this->installed_apps = include(self::$root_path.self::CONFIG_APPS);
            foreach($this->installed_apps as $app_id => &$enabled) {
                if($enabled) {
                    $this->installed_extras[$app_id] = array();
                    $this->installed_extras[$app_id]['plugins'] = self::getConfig(sprintf(self::CONFIG_APP_PLUGINS,$app_id));
                    $this->installed_extras[$app_id]['themes'] = self::getItems(sprintf(self::ITEM_EXTRAS_PATH,$app_id,'themes'));
                    $build_path = self::$root_path.sprintf(self::ITEM_BUILD,$app_id);
                    if (file_exists($build_path)) {
                        $enabled = max(1, include($build_path));
                    } else {
                        $enabled = 1;
                    }

                }
                unset($enabled);
            }
        }
        if(file_exists(self::$root_path.self::CONFIG_SOURCES)){
            $this->sources = include(self::$root_path.self::CONFIG_SOURCES);
        }

        //TODO USE config or etc
        $this->extras_list['plugins']=array('info'=>'plugin','subpath'=>'lib/config/');
        $this->extras_list['themes']=array('info'=>'theme','subpath'=>'');
    }

    private function getSources($key, $vendors = array())
    {
        if(!is_array($vendors) && $vendors){
            $vendors = array($vendors);
        }

        if (!$this->sources) {
            throw new Exception('Empty sources list');
        }

        if (empty($this->sources[$key])) {
            throw new Exception(sprintf('Not found sources for %s',$key));
        }
        $sources = array();
        foreach ((array)$this->sources[$key] as $vendor => $source) {
            if(!$vendor){
                $vendor = self::VENDOR_SELF;
            }
            if(!$vendors || in_array($vendor,$vendors)) {
                $sources[$vendor] = $source;
            }
        }
        return $sources;
    }

    /**
     * Get server items list for subject $key and $vendors
     * @param $key
     * @param $vendors
     * @return array
     */
    private function getList($key,$vendors = array())
    {
        static $lists = array();
        if(!is_array($vendors)){
            $vendors = array($vendors);
        }
        if(!isset($lists[$key])){
            $lists[$key] = array();
            if($sources = $this->getSources($key, $vendors)) {

                foreach($sources as $vendor=>$source) {
                    try{
                        if ($key == self::LIST_APPS) {
                            $options = $this->getInstalled($vendor);
                        } else {
                            $options = array();
                        }
                        foreach ($options as $k => $v ) {
                            $source .= strpos($source, '?') ? '&':'?';
                            $source .= "installed[{$k}]={$v}";
                        }
                        $lists[$key] = array_merge($lists[$key], $this->getFileData($source));
                    } catch(Exception $ex) {
                        //TODO write log
                        throw $ex;
                    }
                }
                if(!$lists[$key]) {
                    throw new Exception('Empty list. File '.self::CONFIG_SOURCES.' may be corrupted');
                }
                foreach($lists[$key] as $id=>&$item) {

                    $item['id'] = $item['slug'];
                    if(!empty($item['edition'])) {
                        $item['id'] .= '_'.$item['edition'];
                    }
                    $id = $item['slug'];

                    $item['current'] = self::getConfig(sprintf(self::ITEM_CONFIG,$id));
                    if($item['current']){
                        if(!isset($item['current']['edition'])) {
                            $item['current']['edition'] = '';
                        }
                        if (!empty($item['current']['prefix'])) {
                            $item['current']['slug'] = $item['current']['prefix'];
                        }
                    }


                    if (self::checkVendor($item)) {
                        self::fixItemVersion($item,$id);
                        self::fixItemIcon($item);


                        foreach($this->extras_list as $extras_type=>$extras_info) {
                            if(isset($item['extras'][$extras_type])) {
                                foreach($item['extras'][$extras_type] as &$extras_item) {
                                    $extras_id = (isset($extras_item['id']) && $extras_item['id'])?$extras_item['id']:$extras_item['slug'];
                                    $extras_config_path = self::getConfigPath($id,$extras_type,$extras_id,$extras_info);
                                    $extras_item['config_path'] = $extras_config_path;
                                    if($extras_item['current'] = self::getConfig($extras_config_path)) {
                                        if(self::checkVendor($extras_item)) {
                                            //XXX uncomplete code
                                            self::fixItemVersion($extras_item,null,$extras_info);
                                            self::fixItemIcon($extras_item);
                                            if(!file_exists(self::$root_path.'wa-apps/'.$extras_item['slug'].'/'.$extras_item['current']['img'])) {
                                                $extras_item['current']['img'] = false;
                                            }
                                        }else {
                                            $extras_item['current'] = false;
                                        }
                                    }else {
                                        $extras_item['current'] = false;
                                    }
                                    $extras_item['action'] = self::getApplicableAction($extras_item);
                                    $extras_item['applicable'] = $this->checkRequirements($extras_item['requirements'],false,$extras_item['action']);
                                    unset($extras_item);
                                }
                            }
                        }
                    }else {
                        $item['current'] = false;
                    }

                    $item['action'] = self::getApplicableAction($item);
                    $item['applicable'] = $this->checkRequirements($item['requirements'],false,$item['action']);
                    unset($item);
                }

            }else{
                throw new Exception(sprintf('Not found sources for %s',$key));
            }
        }elseif(!isset($lists[$key])){
            throw new Exception('Empty source list');
        }
        return $lists[$key];
    }

    /**
     *
     * @param $requirements
     * @param $update_config
     * @return boolean
     */
    public static function checkRequirements(&$requirements, $update_config = false, $action = false)
    {
        if(is_null($requirements)){
            $requirements = self::getRequirements('wa-installer/lib/config/requirements.php','installer');
        }
        $passed = true;
        $config = array();
        $actions = array(
        self::ACTION_CRITICAL_UPDATE,
        self::ACTION_UPDATE,
        self::ACTION_INSTALL,
        );
        $update = $action && in_array($action, $actions);
        foreach($requirements as $subject => &$requirement) {
            $requirement['passed'] = false;
            $requirement['note'] = null;
            $requirement['warning'] = false;
            $requirement['update'] = $update;

            waInstallerRequirements::test($subject, $requirement);

            $passed = $requirement['passed'] && $passed;
            if ($update_config && isset($requirement['config'])){
                $config[$requirement['config']] = $requirement['value'];
            }
            if ($requirement['note'] && isset($requirement['allow_skip']) && $requirement['allow_skip']) {
                unset($requirement);
                unset($requirements[$subject]);
            } else {
                unset($requirement);
            }
        }
        if($update_config){
            try {
                self::updateGenericConfig($config);
            } catch (Exception $e) {
                $requirements[] = array(
                    'name'=>'',
                    'passed' => false,
                    'warning' =>$e->getMessage(),
                    'description' =>'',
                    'note'=>'',
                );
                $passed = false;
            }
        }

        return $passed;
    }

    /**
     *
     * @param $item
     * @param $current_item
     * @return boolean
     */
    private static function checkVendor($item,$current_item = null)
    {
        $applicable = false;
        if(is_null($current_item) && isset($item['current'])) {
            $current_item = $item['current'];
        }
        if($current_item) {
            if(isset($current_item['vendor'])) {
                if(isset($item['vendor'])) {
                    $applicable = (strcasecmp($current_item['vendor'],$item['vendor'])==0)?true:false;
                    if($applicable) {
                        if(isset($item['edition'])) {
                            $applicable = (strcasecmp($current_item['edition'],$item['edition'])==0)?true:false;
                        } elseif(!empty($current_item['edition'])) {
                            $applicable = false;
                        }
                    }
                }else {
                    //TODO
                }

            }else {
                //XXX allow update, while vendor missed
                $applicable = false;
            }
        }
        return $applicable;
    }

    /**
     *
     * @param $path
     * @param $slug
     * @return array
     */
    private static function getRequirements($path,$slug)
    {
        if(!($requirements = false && self::getCacheValue($slug,null,$path))) {
            $requirements = self::getConfig($path);
            $fields = array('name','description');
            foreach($requirements as &$requirement) {
                foreach($fields as $field) {
                    if(isset($requirement[$field]) && is_array($requirement[$field])) {
                        if(self::$locale && isset($requirement[$field][self::$locale])) {
                            $value = $requirement[$field][self::$locale];
                        }elseif(isset($requirement[$field]['en_US'])) {
                            $value = $requirement[$field]['en_US'];
                        }else {
                            $value = array_shift($requirement[$field]);
                        }
                        $requirement[$field] = $value;
                    }elseif(!isset($requirement[$field])) {
                        $requirement[$field] = '';
                    }else {
                        $requirement[$field] = _wd($slug,$requirement[$field]);
                    }
                }
                if(!isset($requirement['strict'])) {
                    $requirement['strict'] = false;
                }
                unset($requirement);
            }
            //self::setCacheValue($slug,$requirements,$path);
        }
        return $requirements;
    }

    public static function getGenericConfig($property = null,$default = false)
    {
        $config =  self::getConfig(self::CONFIG_GENERIC);
        if ($property) {
            return isset($config[$property])?$config[$property]:$default;
        } else {
            return $config;
        }
    }

    /**
     *
     * @param array $list
     * @param boolean $clean_up
     * @return int|array
     */
    public static function getUpdateCount(&$items, $minimize = false)
    {
        $count = array();
        $count['total'] = 0;
        $count['applicable'] = 0;
        $count['payware'] = 0;

        $update_actions = array(self::ACTION_UPDATE, self::ACTION_CRITICAL_UPDATE);

        if($minimize) {
            $callback = create_function('$a, $b', 'return $a + $b;');
        }

        foreach ($items as $key => &$item) {
            $update = false;
            if ($item['current'] && ($item['enabled']) /*&& ($app_item['applicable'])*/) {
                if (in_array($item['action'], $update_actions)){
                    ++$count['total'];
                    if ($minimize && $item['applicable']) {
                        if (empty($item['payware']) || !empty($item['payware']['purchased'])) {
                            ++$count['applicable'];
                        } else {
                            ++$count['payware'];
                        }
                    }
                    $update = true;
                }

                if (isset($item['extras'])) {
                    foreach ($item['extras'] as $type=>&$extras) {
                        $extras_count = self::getUpdateCount($extras, $minimize);
                        if(!$minimize) {
                            $extras_count = array('total'=>$extras_count);
                        }
                        if (array_sum($extras_count)) {
                            $update = true;
                            foreach ($extras_count as $field => $extras_counter) {
                                $count[$field] += $extras_counter;
                            }
                        }
                        unset($extras);
                    }
                }
            }
            unset($item);
            if (!$update && $minimize) {
                unset($items[$key]);
            }
        }
        return $minimize ? $count : $count['total'];
    }


    /**
     *
     * @return array
     */
    public function getApplicationsList($local = false, $vendors = array(), $image_path = null, &$messages = null)
    {


        $local_apps = $this->installed_apps;
        $local_apps_extras = $this->installed_extras;
        if ($local || !$this->sources){
            $list = array();
        } else {
            try {
                $list = $this->getList(self::LIST_APPS, $vendors);
            } catch (Exception $ex) {
                if ($messages === null) {
                    throw $ex;
                } else {
                    $messages[] = array('text'=>$ex->getMessage(), 'result'=>'fail');
                }
                $list = array();
            }


            foreach ($list as &$item) {
                $app_id = $item['slug'];
                $item['img_cached'] = null;
                $item['enabled'] = $item['current'] && (isset($local_apps[$app_id])?$local_apps[$app_id]:null) && (!isset($item['edition']) || $item['edition'] == $item['current']['edition']);
                if($item['enabled'] && isset($local_apps[$app_id])) {
                    unset($local_apps[$app_id]);
                }

                if ($item['enabled'] && isset($local_apps_extras[$app_id])) {
                    foreach($item['extras'] as $type=>&$extras) {
                        if(isset($local_apps_extras[$app_id][$type])) {
                            foreach($extras as &$extras_item) {
                                $extras_id = str_replace("{$app_id}/{$type}/",'',$extras_item['slug']);
                                $extras_item['enabled'] = $extras_item['current'] && (isset($local_apps_extras[$app_id][$type][$extras_id]) ? $local_apps_extras[$app_id][$type][$extras_id]:null);
                                if($extras_item['enabled'] && isset($local_apps_extras[$app_id][$type][$extras_id])) {
                                    unset($local_apps_extras[$app_id][$type][$extras_id]);
                                }
                                unset($extras_item);
                            }
                        } else {
                            foreach($extras as &$extras_item) {
                                $extras_item['enabled'] = false;
                                $extras_item['current'] = false;
                                unset($extras_item);
                            }
                        }
                        unset($extras);
                    }
                } else {
                    foreach($item['extras'] as $type=>&$extras) {

                        foreach($extras as &$extras_item) {
                            $extras_item['enabled'] = false;
                            $extras_item['current'] = false;
                            unset($extras_item);
                        }
                    }
                }
                $this->fixItemLinks($item);
                foreach($item['extras'] as $type=>&$extras) {
                    foreach($extras as &$extras_item) {
                        $this->fixItemLinks($extras_item);
                        $this->fixItemImage($extras_item, $image_path);
                        self::fixItemIcon($extras_item);

                        unset($extras_item);
                    }
                    unset($extras);
                }
                $this->fixItemImage($item, $image_path);
                self::fixItemIcon($item);

                unset($item);
            }

        }


        $direcoryContent = scandir(self::$root_path.'wa-apps/');
        foreach($direcoryContent as $path) {
            if(preg_match('/^[a-z_\-\d][a-z_\-\d\.]*$/i',$path)){
                if(!isset($local_apps[$path])){
                    $local_apps[$path] = null;
                }
            }
        }
        foreach($local_apps as $app_id => $enabled) {
            if($enabled && ($config = self::getConfig(sprintf(self::ITEM_CONFIG,$app_id)))) {
                $item = array();
                $item['enabled'] = $enabled;
                $item['current'] = $config;
                $item['vendor'] = self::VENDOR_UNKNOWN;
                self::fixItemCurrent($item,$app_id,array('themes'));
                $item['extras'] = array();

                $requirements = self::getRequirements(sprintf(self::ITEM_REQUIREMENTS,$app_id),$item['slug']);
                $requirements = array_diff_assoc($requirements,$item['requirements']);
                if($requirements) {
                    if($item['applicable']){
                        $item['applicable'] = $this->checkRequirements($requirements);
                    }
                    $item['requirements'] = array_merge($requirements,$item['requirements']);

                }

                $list[] = $item;
                unset($item);
            }
        }

        #list local extras items
        foreach($list as &$item) {
            $app_id = $item['slug'];
            if(isset($local_apps_extras[$app_id])) {
                foreach($local_apps_extras[$app_id] as $extras_type => $extras) {

                    if(isset($this->extras_list[$extras_type])) {
                        if(!isset($item['extras'][$extras_type])) {
                            $item['extras'][$extras_type] = array();
                        }
                        foreach($extras as $extras_id => $enabled) {

                            if($enabled) {
                                $extras_item = array();
                                $extras_item['app_id'] = $app_id;
                                if(is_array($enabled)) {
                                    $extras_item = $enabled;
                                    $extras_item['enabled'] = true;
                                    $extras_item['action'] = false;
                                    $extras_item['applicable'] = true;
                                    $extras_item['requirements'] = array();
                                    $extras_item['current'] = $enabled;
                                    self::fixItemCurrent($extras_item,$extras_id,$this->extras_list[$extras_type]);
                                    $item['extras'][$extras_type][$extras_id] = $extras_item;
                                }else {
                                    $extras_item['enabled'] = $enabled;
                                    $extras_config_path = self::getConfigPath($app_id,$extras_type,$extras_id,$this->extras_list[$extras_type]);
                                    if($extras_item['current'] = self::getConfig($extras_config_path)) {
                                        self::fixItemCurrent($extras_item,"{$app_id}/{$extras_type}/{$extras_id}");
                                        self::fixItemVersion($extras_item,"{$app_id}/{$extras_type}/{$extras_id}",$this->extras_list[$extras_type]);
                                        if(!file_exists(self::$root_path.'wa-apps/'.$extras_item['slug'].'/'.$extras_item['current']['img'])) {
                                            $extras_item['current']['img'] = false;
                                        }
                                        $extras_item['requirements'] = self::getRequirements(sprintf(self::ITEM_REQUIREMENTS,$extras_id),$extras_id);
                                        $extras_item['applicable'] = $this->checkRequirements($extras_item['requirements'],false,$extras_item['action']);

                                        $item['extras'][$extras_type][$extras_id] = $extras_item;
                                    }
                                }
                            }
                        }
                    }
                }
                unset($item);
            }
        }

        uasort($list,array($this,'sortAppsCallback'));
        return $list;
    }

    /**
     * Download if neccassary local image copy for item (application or extras logo)
     * @param $item
     * @param $image_path
     * @return void
     */
    private function fixItemImage(&$item, $image_path = null)
    {
        if($image_path && !$item['current'] && isset($item['img'])&&$item['img']){
            $path = $image_path.'/'.$item['vendor'];
            if(!file_exists($path)){
                mkdir($path,0777,true);
            }
            if(strpos($item['slug'],'/') !== false) {
                if(!file_exists($path.'/'.dirname($item['slug']))) {
                    mkdir($path.'/'.dirname($item['slug']),0777,true);
                }
            }
            $path .= '/'.$item['slug'].(isset($item['edition'])&&$item['edition']?"_{$item['edition']}":'').'.png';
            if(!file_exists($path) || ((time()-filectime($path))>3600)){
                try{
                    if($img = $this->getFileContent($item['img'])){
                        file_put_contents($path,$img);
                    }
                }catch(Exception $ex) {
                    //ignore image downloading error
                    if(class_exists('waLog')) {
                        waLog::log($ex->__toString());
                    }
                }
            }
            if(file_exists($path)){
                $item['img_cached'] = $item['vendor'].'/'.$item['slug'].(isset($item['edition'])&&$item['edition']?"_{$item['edition']}":'').'.png';
            }else {
                $item['img_cached'] = null;
            }
        }
    }

    /**
     * Fix item images and icons names
     * @param $item
     * @return void
     */
    private static function fixItemIcon(&$item, $id = null)
    {
        if(!$id) {
            $id = (isset($item['id']) && $item['id'])?$item['id']:$item['slug'];
        }
        if (isset($item['current']) && $item['current'] !== false) {

            if(!isset($item['current']['img'])) {
                if (!isset($item['current']['icon'])) {
                    $item['current']['img'] = sprintf(self::ITEM_ICON,$id);
                } elseif(isset($item['current']['icon'][48])) {
                    $item['current']['img'] = $item['current']['icon'][48];
                } else {
                    $item['current']['img'] = sprintf(self::ITEM_ICON,$id);
                }
            }
            if(!isset($item['current']['icon'])) {
                $item['current']['icon'] = array();
            }

            if (!isset($item['current']['icon'][48])) {
                $item['current']['icon'][48] = $item['current']['img'];
            }
            if (!isset($item['current']['icon'][24])) {
                $item['current']['icon'][24] = $item['current']['icon'][48];
            }
            if (!isset($item['current']['icon'][16])) {
                $item['current']['icon'][16] = $item['current']['icon'][24];
            }
        }
    }

    /**
     *
     * @param $item
     * @param $fields
     * @return void
     */
    private function fixItemLinks(&$item, $fields = array())
    {
        if (!$fields) {
            $fields = array('img','download_link','info');
        }
        foreach ($fields as $field) {
            if(!empty($item[$field])) {
                $this->buildUrl($item[$field]);
            }
        }
    }


    private function buildUrl(&$path)
    {
        $is_url = preg_match('@^https?://@',$path);
        if (($this->license || $this->identity_hash) && $is_url && $this->originalUrl($path)) {
            $query = parse_url($path,PHP_URL_QUERY);
            if ($this->license) {
                $query = $query.($query?'&':'').'license='.$this->license;
            }
            if ($this->identity_hash) {
                $query = $query.($query?'&':'').'hash='.$this->identity_hash;
            }
            $host = $_SERVER['HTTP_HOST'];
            if ($host = preg_replace('@(^www\.|:\d+$)@', '', $host)) {
                $query = $query.($query?'&':'').'domain='.urlencode(base64_encode($host));
            }
            $path = preg_replace("@\?.*$@",'',$path);
            $path .= '?'.$query;
        }
        return $is_url;
    }

    private function originalUrl($url)
    {
        static $original_host;
        if(!$original_host) {
            foreach((array)$this->sources['apps'] as $vendor=>$source) {
                if(!$vendor){
                    $vendor = self::VENDOR_SELF;
                }
                if($vendor == self::VENDOR_SELF) {
                    $original_host = parse_url($source,PHP_URL_HOST);
                }
            }
        }
        $host = parse_url($url,PHP_URL_HOST);
        return $original_host && ($original_host == $host);
    }

    private static function fixItemCurrent(&$item,$id,$fields = array())
    {
        if(!$id) {
            $id = (isset($item['id']) && $item['id'])?$item['id']:$item['slug'];
        }

        $item['name'] = (isset($item['current']['name']) && $item['current']['name'])?$item['current']['name']:$id;
        $item['slug'] = (isset($item['current']['slug']) && $item['current']['slug'])?$item['current']['slug']:$id;
        $item['action'] = self::ACTION_NONE;
        $item['requirements'] = array();
        $item['applicable'] = true;

        $fields = array_merge($fields,array('slug','description','author','system','vendor'));

        foreach($fields as $field) {
            if(isset($item['current'][$field])) {
                if($item['current'][$field]){
                    $item[$field] = $item['current'][$field];
                }
            }else {
                $item['current'][$field] = '';
            }
            if(!isset($item[$field])) {
                $item[$field] = '';
            }
        }
        $ml_fileds = array('name','description');
        foreach($ml_fileds as $field) {
            //TODO use _wd/etc
            if(isset($item[$field]) && is_array($item[$field])) {
                $item[$field] = isset($item[$field][self::$locale])?$item[$field][self::$locale]:current($item[$field]);
            }
        }

        self::fixItemIcon($item);
    }

    private static function fixItemVersion(&$item,$id = null,$extras_info = null)
    {
        if(!isset($item['current']['version'])||!$item['current']['version']) {
            $item['current']['version'] = '0.0.0';
        }

        if(is_null($id) && isset($item['slug'])) {
            $id = $item['slug'];
        }
        if($id) {
            if(is_null($extras_info)) {
                $build_path = self::$root_path.sprintf(self::ITEM_BUILD,$id);
            }else {
                $build_path = self::$root_path.sprintf(self::ITEM_EXTRAS_PATH,$id,$extras_info['subpath']).'build.php';
            }
            if(file_exists($build_path) && ($build = include($build_path))) {
                $item['current']['version'] .= ".{$build}";
            }
        }
    }

    private function sortAppsCallback($a,$b)
    {
        $a['order'] = self::getActionPriority($a['action']);
        $b['order'] = self::getActionPriority($b['action']);
        $result = max(-1,min(1,($b['order']-$a['order'])));
        if($result == 0) {
            //XXX hardcoded order
            if($a['slug']=='installer') {
                $result = 1;
            } elseif($b['slug']=='installer') {
                $result = -1;
            } else {
                if(isset($a['priority']) && isset($b['priority'])) {
                    $result = max(-1,min(1,($b['priority']-$a['priority'])));
                }
                if($result == 0) {
                    $ap = (int)!empty($a['payware']);
                    $bp = (int)!empty($b['payware']);
                    if($ap != $bp) {
                        $result = $bp - $ap;
                    }
                }
                if($result == 0) {
                    $result = strcmp($a['name'],$b['name']);
                }
            }
        }
        return $result;
    }

    private static function getActionPriority($action)
    {
        $priority = null;
        switch($action){
            case self::ACTION_INSTALL:{$priority = 5;break;}
            case self::ACTION_CRITICAL_UPDATE:{$priority = 4;break;}
            case self::ACTION_UPDATE:{$priority = 3;break;}
            case self::ACTION_REPAIR:{$priority = 2;break;}
            case self::ACTION_NONE:{$priority = 1;break;}
        }
        return $priority;
    }

    /**
     *
     * @return array
     */
    public function getSystemList()
    {
        return $this->getList(self::LIST_SYSTEM,self::VENDOR_SELF);
    }

    private function getFileContent($path, $allow_caching = false)
    {
        //TODO check response code 4xx/200
        $is_url = $this->buildUrl($path);

        if(self::$locale && $is_url) {
            $query = parse_url($path,PHP_URL_QUERY);
            $query = $query.($query?'&':'').'lang='.self::$locale;
            $path = preg_replace("@\?.*$@",'',$path);
            $path .= '?'.$query;
        }

        if ($is_url && ($ch = self::getCurl($path))) {
            if (session_id()) {
                session_write_close();
            }
            $encoded = curl_exec($ch);

            if ($errno =  curl_errno($ch)) {
                $message = "Curl error: {$errno}# ".curl_error($ch)." at [{$path}]";
                curl_close($ch);
                throw new Exception($message);
            }
            $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response_code != 200) {
                $encoded = strip_tags($encoded);
                throw new Exception("Invalid server response with code {$response_code} while request {$path}");
            }
            curl_close($ch);
        } elseif ($is_url && @ini_get('allow_url_fopen')) {
            if (session_id()) {
                session_write_close();
            }
            $encoded = @file_get_contents($path);
            if (!$encoded ) {
                $response_code = 'unknown';
                $hint = '';
                if (!empty($http_response_header)) {
                    foreach($http_response_header as $header) {
                        if (preg_match('@^status:\s+(\d+)\s+(.+)$@i', $header, $matches)) {
                            $response_code = $matches[1];
                            $hint = " Hint: {$matches[2]}";
                            break;
                        }
                    }
                }
                throw new Exception("Invalid server response with code {$response_code} while request {$path}.{$hint}");
            }
            //TODO check stream headers
        } elseif (!$is_url) {
            $encoded = @file_get_contents($path);
        } else {
            throw new Exception("Couldn't read {$path} Please check allow_url_fopen setting or PHP extension Curl are enabled");
        }
        return $encoded;
    }

    private function getFileData($path)
    {
        //TODO add local sources support

        if(!$data = self::getCacheValue($path,array())){


            if(!($encoded = $this->getFileContent($path))){
                throw new Exception("Error while get server response {$path}");
            }

            if(!($serialized = base64_decode($encoded,true))){
                $hint = preg_replace('/[\w\d]{8,}=/','',$encoded,1);
                throw new Exception("Error while decode server response {$path}:\n {$hint}");
            }
            if(($data = @unserialize($serialized))===false){
                $hint = preg_replace('/a:\d+:\{.+}$/','',$serialized,1);
                throw new Exception("Error while unserialize server response {$path}:\n {$hint}");
            }
            if(!is_array($data)){
                $hint = 'array expected';
                throw new Exception("Invalid server response {$path}:\n {$hint}");
            }
            self::setCacheValue($path,$data);
        }
        return array_values($data);
    }

    private static function getCurl($url,$curl_options = array())
    {
        $ch = null;
        if(extension_loaded('curl')&&function_exists('curl_init')){
            if (!($ch = curl_init()) ){
                throw new Exception(("err_curlinit"));
            }

            if ( curl_errno($ch) != 0 ){
                throw new Exception(translate("err_curlinit").' '.curl_errno($ch).' '.curl_error($ch));
            }
            if(!is_array($curl_options)){
                $curl_options = array();
            }
            $curl_default_options = array(
            CURLOPT_HEADER				=>0,
            CURLOPT_RETURNTRANSFER		=>1,
            CURLOPT_TIMEOUT				=>10,
            CURLOPT_CONNECTTIMEOUT		=>10,
            CURLE_OPERATION_TIMEOUTED	=>10,
            //CURLOPT_FOLLOWLOCATION		=>true,
            );

            if ((version_compare(PHP_VERSION, '5.4', '>=') || !ini_get('safe_mode')) && !ini_get('open_basedir')){
                $curl_default_options[CURLOPT_FOLLOWLOCATION] = true;
            }

            foreach($curl_default_options as $option=>$value){
                if(!isset($curl_options[$option])){
                    $curl_options[$option] = $value;
                }
            }
            $curl_options[CURLOPT_URL] = $url;
            $options_fields =array(
				'host'=>'PROXY_HOST',
				'port'=>'PROXY_PORT',
				'user'=>'PROXY_USER',
				'password'=>'PROXY_PASS',
            ) ;
            $options = array();//SystemSettings::get($options_fields);

            if (isset($options['host'])&&strlen($options['host'])) {
                $curl_options[CURLOPT_HTTPPROXYTUNNEL] = true;
                $curl_options[CURLOPT_PROXY] = sprintf("%s%s",$options['host'],(isset($options['port'])&&$options['port']) ? ':'.$options['port'] :'');

                if (isset($options['user'])&&strlen($options['user'])) {
                    $curl_options[CURLOPT_PROXYUSERPWD] = sprintf("%s:%s",$options['user'],$options['password']);
                }
            }
            foreach($curl_options as $param=>$option){
                curl_setopt($ch, $param, $option);
            }
        }
        return $ch;
    }

    private static function getItems($path, $pattern = '/^[a-z_\-\d][a-z_\-\d\.]*$/i')
    {
        $paths = array();
        if(file_exists(self::$root_path.$path)) {
            $direcoryContent = scandir(self::$root_path.$path);
            foreach($direcoryContent as $item_path) {
                if(preg_match($pattern,$item_path)){
                    $paths[$item_path] = true;
                }
            }
        }
        return $paths;
    }

    private static function getConfigPath($id, $extras_type, $extras_id = null,$extras_info = 'config')
    {
        return sprintf(self::ITEM_EXTRAS,$id,$extras_type,$extras_id,$extras_info['subpath'],$extras_info['info']);
    }

    private static function getConfig($path)
    {
        $config = array();
        $path = self::$root_path.$path;
        //hack for theme xml
        $path_xml = preg_replace('@\.php$@','.xml', $path);
        if(file_exists($path_xml)) {
            $xml = @simplexml_load_file($path_xml);
            $ml_fields = array('name','description');
            foreach ($ml_fields as $field) {
                $config[$field] = array();
            }
            foreach ($xml->attributes() as $field=>$value) {
                $config[$field] = (string)$value;
            }

            foreach($ml_fields as $field) {
                if($xml->{$field}) {
                    foreach ($xml->{$field} as $value) {
                        if($locale = (string)$value['locale']) {
                            $config[$field][$locale] = (string)$value;
                        }
                    }
                }
            }
        } elseif(file_exists($path)) {
            $locale = self::$locale;
            $config = include($path);
            if(!is_array($config)){
                $config = array();
            }
        }
        return $config;
    }

    private static function setConfig($path,$config)
    {
        if(is_array($config)&&(self::mkdir(dirname($path)))&&($fp = @fopen(self::$root_path.$path,'w'))){
            if (!@flock($fp, LOCK_EX)) {
                fclose($fp);
                throw new Exception('Unable to lock '.$path);
            }
            fwrite($fp,"<?php\n\nreturn ");
            fwrite($fp,var_export($config,true));
            fwrite($fp,";\n//EOF");

            @flock($fp, LOCK_UN);
            fclose($fp);
            return $config;
        }else{
            throw new Exception('Error while save config at '.$path);
        }
    }

    /**
     *
     * @throws Exception
     * @param $app_id string
     * @param $enabled boolean or null to remove
     * @return void
     */
    public function updateAppConfig($app_id,$enabled = true)
    {
        $config = array($app_id=>$enabled);
        $config = array_merge(self::getConfig(self::CONFIG_APPS),$config);
        if(is_null($enabled)){
            unset($config[$app_id]);
        }
        self::setConfig(self::CONFIG_APPS,$config);
    }

    /**
     *
     * @throws Exception
     * @param $app_id string
     * @param $plugin_id string
     * @param $enabled boolean or null to remove
     * @return void
     */
    public function updateAppPluginsConfig($app_id,$plugin_id,$enabled = true)
    {
        $config = array($plugin_id=>$enabled);
        $path = sprintf(self::CONFIG_APP_PLUGINS,$app_id);
        $config = array_merge(self::getConfig($path),$config);
        if(is_null($enabled)){
            unset($config[$plugin_id]);
        }
        return self::setConfig($path,$config);
    }

    /**
     *
     * @throws Exception
     * @param $app_id string
     * @param $routing array
     * @param $domain string
     * @return string
     */
    public function updateRoutingConfig($app_id='default',$routing = array(),$domain = null)
    {
        $result = null;
        $current_routing = self::getConfig(self::CONFIG_ROUTING);
        if(!$routing){
            foreach($current_routing as $domain=>&$routes) {
                foreach($routes as $route_id => $route) {
                    if (is_array($route)) { //route is array
                        if(isset($route['app']) && ($route['app']==$app_id) ) {
                            unset($routes[$route_id]);
                        }
                    } else { //route is string
                        $route = array_shift(array_filter(explode('/',$route),'strlen'));
                        if($route == $app_id) {
                            unset($routes[$route_id]);
                        }
                    }
                }
                unset($routes);
            }
        }else{
            if(is_null($domain)) {
                $domain = $_SERVER['HTTP_HOST'];
                if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']) {
                    $root_url = $_SERVER['SCRIPT_NAME'];
                } elseif (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF']) {
                    $root_url = $_SERVER['PHP_SELF'];
                } else {
                    $root_url = '/';
                }
                $root_url = preg_replace('!/[^/]*$!', '/', $root_url);
                $root_url = trim($root_url, '/');
                if ($root_url) {
                    $domain .= '/'.$root_url;
                }
            }

            if(!isset($current_routing[$domain])){
                $current_routing[$domain] = array();
            }

            $root_owned = false;
            foreach($current_routing[$domain] as $route) {
                $url = is_array($route)?$route['url']:$route;
                if(strpos($url, '*') === 0) {
                    $root_owned = true;
                    break;
                }
            }
            if (($app_id == 'site')) {
                $routing['url'] = $root_owned?"{$app_id}/*":'*';
            }

            $rule_exists = false;
            foreach($current_routing[$domain] as $route_id => $route) {
                if (is_array($route)) { //route is array
                    if(isset($route['app']) && ($route['app']==$app_id) ) {
                        $rule_exists = true;
                        break;
                    }
                } else { //route is string
                    $route = array_shift(array_filter(explode('/',$route),'strlen'));
                    if($route == $app_id) {
                        $rule_exists = true;
                    }
                }
            }

            if (!$rule_exists) {
                $current_routing[$domain][] = $routing;
                if ($root_owned) {
                    uasort($current_routing[$domain] , create_function('$a,$b', '
					$a = is_array($a)?$a["url"]:$a;
					$b = is_array($b)?$b["url"]:$b;
					return (strpos($a, "*") === 0)?1:((strpos($b, "*") === 0)?-1:0);'));
                }
            }
            $result = $domain;
        }
        self::setConfig(self::CONFIG_ROUTING,$current_routing);
        return $domain;
    }

    /**
     * Update database settings
     * @throws Exception
     * @param $config array
     * @param $id
     * @return void
     */
    public function updateDbConfig($config = array(),$id = 'default')
    {
        $config = array($id=>$config);
        $config = array_merge(self::getConfig(self::CONFIG_DB),$config);
        self::setConfig(self::CONFIG_DB,$config);
    }

    /**
     * Update database settings
     * @throws Exception
     * @param $config array
     * @param $id
     * @return void
     */
    private static function updateGenericConfig($config = array())
    {
        $config = array_merge(self::getConfig(self::CONFIG_GENERIC),$config);
        //XXX
        if(!isset($config['debug'])){
            $config['debug'] = false;
        }
        self::setConfig(self::CONFIG_GENERIC,$config);
    }

    public static function setGenericOptions($options = array())
    {
        $allowed_options = array('mod_rewrite','debug');
        foreach($options as $id=>$option) {
            if(!in_array($id, $allowed_options, true)) {
                unset($options[$id]);
            }
        }
        if($options) {
            self::updateGenericConfig($options);
        }
    }

    /**
     * Register applications at config and add routing for it
     * @throws Exception
     * @param $app_id string application slug
     * @param $domain string domen ID fo
     * @param $edition string application edition
     * @return void
     */
    public function installWebAsystItem($slug,$domain = null,$edition = true)
    {
        $slugs = explode('/',$slug);
        if(count($slugs)==3) {
            switch($slugs[1]) {
                case 'plugins': {
                    $this->updateAppPluginsConfig($slugs[0],$slugs[2]);
                    break;
                }
                default: {
                    throw new Exception("Invalid subject for method ".__METHOD__);
                }
            }
        }else {
            $this->installWebAsystApp($slug,$domain,$edition);
        }
    }

    /**
     * Register applications at config and add routing for it
     * @throws Exception
     * @param $app_id string application slug
     * @param $domain string domen ID fo
     * @param $edition string application edition
     * @return void
     */
    public function installWebAsystApp($app_id,$domain = null,$edition = true)
    {
        $this->updateAppConfig($app_id,$edition);
        $config = self::getConfig(sprintf(self::ITEM_CONFIG,$app_id));
        if(isset($config['frontend'])&&$config['frontend']){
            $routing = array(
				'url' => $app_id.'/*',
				'app' => $app_id,
            );
            $this->updateRoutingConfig($app_id,$routing,$domain);
        }
    }

    private static function mkdir($target_path,$mode = 0777)
    {
        if (!file_exists(self::$root_path.$target_path)) {
            if (!mkdir(self::$root_path.$target_path,$mode&0777,true)) {
                throw new Exception("Error occurred while creating a directory {$target_path}");
            }
        } elseif(!is_dir(self::$root_path.$target_path)) {
            throw new Exception("Error occurred while creating a directory {$target_path} - it's a file");

        } elseif(!is_writable(self::$root_path.$target_path)) {
            throw new Exception("Directory {$target_path} unwritable");
        }
        if(preg_match('`^/?(wa-data/protected|wa-log|wa-cache|wa-config)/?`',$target_path,$matches)) {
            $htaccess_path = $matches[1].'/.htaccess';
            if (!file_exists(self::$root_path.$htaccess_path)) {
                if ($fp = @fopen(self::$root_path.$htaccess_path,'w')) {
                    fwrite($fp,"Deny from all\n");
                    fclose($fp);
                } else {
                    throw new Exception("Error while trying to protect a directory {$target_path} with htaccess");
                }
            }
        }
        return true;
    }

    private static function getApplicableAction($item)
    {
        if(isset($item['current']) && $item['current']) {
            if(isset($item['current']['version'])) {
                if(isset($item['edition']) && ($item['edition'] != $item['current']['edition'])) {
                    $action = self::ACTION_INSTALL;
                }elseif(version_compare($item['version'],$item['current']['version'],'>')) {
                    if(isset($item['critical']) && version_compare($item['critical'],$item['current']['version'],'>')) {
                        $action = self::ACTION_CRITICAL_UPDATE;
                    }else {
                        $action = self::ACTION_UPDATE;
                    }
                }else {
                    $action = self::ACTION_REPAIR;
                }
            }else {
                $action = self::ACTION_UPDATE;
            }
        }elseif(isset($item['download_link']) && $item['download_link']) {
            $action = self::ACTION_INSTALL;
        }else {
            $action = self::ACTION_NONE;
        }
        return $action;
    }

    public function getHash()
    {
        return $this->identity_hash;
    }

    private function getInstalled($vendor = self::VENDOR_SELF)
    {
        $list = array();
        foreach($this->installed_apps as $app_id => $build) {
            if($build) {
                $list[$app_id] = $build;
            }
        }
        return $list;
    }

    public function query($query, $vendor = self::VENDOR_SELF)
    {
        $result = false;
        $sources = $this->getSources(self::LIST_APPS, $vendor);
        if (!empty($sources[$vendor])) {
            $path = preg_replace('@apps/list/$@','',$sources[$vendor]).$query;
            if($this->buildUrl($path) && ($result = $this->getFileContent($path))) {
                $result = json_decode($result, true);
            }
        }
        return $result;
    }

    public function checkUpdates()
    {
        if (!$this->sources) {
            throw new Exception('Empty sources list');
        }
        $requirements = array();
        $requirements['php.curl']=array(
		'description'=>'Get updates information from update servers',
		'strict'=>true,
        );
        $requirements['phpini.allow_url_fopen']=array(
		'description'=>'Get updates information from update servers',
		'strict'=>true,
		'value'=>1,
        );
        if (!self::checkRequirements($requirements)) {
            foreach ($requirements as $requirement) {
                if(!$requirement['passed']) {
                    $messages[] = $requirement['name'].' '.$requirement['warning'];
                } else {
                    $messages = null;
                    break;
                }
            }
            if($messages) {
                throw new Exception(implode("\n", $messages));
            }
        }
    }
}
//EOF