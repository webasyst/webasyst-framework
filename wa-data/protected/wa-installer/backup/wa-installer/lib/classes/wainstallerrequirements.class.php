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
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->root = dirname(__FILE__).'/../../../';
        $this->root = preg_replace('@([/\\\\]+)@', '/', $this->root);
        while (preg_match('@/\.\./@', $this->root)) {
            $this->root = preg_replace('@/[^/]+/\.\./@', '/', $this->root);
        }
    }

    private function __clone()
    {
        ;
    }

    /**
     *
     * Run test case
     * @param string $case
     * @param array $requirement
     */
    public static function test($case, &$requirement)
    {
        $subject = null;
        if (strpos($case, '.') !== false) {
            list($case, $subject) = explode('.', $case, 2);
        }
        if (empty($subject) && !empty($requirement['subject'])) {
            $subject = $requirement['subject'];
        }
        $method = 'test'.ucfirst($case);

        $instance = self::getInstance();
        if (!method_exists($instance, $method)) {
            $method = 'testDefault';
            $subject = $case;
        }
        return $instance->$method($subject, $requirement);
    }

    public function __call($name, $args)
    {
        if (preg_match('/^test(\w+)$/', $name, $matches)) {
            throw new Exception(sprintf('Unsupported test case %s. Please update Installer.', $matches[1]));
        } else {
            throw new Exception(sprintf('Call undefined method %s at %s', $name, __CLASS__));
        }
    }

    private function app_version($app_id)
    {
        $app_id = preg_replace('@\\/@', '', strtolower($app_id));
        $path = $this->root.'wa-apps/'.$app_id.'/lib/config/app.php';
        $build_path = $this->root.'wa-apps/'.$app_id.'/lib/config/build.php';
        $version = false;
        if (file_exists($path)) {
            $data = include($path);
            if (is_array($data)) {
                $version = isset($data['version']) ? $data['version'] : 0;
                if (file_exists($build_path)) {
                    if ($build = include($build_path)) {
                        $version .= ".{$build}";
                    }
                }
            } else {
                $version = 0;
            }
        }
        return $version;
    }

    private static function setDefaultDescription(&$requirement, $name = '', $description = '')
    {
        if (is_array($requirement)) {
            if (!isset($requirement['name']) || !$requirement['name']) {
                if (is_array($name)) {
                    $name = array_map('_w', $name);
                    $requirement['name'] = call_user_func_array('sprintf', $name);
                } else {
                    $requirement['name'] = _w($name);
                }
            }
            if (!isset($requirement['description']) || !$requirement['description']) {
                $requirement['description'] = _w($description);
            }
        }
    }

    private function testDefault($subject, &$requirement)
    {
        $requirement['passed'] = empty($requirement['strict']);
        $requirement['note'] = false;
        $requirement['warning'] = _w('Please install updates for the proper verification requirements');
        self::setDefaultDescription($requirement, array('Unknown requirement case %s', htmlentities($subject, ENT_QUOTES, 'utf-8'), ''));
        return $requirement['passed'];
    }

    private function testPhpini($subject, &$requirement)
    {
        $requirement['passed'] = empty($requirement['strict']);
        $requirement['note'] = false;
        $requirement['warning'] = false;
        if ($subject) {
            self::setDefaultDescription($requirement, array('PHP setting %s', htmlentities($subject, ENT_QUOTES, 'utf-8')), '');
            $value = ini_get($subject);
            if (isset($requirement['value'])) {
                if (strtolower($value) == 'on') {
                    $value = true;
                } elseif (strtolower($value) == 'off') {
                    $value = false;
                }
                $relation = $this->getRelation($requirement['value'], true);
                if ($relation) {
                    if (!version_compare($value, $requirement['value'], $relation)) {
                        $format = !empty($requirement['strict']) ? _w('setting has value %s but should be %s') : _w('setting has value %s but recommended %s');
                        $requirement['warning'] = sprintf($format, var_export($value, true), $relation.$requirement['value']);
                    } else {
                        $requirement['passed'] = true;
                        if ($value === true) {
                            $requirement['note'] = 'On';
                        } elseif ($value === false) {
                            $requirement['note'] = 'Off';
                        } else {
                            $requirement['note'] = $value;
                        }
                    }
                } elseif ($value != $requirement['value']) {
                    $format = !empty($requirement['strict']) ? _w('setting has value %s but should be %s') : _w('setting has value %s but recommended %s');
                    $requirement['warning'] = sprintf($format, var_export($value, true), $requirement['value']);
                } else {
                    $requirement['passed'] = true;
                    if ($value === true) {
                        $requirement['note'] = 'On';
                    } elseif ($value === false) {
                        $requirement['note'] = 'Off';
                    } else {
                        $requirement['note'] = $value;
                    }
                }
            } else {
                if ($value === true) {
                    $requirement['note'] = 'On';
                } elseif ($value === false) {
                    $requirement['note'] = 'Off';
                } else {
                    $requirement['note'] = $value;
                }
                $requirement['passed'] = true;
            }
        } else {

        }
        return $requirement['passed'];
    }

    private function testPhp($subject, &$requirement)
    {
        $requirement['passed'] = empty($requirement['strict']);
        $requirement['note'] = false;
        $requirement['warning'] = false;
        if ($subject) {
            self::setDefaultDescription($requirement, array('PHP extension %s', htmlentities($subject, ENT_QUOTES, 'utf-8')), '');
            if (extension_loaded($subject)) {
                $version = phpversion($subject);
                if (isset($requirement['version'])) {
                    $relation = $this->getRelation($requirement['version']);
                    if (!version_compare($version, $requirement['version'], $relation)) {
                        $format = !empty($requirement['strict']) ? _w('extension %s has %s version but should be %s %s') : _w('extension %s has %s version but recomended is %s %s');
                        $requirement['warning'] = sprintf($format, $subject, $version, $relation, $requirement['version']);
                    } else {
                        if ($version) {
                            $requirement['note'] = $version;
                        }
                        $requirement['passed'] = true;
                    }
                } else {
                    $requirement['passed'] = true;
                }
            } else {
                $requirement['warning'] = sprintf(_w('extension %s not loaded'), $subject);
            }
        } else {
            self::setDefaultDescription($requirement, 'PHP version', '');
            $version = PHP_VERSION;
            if (isset($requirement['version'])) {
                $relation = $this->getRelation($requirement['version']);
                if (!version_compare($version, $requirement['version'], $relation)) {
                    $requirement['warning'] = sprintf(_w('PHP has version %s but should be %s %s'), $version, $relation, $requirement['version']);
                } else {
                    if ($version) {
                        $requirement['note'] = $version;
                    }
                    $requirement['passed'] = true;
                }
            } else {
                $requirement['passed'] = true;
            }
        }
        return $requirement['passed'];
    }

    private function testApp($subject, &$requirement)
    {
        if (isset($requirement['update']) && !$requirement['update']) {
            $requirement['strict'] = false;
        }
        $requirement['passed'] = empty($requirement['strict']);
        self::setDefaultDescription($requirement, array('Version of %s', htmlentities(ucfirst($subject), ENT_QUOTES, 'utf-8')), '');
        $requirement['note'] = false;
        $requirement['warning'] = false;
        $version = $this->app_version($subject);
        if ($version !== false) {
            if (isset($requirement['version'])) {
                $relation = $this->getRelation($requirement['version']);
                if (!version_compare($version, $requirement['version'], $relation)) {
                    $format = !empty($requirement['strict']) ? _w('%s has %s version but should be %s %s') : _w('%s has %s version but recomended is %s %s');
                    $relation = _w($relation);
                    $requirement['warning'] = sprintf($format, _w(ucfirst($subject)), $version, $relation, $requirement['version']);
                } else {
                    if ($version) {
                        $requirement['note'] = $version;
                    }
                    $requirement['passed'] = true;
                }
            } else {
                $requirement['passed'] = ($version === false) ? false : true;
            }
        } else {
            $requirement['warning'] = sprintf(_w('%s not installed'), _w(ucfirst($subject)));
        }
    }

    private function testRights($folders, &$requirement)
    {
        $requirement['passed'] = true;
        $requirement['note'] = false;
        $requirement['warning'] = false;
        if (!is_array($folders)) {
            $folders = explode('|', $folders);
        }
        $bad_folders = array();
        $good_folders = array();
        self::setDefaultDescription($requirement, 'Files access rights');
        //TODO make it recursive
        foreach ($folders as $folder) {
            $path = $this->root.$folder;
            if (file_exists($path)) {
                //XXX skip symbolic link
                if (is_writeable($path) || is_link($path)) {
                    $good_folders[] = $folder;
                } else {
                    if (!empty($requirement['strict'])) {
                        $requirement['passed'] = false;
                    }
                    $bad_folders[] = $folder;
                }
            } else {

            }
        }

        if ($bad_folders) {
            $requirement['warning'] .= sprintf(_w('%s should be writable'), implode(', ', $bad_folders));
        }

        if ($good_folders) {
            $requirement['note'] .= sprintf(_w('%s is writable'), implode(', ', $good_folders));
        }
    }

    private function testServer($subject, &$requirement)
    {

        $requirement['passed'] = empty($requirement['strict']);
        $requirement['note'] = false;
        $requirement['warning'] = false;
        $requirement['value'] = empty($requirement['strict']);

        $server = (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '').(isset($_SERVER['SERVER_SIGNATURE']) ? $_SERVER['SERVER_SIGNATURE'] : '');

        if ($subject) { //check server module
            self::setDefaultDescription($requirement, array('Server module %s', htmlentities($subject, ENT_QUOTES, 'utf-8')));
            if (function_exists('apache_get_modules')) {
                if (in_array($subject, apache_get_modules())) {
                    $requirement['note'] = _w('server module loaded');
                    $requirement['passed'] = true;
                    $requirement['value'] = true;
                } else {
                    $requirement['warning'] = _w('server module not loaded');
                    $requirement['value'] = false;
                }
            } elseif (strpos(strtolower($server), 'apache') === false) { //CGI or non apache?
                $requirement['warning'] = _w('not Apache server');
                $requirement['value'] = false;
            } else {
                $requirement['warning'] = _w('CGI PHP mode');
            }
        } else {
            self::setDefaultDescription($requirement, 'Server software version');
            if (function_exists('apache_get_version')) {
                $requirement['note'] = apache_get_version();
            } else {
                $requirement['note'] = $server;
            }
        }
    }

    /**
     *
     * Verify MD5 hashes
     * @param string $pattern
     * @param array $requirement
     */
    private function testMd5($pattern, &$requirement)
    {

        $requirement['passed'] = empty($requirement['strict']);
        $requirement['note'] = false;
        $requirement['warning'] = false;
        $md5_path = $this->root.'.files.md5';

        if ($pattern) { //check files by mask

            self::setDefaultDescription($requirement, 'Files checksum');
            $metacharacters = array('?', '+', '.', '(', ')', '[', ']', '{', '}', '<', '>', '^', '$', '@');
            foreach ($metacharacters as & $char) {
                $char = "\\{$char}";
                unset($char);
            }
            $commandcharacters = array('?', '*');

            foreach ($commandcharacters as & $char) {
                $char = "\\{$char}";
                unset($char);
            }

            $cleanup_pattern = '@({'.implode('|', $metacharacters).')@';
            $command_pattern = '@({'.implode('|', $commandcharacters).')@';
            $pattern = preg_replace($cleanup_pattern, '\\\\$1', $pattern);
            $pattern = preg_replace($command_pattern, '.$1', $pattern);
            $hash_pattern = "@^([\da-f]{32})\s+\*({$pattern})$@m";
            if (file_exists($md5_path)) {
                $hashes = file_get_contents($md5_path);
                if (preg_match_all($hash_pattern, $hashes, $file_matches)) {
                    $requirement['passed'] = true;
                    foreach ($file_matches[2] as $id => $file) {
                        $path = $this->root.$file;
                        if (file_exists($path)) {
                            $md5_hash = md5_file($path);
                            if ($file_matches[1][$id] != $md5_hash) {
                                $requirement['warning'] .= "\n{$file} corrupted";
                                $requirement['passed'] = empty($requirement['strict']) && $requirement['passed'];
                            }
                        } else {
                            $requirement['warning'] .= "\n{$file} missing";
                            $requirement['passed'] = empty($requirement['strict']) && $requirement['passed'];
                        }
                    }
                } else {
                    $requirement['warning'] = 'local archives not found';
                    $requirement['passed'] = true;
                }
            } else {
                if (isset($requirement['silent']) && $requirement['silent']) {
                    $requirement['note'] = '.files.md5 missing';
                } else {
                    $requirement['warning'] = '.files.md5 missing';
                }
                $requirement['passed'] = true;
            }
        } else {
            $requirement['note'] = 'incomplete case';
        }
    }

    private function getRelation(&$version, $strict = false)
    {
        $relation = $strict ? false : '>=';
        if (preg_match('/^(<|<=|=|>|>=)\s*(\d+.*)$/', $version, $matches)) {
            $relation = $matches[1];
            $version = $matches[2];
        }
        return $relation;
    }
}
