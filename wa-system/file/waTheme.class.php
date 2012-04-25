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
 * @subpackage files
 */

/*
 * Theme design helper
 */
class waTheme implements ArrayAccess
{
    /**
     *
     * Original not modified theme
     * @var string
     */
    const ORIGINAL         = 'original';

    /**
     *
     * User theme
     * @var string
     */
    const CUSTOM         = 'custom';

    /**
     *
     * Overriden theme
     * @var string
     */
    const OVERRIDDEN     = 'overridden';

    /**
     *
     * Undefined theme type
     * @var string
     */
    const NONE             = 'none';
    const PATH             = 'theme.xml';

    protected $app;
    protected $id;
    protected $info;
    protected $extra_info = array();
    protected $path_original;
    protected $path_custom;
    protected $path;
    protected $type;
    protected $url;
    private $changed = array();

    /**
     * Get theme instance
     * @param $id
     * @param bool|string $app
     * @param bool $force
     */
    public function __construct($id, $app = true, $force = false)
    {
        //TODO validate theme id
        $this->id = $id;
        $this->app = ($app === true || !$app) ? wa()->getApp() : $app;
        $this->initPath($force);
    }

    public static function exists($id, $app_id = true)
    {
        $app_id = ($app_id === true || !$app_id) ? wa()->getApp() : $app_id;

        $theme_path = wa()->getDataPath('themes', true, $app_id).'/'.$id;
        if (file_exists($theme_path) && file_exists($theme_path.'/'.self::PATH)) {
            return true;
        }
        return file_exists(wa()->getAppPath(null, $app_id).'/themes/'.$id);
    }

    private function initPath($force = false)
    {
        $this->info = null;
        $this->extra_info = array();
        $this->url = null;

        $this->path_custom     = wa()->getDataPath('themes', true, $this->app).'/'.$this->id;
        $this->path_original = wa()->getAppPath('themes/', $this->app).$this->id;

        if (!file_exists($this->path_custom) || (!$force && !file_exists($this->path_custom.'/'.self::PATH))) {
            $this->path_custom = false;
        }

        if (!file_exists($this->path_original) || (!$force && !file_exists($this->path_original.'/'.self::PATH))) {
            $this->path_original = false;
        }

        if($this->path_custom && $this->path_original) {
            $this->type = self::OVERRIDDEN;
            $this->path = $this->path_custom;
        } elseif ($this->path_custom) {
            $this->type = self::CUSTOM;
            $this->path = $this->path_custom;
        } elseif ($this->path_original) {
            $this->type = self::ORIGINAL;
            $this->path = $this->path_original;
        } else {
            $this->type = self::NONE;
            $this->path = false;
            //theme not found
        }
    }

    private function init($param = null)
    {
        if(is_null($this->info)) {
            $path = $this->path.'/'.self::PATH;
            $extension = pathinfo($path,PATHINFO_EXTENSION);
            switch ($extension) {
                case 'xml': {
                    $locale = self::getLocale();

                    $this->info = array('name'=>array($locale=>$this->id),'files'=>array());
                    if(!$xml = $this->getXML()) {
                        trigger_error("Invalid theme description {$path}",E_USER_WARNING);
                        break;
                    }
                    $ml_fields = array('name','description');

                    foreach ($ml_fields as $field) {
                        $this->info[$field] = array();
                    }
                    foreach ($xml->attributes() as $field=>$value) {
                        $this->info[$field] = (string)$value;
                    }

                    $this->info['system'] = isset($this->info['system'])?(bool)$this->info['system'] : false;

                    foreach ($ml_fields as $field) {
                        if ($xml->{$field}) {
                            foreach ($xml->{$field} as $value) {
                                if ($value && ($locale = (string)$value['locale'])) {
                                    $this->info[$field][$locale] = (string)$value;
                                }
                            }
                        } elseif($field == 'name') {
                            $locale = self::getLocale();
                            $this->info[$field][$locale] = $this->id;
                        }
                    }

                    $this->info['files'] = array();
                    if ($files = $xml->files) {
                        foreach ($files->children() as $key=>$file) {
                            $path = (string)$file['path'];
                            $this->info['files'][$path] = array(
                                'custom' => isset($file['custom']) ? (bool)$file['custom'] : false
                            );
                            foreach ($file->description as $value) {
                                if ($value && ($locale = (string)$value['locale'])) {
                                    $this->info['files'][$path]['description'][$locale] = (string)$value;
                                }
                            }
                        }
                        ksort($this->info['files']);
                    }
                    break;
                }
                case 'php': {
                    if (file_exists($path)) {
                        $this->info = include($path);
                    }
                    break;
                }
                default: {
                    $this->info = array();
                    break;
                }
            }
        }
        return ($param===null)?true:isset($this->info[$param]);

        //TODO check info and construct params
    }

