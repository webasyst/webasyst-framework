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

/**
 *
 * @todo try to use extendable class tests
 */
class waInstallerRequirements
{
    private static $instance;
    private $root;

    /**
     *
     * @return waInstallerRequirements
     */
    private static function getInstance()
    {
        if(!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->root = dirname(__FILE__).'/../../../';
        $this->root = preg_replace('@([/\\\\]+)@','/',$this->root);
        while(preg_match('@/\.\./@',$this->root)){
            $this->root = preg_replace('@/[^/]+/\.\./@','/',$this->root);
        }
    }

    private function __clone()
    {
        ;
    }

    public static function test($case,&$requirement)
    {
        static $config = array();
        $subject = null;
        if(strpos($case,'.')!==false){
            list($case,$subject) = explode('.',$case,2);
        }
        $method = 'test'.ucfirst($case);
        $class = ucfirst($method).'Requirements';
        if( false && class_exists($class) && is_subclass_of($class,'iTestRequirements')) {
            //TODO allow use extra requirements
        }else {
            $instance = self::getInstance();
            if(!method_exists($instance,$method)){

                $method = 'testDefault';
                $subject = $case;
            }
            return $instance->{$method}($subject,$requirement);
        }

    }

    public function __call($name,$args)
    {
        if(preg_match('/^test(\w+)$/',$name,$matches)){
            throw new Exception(sprintf('Unsupported test case %s. Please update Installer.',$matches[1]));
        }else{
            throw new Exception(sprintf('Call undefined method %s at %s',$name,__CLASS__));
        }
    }

    private function app_version($app_id)
    {
        strtolower($app_id);
        $path = $this->root.'wa-apps/'.$app_id.'/lib/config/app.php';
        $build_path = $this->root.'wa-apps/'.$app_id.'/lib/config/build.php';
        $version = false;
        if(file_exists($path)){
            $data = include($path);
            if(is_array($data)){
                $version = isset($data['version'])?$data['version']:0;
                if(file_exists($build_path)){
                    if($build = include($build_path)){
                        $version .= ".{$build}";
                    }
                }
            }else{
                $version = 0;
            }
        }
        return $version;
    }

    private static function setDefaultDescription(&$requirement,$name = '',$description = '')
    {
        if(is_array($requirement)){
            if(!isset($requirement['name'])||!$requirement['name']){
                if(is_array($name)){
                    $name = array_map('_w',$name);
                    $requirement['name'] = call_user_func_array('sprintf',$name);
                }else{
                    $requirement['name'] = _w($name);
                }
            }
            if(!isset($requirement['description'])||!$requirement['description']){
                $requirement['description'] = _w($description);
            }
        }
    }

    private function testDefault($subject,&$requirement)
    {
        $requirement['passed'] = !$requirement['strict'];
        $requirement['note'] = false;
        $requirement['warning'] = _w('Please install updates for the proper verification requirements');
        self::setDefaultDescription($requirement,array('Unknown requirement case %s',$subject),'');
        return $requirement['passed'];
    }

    private function testPhpini($subject,&$requirement)
    {
        $requirement['passed'] = !$requirement['strict'];
        $requirement['note'] = false;
        $requirement['warning'] = false;
        if($subject){
            self::setDefaultDescription($requirement,array('PHP setting %s',$subject),'');
            $value = ini_get($subject);
            if(isset($requirement['value'])){
                if(strtolower($value) == 'on'){
                    $value = true;
                }elseif(strtolower($value) == 'off'){
                    $value = false;
                }
                if(preg_match('/^(<|<=|=|>|>=)(\d+.*)$/',$requirement['value'],$matches)){
                    $relevation = $matches[1];
                    $requirement['value'] = $matches[2];
                    if(!version_compare($value,$requirement['value'],$relevation)){
                        $format = $requirement['strict']?_w('setting has value %s but should be %s'):_w('setting has value %s but recommended %s');
                        $requirement['warning'] = sprintf($format,var_export($value, true),$relevation.$requirement['value']);
                    }else{
                        $requirement['passed'] = true;
                        if($value === true){
                            $requirement['note'] = 'On';
                        }elseif($value===false){
                            $requirement['note'] = 'Off';
                        }else{
                            $requirement['note'] = $value;
                        }
                    }
                }elseif($value != $requirement['value']){
                    $format = $requirement['strict']?_w('setting has value %s but should be %s'):_w('setting has value %s but recommended %s');
                    $requirement['warning'] = sprintf($format,var_export($value, true),$requirement['value']);
                }else{
                    $requirement['passed'] = true;
                    if($value === true){
                        $requirement['note'] = 'On';
                    }elseif($value===false){
                        $requirement['note'] = 'Off';
                    }else{
                        $requirement['note'] = $value;
                    }
                }
            }else{
                if($value === true){
                    $requirement['note'] = 'On';
                }elseif($value===false){
                    $requirement['note'] = 'Off';
                }else{
                    $requirement['note'] = $value;
                }
                $requirement['passed'] = true;
            }
        }else{

        }
        return $requirement['passed'];
    }


    private function testPhp($subject,&$requirement)
    {
        $requirement['passed'] = !$requirement['strict'];
        $requirement['note'] = false;
        $requirement['warning'] = false;
        if($subject){
            self::setDefaultDescription($requirement,array('PHP extension %s',$subject),'');
            if(extension_loaded($subject)){
                $version = phpversion($subject);
                if(isset($requirement['version'])){
                    $relevation = '>=';
                    if(preg_match('/^(<|<=|=|>|>=)(\d+.*)$/',$requirement['version'],$matches)){
                        $relevation = $matches[1];
                        $requirement['version'] = $matches[2];
                    }
                    if(!version_compare($version,$requirement['version'],$relevation)){
                        $format = $requirement['strict']?_w('extension %s has %s version but should be %s %s'):_w('extension %s has %s version but recomended is %s %s');
                        $requirement['warning'] = sprintf($format,$subject,$version,$relevation,$requirement['version']);
                    }else{
                        if($version){
                            $requirement['note'] = $version;
                        }
                        $requirement['passed'] = true;
                    }
                }else{
                    $requirement['passed'] = true;
                }
            }else{
                $requirement['warning'] = sprintf(_w('extension %s not loaded'),$subject);
            }
        }else{
            self::setDefaultDescription($requirement,'PHP version','');
            $version = PHP_VERSION;
            if(isset($requirement['version'])){
                $relevation = '>=';
                if(preg_match('/^(<|<=|=|>|>=)(\d+.*)$/',$requirement['version'],$matches)){
                    $relevation = $matches[1];
                    $requirement['version'] = $matches[2];
                }
                if(!version_compare($version,$requirement['version'],$relevation)){
                    $requirement['warning'] = sprintf(_w('PHP has version %s but should be %s %s'),$version,$relevation,$requirement['version']);
                }else{
                    if($version){
                        $requirement['note'] = $version;
                    }
                    $requirement['passed'] = true;
                }
            }else{
                $requirement['passed'] = true;
            }
        }
        return $requirement['passed'];
    }

    private function testApp($subject,&$requirement)
    {
        if(isset($requirement['update'])&&!$requirement['update']){
            $requirement['strict'] = false;
        }
        $requirement['passed'] = !$requirement['strict'];
        self::setDefaultDescription($requirement,array('Version of %s',ucfirst($subject)),'');
        $requirement['note'] = false;
        $requirement['warning'] = false;
        $version = $this->app_version($subject);
        if($version !== false){
            if(isset($requirement['version'])){
                $relevation = '>=';
                if(preg_match('/^(<|<=|=|>|>=)(\d+.*)$/',$requirement['version'],$matches)){
                    $relevation = $matches[1];
                    $requirement['version'] = $matches[2];
                }
                if(!version_compare($version,$requirement['version'],$relevation)){
                    $format = $requirement['strict']?_w('%s has %s version but should be %s %s'):_w('%s has %s version but recomended is %s %s');
                    $relevation = _w($relevation);
                    $requirement['warning'] = sprintf($format,_w(ucfirst($subject)),$version,$relevation,$requirement['version']);
                }else{
                    if($version){
                        $requirement['note'] = $version;
                    }
                    $requirement['passed'] = true;
                }
            }else{
                $requirement['passed'] = ($version === false)?false:true;
            }
        }else{
            $requirement['warning'] = sprintf(_w('%s not installed'),_w(ucfirst($subject)));
        }
    }

    private function testRights($folders,&$requirement)
    {
        $requirement['passed'] = true;
        $requirement['note'] = false;
        $requirement['warning'] = false;
        $folders = explode('|',$folders);
        $bad_folders = array();
        $good_folders = array();
        self::setDefaultDescription($requirement,'Files access rights');
        //TODO make it recursive
        foreach($folders as $folder){
            $path = $this->root.$folder;
            if(file_exists($path)){
                //XXX skip symbolic link
                if(is_writeable($path) || is_link($path)){
                    $good_folders[] = $folder;
                }else{
                    if($requirement['strict']){
                        $requirement['passed'] = false;
                    }
                    $bad_folders[] = $folder;
                }
            }else{

            }
        }

        if($bad_folders){
            $requirement['warning'] .= sprintf(_w('%s should be writeable'),implode(', ',$bad_folders));
        }

        if($good_folders){
            $requirement['note'] .= sprintf(_w('%s is writeable'),implode(', ',$good_folders));
        }
    }

    private function testServer($subject,&$requirement)
    {

        $requirement['passed'] = !$requirement['strict'];
        $requirement['note'] = false;
        $requirement['warning'] = false;
        $requirement['value'] = !$requirement['strict'];

        $server=($_SERVER['SERVER_SOFTWARE'].(isset($_SERVER['SERVER_SIGNATURE'])?$_SERVER['SERVER_SIGNATURE']:''));

        if($subject){//check server module
            self::setDefaultDescription($requirement,array('Server module %s',$subject));
            if(function_exists('apache_get_modules')){
                if(in_array($subject,apache_get_modules())){
                    $requirement['note'] = _w('server module loaded');
                    $requirement['passed'] = true;
                    $requirement['value'] = true;
                }else{
                    $requirement['warning'] = _w('server module not loaded');
                    $requirement['value'] = false;
                }
            }elseif(strpos(strtolower($server),'apache')===false){//CGI or non apache?
                $requirement['warning'] = _w('not Apache server');
                $requirement['value'] = false;
            }else{
                $requirement['warning'] = _w('CGI PHP mode');
            }
        }else{
            self::setDefaultDescription($requirement,'Server software version');
            if(function_exists('apache_get_version')){
                $requirement['note'] = apache_get_version();
            }else{
                $requirement['note'] = $server;
            }
            //TODO teset server software
        }
    }

    private function testMd5($subject,&$requirement)
    {

        $requirement['passed'] = !$requirement['strict'];
        $requirement['note'] = false;
        $requirement['warning'] = false;
        $md5_path = $this->root.'.files.md5';

        if($subject){//check files by mask
            self::setDefaultDescription($requirement,'Files checksum');
            $metacharacters = array('?','+','.','(',')','[',']','{','}','<','>','^','$','@');
            foreach($metacharacters as &$char){
                $char = "\\{$char}";
                unset($char);
            }
            $commandcharacters = array('?','*');

            foreach($commandcharacters as &$char){
                $char = "\\{$char}";
                unset($char);
            }

            $cleanup_pattern = '@({'.implode('|',$metacharacters).')@';
            $command_pattern = '@({'.implode('|',$commandcharacters).')@';
            $subject = preg_replace($cleanup_pattern,'\\\\$1',$subject);
            $subject = preg_replace($command_pattern,'.$1',$subject);
            $hash_pattern = "@^([\da-f]{32})\s+\*({$subject})$@m";
            if(file_exists($md5_path)){
                $hashes = file_get_contents($md5_path);
                if(preg_match_all($hash_pattern,$hashes,$file_matches)){
                    $requirement['passed'] = true;
                    foreach($file_matches[2] as $id=>$file){
                        $path = $this->root.$file;
                        if(file_exists($path)){
                            $md5_hash = md5_file($path);
                            if($file_matches[1][$id] != $md5_hash){
                                $requirement['warning'] .= "\n{$file} corrupted";
                                $requirement['passed'] = !$requirement['strict']&&$requirement['passed'];
                            }
                        }else{
                            $requirement['warning'] .= "\n{$file} missed";
                            $requirement['passed'] = !$requirement['strict']&&$requirement['passed'];
                        }
                    }
                }else{
                    $requirement['warning'] = 'local arhcives not founded';
                    $requirement['passed'] = true;
                }
            }else{
                if(isset($requirement['silent']) && $requirement['silent']) {
                    $requirement['note'] = '.files.md5 missed';
                } else {
                    $requirement['warning'] = '.files.md5 missed';
                }
                $requirement['passed'] = true;
            }
        }else{
            $requirement['note'] = 'unclomplete case';
            //TODO teset server software
        }
    }
}
?>