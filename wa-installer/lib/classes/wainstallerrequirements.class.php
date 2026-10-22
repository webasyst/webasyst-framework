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
    protected const INTERNAL_REQUIREMENT_KEYS = ['passed', 'note', 'warning', 'update'];
    protected const DB_SERVER_NAMES = ['mariadb' => 'MariaDB', 'mysql' => 'MySQL'];

    protected $root;
    protected $wa_locale;

    /**
     *
     * @return waInstallerRequirements
     */
    protected static $instance;

    protected static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    protected function __construct()
    {
        $this->root = dirname(__FILE__) . '/../../../';
        $this->root = preg_replace('@([/\\\\]+)@', '/', $this->root);
        while (preg_match('@/\.\./@', $this->root)) {
            $this->root = preg_replace('@/[^/]+/\.\./@', '/', $this->root);
        }
        $this->wa_locale = new waInstallerLocale();
    }

    private function __clone()
    {
    }

    /**
     *
     * Run test case
     * @param string|int $case
     * @param array $requirement
     * @param int $level Deepness level
     * @throws Exception
     */
    public static function test($case, &$requirement, int $level = 0)
    {
        [$case, $subject, $requirement] = self::extractCaseAndSubject($case, $requirement, $level);

        $camel_case = preg_split('/[-_]/', $case);
        if ($camel_case) {
            $method = 'test' . implode('', array_map('ucfirst', $camel_case));
        } else {
            $method = 'test' . ucfirst($case);
        }

        $instance = self::getInstance();
        if (!method_exists($instance, $method)) {
            $method = 'testDefault';
            $subject = $case;
        }

        return $instance->$method($subject, $requirement);
    }

    /**
     *
     * @param string $subject
     * @param array $requirement
     * @return bool
     * @throws Exception
     */
    protected function testOneOf(string $subject, array &$requirement): bool
    {
        $internal_fields = [
            'passed'  => $requirement['passed'],
            'note'    => $requirement['note'],
            'warning' => $requirement['warning'],
            'update'  => $requirement['update']
        ];

        foreach ($requirement as $key => &$item) {
            if (in_array($key, self::INTERNAL_REQUIREMENT_KEYS, true)) {
                continue;
            }
            $item = array_merge($item, $internal_fields);
            self::test($key, $item, 1);
            $requirement['passed'] = $item['passed'];
            $requirement['note'] = $item['note'];
            $requirement['warning'] = $item['warning'];
            $requirement['update'] = $item['update'];
            if ($item['passed']) {
                break;
            }
        }
        unset($item);

        return $requirement['passed'];
    }

    /**
     * @param string $subject database server code (mysql, MariaDB e.t.c.)
     * @param array $requirement
     * @return bool
     */
    protected function testDb(string $subject, array &$requirement): bool
    {
        $is_strict = !empty($requirement['strict']);
        $requirement['passed'] = !$is_strict;
        $this->castDatabaseServer($subject, $requirement);

        $required_db_servers = array_map(function($v) {return strtolower($v); }, $requirement['server'] ?? []);

        ['version' => $db_server_version, 'server' => $db_server] = $this->detectDatabaseServer();
        self::setDefaultDescription($requirement, 'Database');
        if (!$required_db_servers || in_array($db_server,  $required_db_servers, true)) {
            $this->castVersion($requirement);
            if (isset($requirement['version'])) {
                $relation = $this->getRelation($requirement['version']);
                if (version_compare($db_server_version, $requirement['version'], $relation)) {
                    $requirement['passed'] = true;
                    $requirement['note'] = $db_server_version;
                } else {
                    $requirement['warning'] = $is_strict
                        ? $this->wa_locale->_('You use %s server version %s but version %s %s required')
                        : $this->wa_locale->_('You use %s server version %s but version %s %s recommended');
                    $requirement['warning'] = sprintf(
                        $requirement['warning'],
                        self::DB_SERVER_NAMES[$db_server] ?? $db_server,
                        $db_server_version,
                        $relation,
                        $requirement['version']
                    );
                }
            } else {
                $requirement['passed'] = true;
            }
            if ($requirement['passed'] && !empty($requirement['engine'])) {
                $this->castDatabaseTableEngine($requirement);
                $supported_engines = $this->getSupportedTableEngines();
                $required_engines_lc = array_map(function($val) { return strtolower($val); }, $requirement['engine']);
                $supported_engines_lc = array_map(function ($val) { return strtolower($val); }, $supported_engines);
                if (empty(array_intersect($required_engines_lc, $supported_engines_lc))) {
                    $requirement['passed'] = !$is_strict;
                    $requirement['warning'] = $is_strict
                        ? $this->wa_locale->_('Your %s server supports %s table engines but %s required')
                        : $this->wa_locale->_('Your %s server supports %s table engines but %s recommended');
                    $requirement['warning'] = sprintf(
                        $requirement['warning'],
                        self::DB_SERVER_NAMES[$db_server] ?? $db_server,
                        $this->stringToList($supported_engines),
                        $this->stringToList($requirement['engine'], $this->wa_locale->_('or'))
                    );
                }
            }
        } else {
            $requirement['warning'] = sprintf(
                $is_strict
                    ? $this->wa_locale->_('Your database server is %s but %s required')
                    : $this->wa_locale->_('Your database server is %s but %s recommended'),
                self::DB_SERVER_NAMES[$db_server] ?? $db_server,
                $this->stringToList($requirement['server'], $this->wa_locale->_('or'))
            );
        }

        return $requirement['passed'];
    }

    /**
     * Implode array items into the string, where two last items are joined with $and
     *
     * @param array $list
     * @param string|null $and
     * @param string $separator
     * @return string
     * @example ['a', 'b', 'c', 'd'] => 'a, b, c and d'
     *
     */
    protected function stringToList(array $list, ?string $and = null, string $separator = ', '): string
    {
        if ($and === null) {
            $and = $this->wa_locale->_('and');
        }
        if (count($list) > 1) {
            return implode($separator, array_slice($list, 0, -1)) . ' ' . $and . ' ' . array_pop($list);
        }

        return $list ? array_pop($list) : '';
    }

    protected function castDatabaseServer(string $subject, &$requirement): void
    {
        $server = null;

        if ($subject) {
            $server = [trim($subject)];
        } elseif (!empty($requirement['server'])) {
            if (is_string($requirement['server'])) {
                $server = explode(',', $requirement['server']);
            } elseif (is_array($requirement['server'])) {
                $server = $requirement['server'];
            }
        }

        if ($server) {
            $server = array_map(function($v) { return trim($v); }, $server);
            $requirement['server'] = array_values($server);
        }
    }

    /**
     * Cast a list of required db table engines to the unified array
     *
     * @param array $requirement
     * @return void
     */
    protected function castDatabaseTableEngine(array &$requirement): void
    {
        if (isset($requirement['engine'])) {
            if (is_string($requirement['engine'])) {
                $engines = explode(',', $requirement['engine']);
                $requirement['engine'] = array_map(function($e) { return trim($e); }, $engines);
            }
        }
    }

    /**
     * List supported database table engines like MyISAM, InnoDB
     * @return array
     */
    protected function getSupportedTableEngines(): array
    {
        $engines = [];
        try {
            if (class_exists('waModel')) {
                $engines = (new waModel)->query('SHOW ENGINES')->fetchAll();
                $engines = array_filter($engines, function($e) { return in_array(strtolower($e['Support']), ['yes', 'default'], true); });
            }
        } catch (waDbException $e) {
            // db not set up yet, can't check
        }

        return array_values(array_map(function($e) { return $e['Engine']; }, $engines));
    }

    /**
     * Currently we don't differ mysql and percona engines
     *
     * @return array{version:string, server:string}
     */
    protected function detectDatabaseServer(): array
    {
        try {
            if (class_exists('waModel')) {
                $database_data = (new waModel)->query('SELECT VERSION() AS `version`, @@version_comment AS `version_comment`')->fetchAssoc();
            }
        } catch (waDbException $e) {
            // db not set up yet, can't check
        }

        return [
            'version' => $database_data['version'] ?? '',
            'server'  => stripos($database_data['version_comment'] ?? '', 'mariadb') === false ? 'mysql' : 'mariadb'
        ];
    }

    /**
     * @param $case
     * @param $requirement
     * @param $level
     * @return array
     * @throws Exception
     */
    protected static function extractCaseAndSubject($case, $requirement, $level = 0): array
    {
        $subject = '';

        if (is_numeric($case)) {
            $is_one_of = !$level
                && is_array($requirement)
                && !array_filter($requirement, function($value, $key) {
                    return (
                        !is_numeric($key) && !in_array($key, self::INTERNAL_REQUIREMENT_KEYS, true)
                    ) || (
                        is_numeric($key) && !is_array($value)
                    );
                }, ARRAY_FILTER_USE_BOTH);

            if ($is_one_of) {
                $case = 'one_of';
            } else {
                if (isset($requirement[0]) && is_scalar($requirement[0])) {
                    $case = $requirement[0];
                    if (isset($requirement[1]) && is_array($requirement[1])) {
                        // e.g. ['db.mariadb', ['version' => '>=10']]
                        // replace $requirements but keep all keys of root array except 0 and 1 (there can be non-numeric keys)
                        $requirement = $requirement[1] + array_diff_key($requirement, ['', '']);
                    } else {
                        // e.g. ['php', 'version' => '>=7.4']
                        unset($requirement[0]);
                    }
                } else {
                    $case = '';
                }
                return self::extractCaseAndSubject($case, $requirement, $level);
            }
        } elseif (is_string($case)) {
            if (strpos($case, '.') !== false) {
                [$case, $subject] = explode('.', $case, 2);
            }
        } else {
            throw new Exception('Invalid requirement format');
        }

        if (!$subject && !empty($requirement['subject'])) {
            $subject = $requirement['subject'];
        }

        return [$case, $subject, $requirement];
    }

    public function __call($name, $args)
    {
        if (preg_match('/^test(\w+)$/', $name, $matches)) {
            throw new Exception(sprintf('Unsupported test case %s. Please update Installer.', $matches[1]));
        } else {
            throw new Exception(sprintf('Call undefined method %s at %s', $name, __CLASS__));
        }
    }

    protected function getAppsConfig()
    {
        static $apps;
        if ($apps === null) {
            $apps = array();
            if (class_exists('waConfig')) {
                $path = waConfig::get('wa_path_config');
                if ($path && file_exists($path . '/apps.php')) {
                    $apps = include($path . '/apps.php');
                }
            }
        }
        return $apps;
    }

    /**
     * @param $app_id
     * @return bool|string
     */
    protected function appVersion($app_id)
    {
        $app_id = preg_replace('@\\/@', '', strtolower($app_id));
        $path = $this->root . 'wa-apps/' . $app_id . '/lib/config/app.php';
        $build_path = $this->root . 'wa-apps/' . $app_id . '/lib/config/build.php';
        $version = false;
        if (file_exists($path)) {
            $apps = $this->getAppsConfig();
            if (empty($apps) || !empty($apps[$app_id])) {
                $data = include($path);
                if (is_array($data)) {
                    $version = isset($data['version']) ? $data['version'] : 0;
                    if (file_exists($build_path)) {
                        $build = include($build_path);
                        if ($build) {
                            $version .= ".{$build}";
                        }
                    }
                } else {
                    $version = 0;
                }
            }
        }
        return $version;
    }

    protected static function setDefaultDescription(&$requirement, $name = '', $description = '')
    {
        if (is_array($requirement)) {
            if (!isset($requirement['name']) || !$requirement['name']) {
                if (is_array($name)) {
                    $name = array_map('_w', $name);
                    $args = $name;
                    $format = array_shift($args);
                    $formatted = @vsprintf($format, $args);
                    if ($formatted !== false) {
                        $requirement['name'] = $formatted;
                    } else {
                        $requirement['name'] = $format;
                    }
                } else {
                    $requirement['name'] = _w($name);
                }
            }
            if (!isset($requirement['description']) || !$requirement['description']) {
                $requirement['description'] = _w($description);
            }
        }
    }

    protected function testDefault($subject, &$requirement)
    {
        $requirement['passed'] = empty($requirement['strict']);
        $requirement['note'] = false;
        $requirement['warning'] = $this->wa_locale->_('Please install updates for the proper verification requirements');
        self::setDefaultDescription($requirement, array('Unknown requirement case %s', htmlentities($subject, ENT_QUOTES, 'utf-8'), ''));
        return $requirement['passed'];
    }

    protected function testPhpini($subject, &$requirement)
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
                        $format = $this->wa_locale->_('Value of PHP configuration parameter %s: %s. Required value: %s.');
                        if (empty($requirement['strict'])) {
                            $format = $this->wa_locale->_('Value of PHP configuration parameter %s: %s. Recommended value: %s.');
                        }
                        $requirement['warning'] = sprintf($format, $subject, $value, $relation . $requirement['value']);
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
                    $format = $this->wa_locale->_('Value of PHP configuration parameter %s: %s. Required value: %s.');
                    if (empty($requirement['strict'])) {
                        $format = $this->wa_locale->_('Value of PHP configuration parameter %s: %s. Recommended value: %s.');
                    }
                    $requirement['warning'] = sprintf($format, $subject, $value, $requirement['value']);
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

    protected function testPhp($subject, &$requirement)
    {
        $requirement['passed'] = empty($requirement['strict']);
        $requirement['note'] = false;
        $requirement['warning'] = false;
        $this->castVersion($requirement);

        if ($subject) {
            self::setDefaultDescription($requirement, array('PHP extension %s', htmlentities($subject, ENT_QUOTES, 'utf-8')), '');
            if (extension_loaded($subject)) {
                $version = phpversion($subject);
                if (isset($requirement['version'])) {
                    $requirement['relation'] = $this->getRelation($requirement['version']);
                    if (!version_compare($version, $requirement['version'], $requirement['relation'])) {
                        if (!empty($requirement['strict'])) {
                            $format = $this->wa_locale->_('extension %s has %s version but should be %s %s');
                        } else {
                            $format = $this->wa_locale->_('extension %s has %s version but recommended is %s %s');
                        }

                        $requirement['warning'] = sprintf($format, $subject, $version, $requirement['relation'], $requirement['version']);
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
                $requirement['warning'] = sprintf($this->wa_locale->_('PHP extension %s is required'), $subject);
            }
        } else {
            self::setDefaultDescription($requirement, 'PHP version', '');
            $version = PHP_VERSION;
            if (isset($requirement['version'])) {
                $requirement['relation'] = $this->getRelation($requirement['version']);
                if (!version_compare($version, $requirement['version'], $requirement['relation'])) {
                    $requirement['warning'] = sprintf($this->wa_locale->_('PHP has version %s but should be %s %s'), $version, $requirement['relation'], $requirement['version']);
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

    protected function testApp($subject, &$requirement)
    {
        if (isset($requirement['update']) && !$requirement['update']) {
            $requirement['strict'] = false;
        }
        $this->castVersion($requirement);

        $app_name = !empty($requirement['name']) ? $requirement['name'] : ucfirst($subject);
        $requirement['passed'] = empty($requirement['strict']);
        self::setDefaultDescription($requirement, array('Version of %s', htmlentities($app_name, ENT_QUOTES, 'utf-8')), '');
        $requirement['note'] = false;
        $requirement['warning'] = false;

        $version = $this->appVersion($subject);
        if ($version !== false) {
            if (isset($requirement['version'])) {
                $requirement['relation'] = $this->getRelation($requirement['version']);
                if (!version_compare($version, $requirement['version'], $requirement['relation'])) {
                    $format = !empty($requirement['strict']) ? $this->wa_locale->_('%s has %s version but should be %s %s') : $this->wa_locale->_('%s has %s version but recommended is %s %s');
                    $relation = $this->wa_locale->_($requirement['relation']);
                    $name = $subject == 'installer' ? $this->wa_locale->_('Webasyst Framework') : $app_name;
                    $requirement['warning'] = sprintf($format, $name, $version, $relation, $requirement['version']);
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
            $requirement['warning'] = sprintf($this->wa_locale->_('%s not installed'), $app_name);
        }
        return $requirement['passed'];
    }

    protected function testRights($folders, &$requirement)
    {
        $requirement['passed'] = true;
        $requirement['note'] = false;
        $requirement['warning'] = false;
        if (!is_array($folders)) {
            $folders = explode('|', $folders);
        }
        $bad_folders = array();
        $good_folders = array();
        $root_path = sprintf('<span style="color:#ccc;">%s</span>', rtrim($this->root, '\\/'));
        self::setDefaultDescription($requirement, 'Files access rights');
        //TODO make it recursive
        foreach ($folders as $folder) {
            $path = $this->root . $folder;
            $folder_name = $root_path . preg_replace('@^\.?/?@', '/', $folder);

            if (file_exists($path)) {
                //XXX skip symbolic link
                if (is_writeable($path) || is_link($path)) {
                    $good_folders[] = $folder_name;
                } else {
                    if (!empty($requirement['strict'])) {
                        $requirement['passed'] = false;
                    }
                    $bad_folders[] = $folder_name;
                }
            } else {

            }
        }

        if ($bad_folders) {
            $requirement['warning'] .= '<div><div>'.sprintf($this->wa_locale->_('%s should be writable'), implode('</div><div>', $bad_folders)).'</div></div>';
        }

        if ($good_folders) {
            $requirement['note'] .= '<div><div>'.sprintf($this->wa_locale->_('%s is writable'), implode('</div><div>', $good_folders)).'</div></div>';
        }
        return $requirement['passed'];
    }

    protected function testServer($subject, &$requirement)
    {
        $requirement['passed'] = empty($requirement['strict']);
        $requirement['note'] = false;
        $requirement['warning'] = false;
        $requirement['value'] = empty($requirement['strict']);

        $server = (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '') . (isset($_SERVER['SERVER_SIGNATURE']) ? $_SERVER['SERVER_SIGNATURE'] : '');

        if ($subject) { //check server module
            self::setDefaultDescription($requirement, array('Server module %s', htmlentities($subject, ENT_QUOTES, 'utf-8')));
            if (function_exists('apache_get_modules')) {
                if (in_array($subject, apache_get_modules())) {
                    $requirement['note'] = $this->wa_locale->_('server module loaded');
                    $requirement['passed'] = true;
                    $requirement['value'] = true;
                } else {
                    $requirement['warning'] = $this->wa_locale->_('server module not loaded');
                    $requirement['value'] = false;
                }
            } elseif (strpos(strtolower($server), 'apache') === false) { //CGI or non apache?
                $requirement['warning'] = $this->wa_locale->_('not Apache server');
                $requirement['value'] = false;
            } else {
                $requirement['warning'] = $this->wa_locale->_('CGI PHP mode');
            }
        } else {
            self::setDefaultDescription($requirement, 'Server software version');
            if (function_exists('apache_get_version')) {
                $requirement['note'] = apache_get_version();
            } else {
                $requirement['note'] = $server;
            }
        }
        return $requirement['passed'];
    }

    /**
     *
     * Verify MD5 hashes
     * @param string $pattern
     * @param array $requirement
     */
    protected function testMd5($pattern, &$requirement)
    {

        $requirement['passed'] = empty($requirement['strict']);
        $requirement['note'] = false;
        $requirement['warning'] = false;
        $md5_path = $this->root . '.files.md5';

        if ($pattern) { //check files by mask

            self::setDefaultDescription($requirement, 'Files checksum');
            $meta_characters = array('?', '+', '.', '(', ')', '[', ']', '{', '}', '<', '>', '^', '$', '@');
            foreach ($meta_characters as & $char) {
                $char = "\\{$char}";
                unset($char);
            }
            $command_characters = array('?', '*');

            foreach ($command_characters as & $char) {
                $char = "\\{$char}";
                unset($char);
            }

            $cleanup_pattern = '@({' . implode('|', $meta_characters) . ')@';
            $command_pattern = '@({' . implode('|', $command_characters) . ')@';
            $pattern = preg_replace($cleanup_pattern, '\\\\$1', $pattern);
            $pattern = preg_replace($command_pattern, '.$1', $pattern);
            $hash_pattern = "@^([\\da-f]{32})\\s+\\*({$pattern})$@m";
            if (file_exists($md5_path)) {
                $hashes = file_get_contents($md5_path);
                if (preg_match_all($hash_pattern, $hashes, $file_matches)) {
                    $requirement['passed'] = true;
                    foreach ($file_matches[2] as $id => $file) {
                        $path = $this->root . $file;
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
        return $requirement['passed'];
    }

    protected function testCloud($subject, &$requirement)
    {
        $requirement['passed'] = empty($requirement['strict']);

        $target = $requirement['passed'] ? 'note' : 'warning';
        $requirement[$target] = isset($requirement['name']) ? $requirement['name'] : $subject;
        if (!empty($requirement['description'])) {
            $requirement[$target] .= sprintf(': %s', $requirement['description']);
        }

        return $requirement['passed'];
    }

    protected function testExpired($subject, &$requirement)
    {
        $requirement['passed'] = empty($requirement['strict']);
        $target = $requirement['passed'] ? 'note' : 'warning';
        $requirement[$target] = isset($requirement['name']) ? $requirement['name'] : $subject;
        if (!empty($requirement['description'])) {
            $requirement[$target] .= sprintf(': %s', $requirement['description']);
        }

        return $requirement['passed'];
    }

    protected function getRelation(&$version, $strict = false)
    {
        $relation = $strict ? false : '>=';
        if (preg_match('/^(<|<=|=|>|>=)\s*(\d+.*)$/', $version, $matches)) {
            $relation = $matches[1];
            $version = $matches[2];
        }
        return $relation;
    }

    protected function castVersion(&$requirement)
    {
        if (!isset($requirement['version'])
            && isset($requirement['value'])
            && preg_match('@^([<>]?=|[<=>])@', $requirement['value'])
        ) {
            $requirement['version'] = $requirement['value'];
        }
    }
}