    /**
     *
     * Append file into theme
     * @param string $path
     * @param array $description
     */
    public function addFile($path, $description)
    {
        $this->setFiles(array($path=>array('custom'=>true,'description'=>$description)));
        return $this;
    }

    /**
     *
     * Remove file from theme
     * @param string $path
     * @throws waException
     * @return waTheme
     */
    public function removeFile($path)
    {
        if(preg_match('@(^|[\\/])..[\\/]@', $path)){
            throw new waException("Invalid theme's file path");
        }
        $this->init();
        if (isset($this->info['files'][$path])) {
            unset($this->info['files'][$path]);
            if (!isset($this->changed['files'])) {
                $this->changed['files'] = array();
            }
            $this->changed['files'][$path] = true;
            waFiles::delete($this->path."/".$path);
        }
        return $this;
    }

    public function changeFile($file, $description)
    {
        $this->init();
        if (!isset($this->info['files'][$file]) || !$this->info['files'][$file]['custom']) {
            return true;
        }
        if(is_array($description)){
            if ($this->info['files'][$file]['description'] == $description) {
                return true;
            }
        } else {
            if (self::prepareField($this->info['files'][$file]['description']) == $description) {
                return true;
            }
        }
        $this->setFiles(array($file=>array('description'=>$description)));
        return $this->save();
    }

    private function getXML($as_dom = false)
    {
        $path = $this->path.'/'.self::PATH;
        if (file_exists($path)) {
            if($as_dom) {
                $xml = new DOMDocument(1.0, 'UTF-8');
                $xml->load($path);
            } else {
                $xml = @simplexml_load_file($path, null, LIBXML_NOCDATA);
            }
        } else {
            $data = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE theme PUBLIC "wa-app-theme" "http://www.webasyst.com/wa-content/xml/wa-app-theme.dtd" >
<theme id="{$this->id}" system="0" vendor="unknown" app="{$this->app}">
    <name locale="en_US">{$this->id}</name>
    <files>
    </files>
</theme>
XML;
            if($as_dom) {
                $xml = new DOMDocument(1.0, 'UTF-8');
                $xml->loadXML($data);
            } else {
                $xml = @simplexml_load_string($data, null, LIBXML_NOCDATA);
            }
        }
        return $xml;
    }

    /**
     * @todo complete code - it's not work properly
     * Enter description here ...
     */
    public function save()
    {
        $res = null;
        if ($this->changed) {
            $path = $this->path.'/'.self::PATH;
            $extension = pathinfo($path,PATHINFO_EXTENSION);
            switch($extension) {
                case 'xml': {
                    $dom = $this->getXML(true);
                    $xpath = new DOMXPath($dom);
                    $theme = $xpath->query('/theme')->item(0);

                    $ml_fields = array('name','description');
                    foreach ($ml_fields as $field) {
                        if(isset($this->changed[$field]) && $this->info[$field]) {
                            foreach($this->info[$field] as $locale=>$value) {
                                $query = "/theme/{$field}[@locale='{$locale}']";
                                if(!($node = $xpath->query($query)->item(0))) {
                                    $node = new DOMElement($field, $value);
                                    //$xml_node->setAttribute('locale', $locale);
                                    $theme->appendChild($node);
                                    $node->setAttribute('locale', $locale);
                                } else {
                                    $node->nodeValue = $value;
                                }
                            }
                        }
                        unset($this->changed[$field]);
                    }


                    if(isset($this->changed['files'])) {
                        //files workaround
                        $query = "/theme/files";
                        if(!($files = $xpath->query($query)->item(0)) ){
                            $files = new DOMElement('files');
                            $dom->appendChild($files);
                        }
                        foreach($this->changed['files'] as $file_path=>$changed) {

                            if(isset($this->info['files'][$file_path]) && $this->info['files'][$file_path]) {
                                $info = $this->info['files'][$file_path];
                                $query = "/theme/files/file[@path='{$file_path}']";
                                if(!($file = $xpath->query($query)->item(0))) {
                                    $file = new DOMElement('file');
                                    $files->appendChild($file);
                                    $file->setAttribute('path', $file_path);
                                }
                                $file->setAttribute('custom', $info['custom']?'1':'0');

                                foreach($info['description'] as $locale=>$value) {
                                    $query_description = "{$query}/description[@locale='{$locale}']";
                                    if(!($description = $xpath->query($query_description)->item(0))) {
                                        $description = new DOMElement('description',$value);
                                        $file->appendChild($description);
                                        $description->setAttribute('locale',$locale);
                                    } else {
                                        $description->nodeValue = $value;
                                    }
                                }



                            } else {
                                $query = "/theme/files/file[@path='{$file_path}']";
                                if(($file =  $xpath->query($query)->item(0))) {
                                    $files->removeChild($file);
                                }
                            }
                        }
                        unset($this->changed['files']);
                    }

                    if($this->changed) {
                        foreach ($this->changed as $field => $changed) {
                            if ($changed) {
                                $theme->setAttribute($field, $this->info[$field]);
                            }
                            unset($this->changed[$field]);
                        }
                    }

                    $res = $dom->save($path,LIBXML_COMPACT);
                    break;
                }
                case 'php': {
                    $res = waUtils::varExportToFile($this->info, $path);
                    break;
                }
                default: {
                    //nothing todo
                    break;
                }
            }
        }
        return $res;
    }

    public function __destruct()
    {
        $this->save();
    }

    /**
     * Copy existing theme
     * @param string $id
     * @param array $params
     * @throws waException
     * @return siteThemes
     */
    public function copy($id = null, $params = array())
    {
        if ($id) {
            self::verify($id);
        } else {
            $id = $this->id;
        }
        $target = wa()->getDataPath("themes/{$id}",true,$this->app,false);
        if (file_exists($target.'/'.self::PATH)) {
            throw new waException(sprintf(_ws("Theme %s already exists"),$id));
        }
        self::protect($this->app);
        waFiles::copy($this->path, $target,'/\.(files\.md5|cvs|svn|git|php\d*)$/');
        @touch($target.'/'.self::PATH);
        if ($this->id != $id) {
            //hack for extended classes
            $class = get_class($this);
            /**
             * @var $instance waTheme
             */
            $instance =  new $class($id,$this->app);
            $instance->init();
            $instance->info['id'] = $id;
            $instance->changed['id'] = true;
            foreach($params as $param=>$value) {
                $instance[$param] = $value;
            }
            $instance->save();
            return $instance;
        } else {
            $this->initPath();
            foreach($params as $param=>$value) {
                $this[$param] = $value;
            }
            if ($params) {
                $this->save();
            }
            return $this;
        }
    }


    /**
     * Rename existing theme
     * @param string $id
     * @param array $params
     * @throws waException
     * @return waTheme
     */
    public function move($id, $params = array())
    {
        if ($this->id != $id) {
            self::verify($id);
            $target = wa()->getDataPath("themes/{$id}",true,$this->app,false);
            if (file_exists($target)) {
                throw new waException(sprintf(_ws("Theme %s already exists"),$id));
            }
            self::protect($this->app);
            waFiles::move($this->path, $target);
            $class = get_class($this);
            /**
             * @var waTheme $instance
             */
            $instance =  new $class($id,$this->app);
            $instance->init();
            $instance->info['id'] = $id;
            $instance->changed['id'] = true;
            foreach($params as $param=>$value) {
                $instance[$param] = $value;
            }
            $instance->save();
            return $instance;
        } elseif($this->type == self::ORIGINAL) {
            return $this->copy($id,$params);
        } else {
            foreach($params as $param=>$value) {
                $this[$param] = $value;
            }
            if($params) {
                $this->save();
            }
            return $this;
        }
    }

    /**
     *
     * Reset overriden theme changes
     * @return bool
     */
    public function reset()
    {
        if ($this->path_custom && ($this->type == self::OVERRIDDEN)) {
            $res = waFiles::delete($this->path_custom);
            $this->initPath();
            $this->flush();
            return $res;
        } else {
            return false;
        }
    }

    /**
     *
     * Flush template's caches
     */
    public function flush()
    {
        //wa-cache/apps/$app_id/templates/
        waFiles::delete(wa()->getAppCachePath('templates',$this->app));
    }

    /**
     *
     * Delete custom theme
     */
    public function delete()
    {
        if ($this->path_custom && in_array($this->type, array(self::OVERRIDDEN,self::CUSTOM))) {
            $res = waFiles::delete($this->path_custom);
            $this->flush();
            return $res;
        } else {
            return false;
        }
    }

    protected static function protect($app)
    {
        // create .htaccess to ney access to *.php and *.html files
        $path = wa()->getDataPath('themes/.htaccess', true, $app, false);
        if (!file_exists($path)) {
            waFiles::create($path);
            $htaccess = '<FilesMatch "\.(php\d*|html?)$">
    Deny from all
</FilesMatch>
';
            @file_put_contents($path, $htaccess);
        }
    }

    public function getPath()
    {
        return $this->path;
    }

    private function getPath_custom()
    {
        return $this->path_custom;
    }

    private function getPath_original()
    {
        return $this->path_original;
    }

    private function getApp()
    {
        return $this->app;
    }

    /**
     *
     * @todo app or app_id at theme description?
     */
    private function getApp_id()
    {
        return $this->app;
    }

    private function getId()
    {
        return $this->id;
    }

    public function getUrl()
    {
        if (is_null($this->url)) {
            switch ($this->type) {
                case self::CUSTOM:
                case self::OVERRIDDEN: {
                    $this->url = wa()->getDataUrl('themes', true, $this->app).'/'.$this->id.'/';
                    break;
                }
                case self::ORIGINAL: {
                    $this->url = wa()->getAppStaticUrl($this->app).'themes/'.$this->id.'/';
                    break;
                }
                default: {
                    $this->url = false;
                    break;
                }
            }
        }
        return $this->url;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setName($name)
    {
        $this->info['name'] = self::prepareSetField($this->init('name')?$this->info['name']:'', $name);
        $this->changed['name'] = true;
    }

    public function setVendor($vendor)
    {
        $this->init();
        $this->info['vendor'] = $vendor;
        $this->changed['vendor'] = true;
    }

    public function getVendor()
    {
        return $this->init('vendor')?$this->info['vendor']:'unknow';
    }

    public function setDescription($name)
    {
        $this->info['description'] = self::prepareSetField($this->init('description')?$this->info['description']:'', $name);
        $this->changed['description'] = true;
    }

    private static function prepareSetField($field,$value)
    {
        $field = self::prepareField($field,true);
        if (is_array($value)) {
            foreach($value as $locale=>$item_value) {
                $field[$locale] = $item_value;
            }
        } else {
            $locale = self::getLocale($field);
            $field[$locale] = $value;
        }
        return $field;
    }

    private static function prepareField($field,$full = false)
    {
        if (is_array($field)) {
            if ($full) {
                return $field;
            } else {
                $locale = self::getLocale($field);
                return $field[$locale];
            }
        } elseif($full) {
            $locale = self::getLocale();
            return array($locale=>$field);
        } else {
            return $field;
        }
    }

    public function getName($full = false)
    {
        return self::prepareField($this->init('name')?$this->info['name']:$this->id,$full);
    }

    public function getDescription($full = false)
    {
        return self::prepareField($this->init('description')?$this->info['description']:'',$full);
    }

    /**
     *
     * Hook for offsetSet('files')
     * @param array $file
     */
    private function setFiles($file)
    {
        $this->init();
        foreach ($file as $path=>$properties) {
            if(!isset($this->changed['files'])) {
                $this->changed['files'] = array();
            }
            $this->changed['files'][$path] = true;
            if (!$properties) {
                unset($this->info['files'][$path]);
            } else {
                $description = $properties['description'];
                if(!isset($this->info['files'][$path])) {
                    $this->info['files'][$path] = array('description'=>array(),'custom'=>true);
                }
                if(isset($properties['custom'])) {
                    $this->info['files'][$path]['custom'] = $properties['custom']?true:false;
                }
                $this->info['files'][$path]['description'] = self::prepareSetField($this->info['files'][$path]['description'], $description);
            }
        }
    }

    /**
     * @param offset
     */
    public function offsetExists ($offset)
    {
        return method_exists($this, 'get'.ucfirst($offset))?true:$this->init($offset)?true:isset($this->extra_info[$offset]);
    }

    /**
     * @param offset
     */
    public function offsetGet ($offset)
    {
        $value = null;
        if(method_exists($this, $method_name = 'get'.ucfirst($offset))) {
            $value =  $this->{$method_name}();
        } elseif($this->init($offset)) {
            $value =  &$this->info[$offset];
        } elseif(isset($this->extra_info[$offset])) {
            $value = $this->extra_info[$offset];
        }
        return $value;
    }

    /**
     * @param offset
     * @param value
     */
    public function offsetSet ($offset, $value)
    {
        if(method_exists($this, $method_name = 'set'.ucfirst($offset))) {
            //hook for $theme['name']=array('ru_RU' => 'name'); and etc
            $value =  $this->{$method_name}($value);
        } elseif($this->init($offset)) {
            $this->changed[$offset] = true;
            $this->info[$offset] = $value;
        } else {
            $this->extra_info[$offset] = $value;
        }
        return $value;
    }

    /**
     * @param offset
     */
    public function offsetUnset ($offset)
    {
        if (is_null($this->info)) {
            $this->init();
        }
        if(isset($this->info[$offset])) {
            $this->changed[$offset] = true;
            unset($this->info[$offset]);
        } else if(isset($this->extra_info[$offset])) {
            unset($this->extra_info[$offset]);
        }
    }

    private function getMtime()
    {
        $path = $this->path.'/'.self::PATH;
        return file_exists($path)?filemtime($path):false;
    }

    public function getFile($file)
    {
        $this->init();
        if (!$file || !isset($this->info['files'][$file])) {
            return array();
        }
        $res = $this->info['files'][$file];
        $res['description'] = self::prepareField($res['description']);
        return $res;
    }

    public function getFiles($full = false)
    {
        $this->init();
        if($full) {
            return $this->info['files'];
        } else {
            $files = $this->info['files'];
            foreach($files as &$file) {
                $file['description'] = self::prepareField($file['description']);
            }
            unset($file);
            return $files;
        }
    }


    private static function getLocale($data = array())
    {
        $locale = wa()->getLocale();
        if ($data) {
            if(!isset($data[$locale])) {
                $locale = 'en_US';
                if(!isset($data[$locale])) {
                    $locale = reset(array_keys($data));
                }
            }
        }

        return $locale;
    }

    public static function verify($id)
    {
        if(!preg_match('/^[a-z_][a-z_\-0-9]*$/i',$id)) {
            throw new waException(sprintf(_ws("Invalid theme id %s"),$id));
        }
    }
}