<?php


class webasystCompressCli extends waCliController
{
    private $type;
    private $app_id;
    private $extension_id;
    private $folder;

    private $files = array();

    private $path;
    private $archive_path;

    private $params = array();

    private static $default = array(
        'style' => 'no-vendors',
        'skip'  => 'none',
    );

    /**
     * @var array
     */
    private $config;

    protected function preExecute()
    {
        if (class_exists('waAutoload')) {
            if (file_exists(wa()->getConfig()->getPath('installer'))) {
                waAutoload::getInstance()->add('Archive_Tar', '/wa-installer/lib/vendors/PEAR/Tar.php');
                waAutoload::getInstance()->add('PEAR', '/wa-installer/lib/vendors/PEAR/PEAR.php');
            }
        }
    }

    private function printHelp()
    {

        if (preg_match('/^webasyst(\w+)Cli$/', __CLASS__, $matches)) {
            $callback = create_function('$m', 'return strtolower($m[1]);');
            $action = preg_replace_callback('/^([\w]{1})/', $callback, $matches[1]);
        } else {
            $action = '';
        }
        $help = <<<HELP
Usage: php wa.php {$action} slug [params]
Slug examples:
    myapp
    someapp/plugins/myplugin
    someapp/widgets/mywidget
    someapp/themes/mytheme
    wa-plugins/payment/myplugin
    wa-plugins/shipping/myplugin
    wa-plugins/sms/myplugin
Optional parameters:
    -style true|false|no-vendors Options for code style checks:
        true         Check all code.
        false        Disable code checking.
        report       Use non-interactive checks
        no-vendors   (default choice) Check all code except for lib/vendors/ and js/vendors/ directories, if available.
    -skip compress|test|all|none Options to skip some operations:
        compress     Do not compress source files to archive.
        test         Skip minimal checking: routing setup, database configuration file.
        all          Skip all above.
        none         (default choice) Do not skip anything.
    -php /path/to/php/bin Option to specify custom path to check php syntax
HELP;

        print($help)."\n";
    }

    public function execute()
    {
        $slug = waRequest::param(0);
        $this->params = waRequest::param();
        $id_pattern = '[a-z][a-z0-9_]+';
        try {
            if (empty($this->params) || isset($this->params['help']) || empty($slug)) {
                $this->printHelp();
            } else {
                if (preg_match("@^({$id_pattern})($|/(plugins|widgets|themes)/({$id_pattern})$)@", $slug, $matches)) {
                    $this->type = ifset($matches[3], 'app');
                    $this->app_id = $matches[1];
                    $this->extension_id = ifset($matches[4]);
                } elseif (preg_match("@^(wa-plugins/(payment|shipping|sms))/({$id_pattern})$@", $slug, $matches)) {
                    $this->app_id = $matches[1];
                    $this->type = $matches[2];
                    $this->extension_id = $matches[3];
                } else {
                    throw new Exception("invalid SLUG");
                }

                $this->tracef('Check & compress %s with params:', $slug);

                foreach (self::$default as $param => $default) {
                    $this->tracef("\t%s\t%s", $param, implode(', ', $this->getParam($param)));
                }


                $this->tracef('PHP version %s', phpversion());

                $skipped = $this->initPath();
                if ($skipped) {
                    $this->trace(str_repeat('-', 80));
                    $this->tracef("IGNORE %d FILE(S)", count($skipped));
                    $count = 0;
                    $this->trace(str_repeat('-', 80));
                    foreach ($skipped as $file => $description) {
                        $this->tracef('%3s | %-40s | %s', ++$count, $file, $description);
                    }
                    $this->trace(str_repeat('-', 80));
                }


                $compress = true;
                $skip = ifset($this->params['skip'], '');

                if (!in_array($skip, array('test', 'all'), true)) {
                    //test minimal requirements
                    $compress = $this->test();
                    if ($compress) {
                        $this->trace('Config check OK');
                    }
                } else {
                    $this->trace('Config check skipped');
                }


                if ($compress && ('theme' != $this->type)) {
                    $style = $this->getParam('style');

                    if (!in_array('false', $style, true)) {
                        $count = $this->codeStyle($style);
                        if ($count === false) {
                            $this->trace('Code style check skipped, try to use internal checks');
                            $compress = $this->checkCode() && $compress;
                        } elseif ($count) {
                            $compress = false;
                            if (!in_array('report', $style, true)) {
                                $this->tracef('Code style check skipped %d errors', $count);
                            } else {
                                $this->tracef('Code style check detect %d errors', $count);
                            }
                        } else {
                            $this->trace('Code style check OK');
                        }

                    } else {
                        $this->trace('Code style check skipped');
                    }
                }

                if ($compress && !in_array($skip, array('compress', 'all'), true)) {
                    $this->trace();
                    $this->compress();
                    if ($skipped) {
                        $this->trace(str_repeat('-', 80));
                        $this->tracef("NOTICE: Archive created without %d file(s)", count($skipped));
                        $map = array();
                        foreach ($skipped as $file => $description) {
                            if (!isset($map[$description])) {
                                $map[$description] = 0;
                            }
                            ++$map[$description];
                        }

                        $this->trace(str_repeat('-', 80));
                        $line = 0;
                        $this->tracef('%2s | %-65s | %s', '##', 'DESCRIPTION', 'count');
                        $this->trace(str_repeat('-', 80));
                        foreach ($map as $description => $count) {
                            $this->tracef('%02d | %-65s | %d', ++$line, $description, $count);
                        }
                        $this->trace(str_repeat('-', 80));
                    }
                } elseif ($compress) {
                    $this->trace('Test completed');
                } else {
                    exit(1);
                }
            }
        } catch (Exception $ex) {
            $this->tracef("ERROR: %s\n\n", $ex->getMessage());
            $this->printHelp();
            exit(-1);
        }
    }

    /**
     * @param string $name
     * @return array
     */
    private function getParam($name)
    {
        return array_map('trim', explode(',', ifset($this->params[$name], ifset(self::$default[$name]))));
    }

    private function initPath()
    {

        $type = preg_replace('@(plugin|widget|theme|app)s$@', '$1', $this->type);
        switch ($type) {
            case 'app':
                $namespace = $this->app_id;
                $this->path = wa()->getConfig()->getAppsPath($this->app_id, null);
                break;
            case 'widget': #TODO #51.808
            case 'plugin':
            case 'theme':
                $namespace = $this->app_id.ucfirst($this->extension_id);

                $this->path = wa()->getConfig()->getAppsPath($this->app_id, $this->type.'/'.$this->extension_id);
                break;
            case 'shipping':
            case 'payment':
                $namespace = $this->extension_id.ucfirst($this->type);
                $this->path = wa()->getConfig()->getPath('plugins').'/'.$this->type.'/'.$this->extension_id;
                $type = 'plugin';
                break;
            case 'sms':
                $namespace = $this->extension_id.strtoupper($this->type);
                $this->path = wa()->getConfig()->getPath('plugins').'/'.$this->type.'/'.$this->extension_id;
                $type = 'plugin';
                break;
            default:
                throw new waException('Unknown type '.$this->type);
        }
        if (!file_exists($this->path) || !is_dir($this->path)) {
            throw new waException('Invalid SLUG: path not found');
        }
        $this->type = $type;
        $this->folder = $this->extension_id ? $this->extension_id : $this->app_id;
        $this->archive_path = $this->path.'/'.$this->folder.'.tar.gz';

        $this->config = $this->getItemConfig($this->type);
        $this->files = waFiles::listdir($this->path, true);
        sort($this->files);
        $blacklist = array();
        if ($this->type == 'app') {
            $blacklist['@^plugins/.+@'] = "application's plugins";
            if (true) {
                $blacklist['@^themes/(?!default).+@'] = "application's themes";
            }
            $blacklist['@^widgets/.+@'] = "application's widgets";
        }

        $whitelist = array();

        # use .gitignore
        if (file_exists($this->path.'/.gitignore')) {
            $rules = $this->parseGitignore($this->path.'/.gitignore');
            $blacklist = array_merge($rules['blacklist'], $blacklist);
            $whitelist = array_merge($rules['whitelist'], $whitelist);
        }

        waRequest::setParam(
            array(
                'namespace' => $namespace,
                'prefix'    => isset($this->config['prefix']) ? $this->config['prefix'] : null,
                'type'      => $this->type,
                'app'       => $this->app_id,
                'app_id'    => $this->app_id,
                'extension' => $this->extension_id,
            )
        );
        return $this->filter($this->files, $blacklist, $whitelist);
    }

    private function parseGitignore($file)
    {
        $blacklist = array();
        $whitelist = array();

        $description = 'Gitignore rule ';

        $lines = file($file);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                # empty line
                continue;
            }
            if (substr($line, 0, 1) == '#') {
                # a comment
                continue;
            }
            if (substr($line, 0, 1) == '!') {
                $line = substr($line, 1);
                $whitelist[self::getGitPattern($line)] = $description.'!'.$line;
            } else {
                $blacklist[self::getGitPattern($line)] = $description.$line;
            }

        }
        return compact('blacklist', 'whitelist');
    }

    private static function getGitPattern($pattern)
    {
        $pattern = preg_replace('@^/\*\*/@', '', $pattern);
        $pattern = preg_replace('@^/@', '^', $pattern);
        $pattern = preg_replace('@/\*\*/@', '([^/]+/){0,}', $pattern);
        $pattern = preg_replace('@\*@', '.*', $pattern);
        return "@{$pattern}@";
    }

    private function checkConfig($path)
    {
        $result = true;
        $name = '/lib/config/'.$path.'.php';
        $config_path = $this->path.$name;
        if (file_exists($config_path)) {
            $content = file_get_contents($config_path);
            $tokens = token_get_all($content);
            foreach ($tokens as $id => $token) {
                if (is_array($token) && (in_array($token[0], array(T_FUNC_C, T_FUNCTION, T_CLASS, T_CLASS_C)))) {
                    $result = false;
                    $this->tracef('ERROR: Unexpected %s at config file %s on line', token_name($token[0]), $name, $token[2]);
                    break;
                }
            }
        }
        return $result;
    }

    private function getItemConfig($path)
    {
        $config = null;
        $name = '/lib/config/'.$path.'.php';
        $config_path = $this->path.$name;
        if (file_exists($config_path)) {
            if ($this->checkConfig($path)) {
                $config = array();
                if (file_exists($config_path)) {
                    $config = include($config_path);
                    if (!is_array($config)) {
                        $this->tracef('ERROR: Invalid or empty config %s', $name);
                        $config = false;
                    } else {
                        //TODO check icon path #51.795
                    }
                }
            }
        }
        return $config;
    }

    /**
     * @link http://www.webasyst.ru/developers/docs/basics/naming-rules/
     * @param string[] $param
     * @return false|int
     */
    private function codeStyle($param)
    {
        @include_once 'PHP/CodeSniffer.php';
        if (!class_exists('PHP_CodeSniffer')) {
            $this->trace('WARNING: Code style check skipped:');
            $this->trace('         PEAR extension CodeSniffer required');
            return false;
        }

        error_reporting(E_ALL | E_STRICT);
        $files = array();
        $ext = array(
            'php',
            'js',
            //'html',
            'css',
        );
        foreach ($this->files as $file) {
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), $ext)) {
                if (in_array('no-vendors', $param, true) && preg_match('@^(lib/|js/)?vendors?/@', $file)) {
                    continue;
                }
                if (preg_match('@(js|css)/compiled/.+\.(js|css)$@', $file)) {
                    continue;
                }
                if (preg_match('@\.min\.(js|css)$@', $file)) {
                    continue;
                }
                $files[] = $this->path.'/'.$file;
            }
        }

        $count = false;

        try {
            $count = $this->process($files, !in_array('report', $param, true));
        } catch (Exception $ex) {
            $this->tracef("ERROR: %s\n\n", $ex->getMessage());
        }
        return $count;
    }

    private function process($files, $interactive = true)
    {
        $standards = 'Webasyst';
        if (PHP_CodeSniffer::isInstalledStandard($standards) === false) {
            $this->tracef('WARNING: %s standard not found, will used PSR2. Some rules are differ', $standards);
            $standards = 'PSR2';
        }
        if (is_array($standards) === false) {
            $standards = array($standards);
        }

        $sniffer = new PHP_CodeSniffer(0, 4, 'UTF-8', $interactive);
        // Set file extensions if they were specified. Otherwise,
        // let PHP_CodeSniffer decide on the defaults.
        if (true) {
            $extensions = array(
                'php',
                // 'html',
                'js',
                'css',
            );
            $sniffer->setAllowedFileExtensions($extensions);
        }

        if (is_array($files) === false) {
            $files = array($files);
        }


        // Reset the members.


        // Ensure this option is enabled or else line endings will not always
        // be detected properly for files created on a Mac with the /r line ending.
        ini_set('auto_detect_line_endings', true);

        $sniffs = array();
        foreach ($standards as $standard) {
            $installed = $sniffer->getInstalledStandardPath($standard);
            if ($installed !== null) {
                $standard = $installed;
            } else {
                if (is_dir($standard) === true
                    && is_file(realpath($standard.'/ruleset.xml')) === true
                ) {
                    $standard = realpath($standard.'/ruleset.xml');
                }
            }
            $sniffs = array_merge($sniffs, $sniffer->processRuleset($standard));
        }
        //end foreach

        $sniffRestrictions = array();

        $sniffer->registerSniffs($sniffs, $sniffRestrictions);
        $sniffer->populateTokenListeners();

        // The SVN pre-commit calls process() to init the sniffs
        // and ruleset so there may not be any files to process.
        // But this has to come after that initial setup.

        //define('PHP_CODESNIFFER_IN_TESTS',true);
        $_SERVER['argc'] = 0;
        $errors_count = 0;
        foreach ($files as $file) {
            $sniffer_file = $sniffer->processFile($file);
            // Show progress information.
            if ($sniffer_file !== null) {
                $count = ($sniffer_file->getErrorCount() + $sniffer_file->getWarningCount());
                if (!$interactive && $count) {
                    $report = array(
                        'ERROR'   => $sniffer_file->getErrors(),
                        'WARNING' => $sniffer_file->getWarnings(),
                    );
                    $this->tracef("\nFILE: %s", str_replace($this->path.'/', '', $file));
                    $this->trace(str_repeat('-', 80));
                    foreach ($report as $type => $errors) {
                        foreach ($errors as $line => $line_errors) {
                            foreach ($line_errors as $column => $errors) {
                                foreach ($errors as $error) {
                                    $this->tracef('%4d | %s | %s', $line, $type, $error['message']);
                                }
                            }
                        }
                    }
                    $this->trace(str_repeat('-', 80));
                }

                $errors_count += $count;
            }
        }
        return $errors_count;
    }

    private function test()
    {
        $result = true;


        switch ($this->type) {
            case 'theme':

                break;
            default:
                # 1. Check settings
                $result = $this->testConfig() && $result;
                # 1.1 Routing
                $result = $this->testRouting() && $result;
                $result = $this->testRequirements() && $result;
                # 1.2 themes
                # 1.3 plugins
                # 2. Check PHP code
                $result = $this->testPhp() && $result;
                break;
        }


        # 4 Table names
        if (in_array($this->type, array('app', 'plugin'))) {
            $result = $this->testDb() && $result;

            $result = $this->testInstall() && $result;
        }


        return $result;
    }

    private function testRequirements()
    {
        $result = true;
        $extensions = array();
        $requirements = $this->getItemConfig('requirements');
        if ($requirements) {
            foreach ($requirements as $requirement => $requirement_info) {
                if (preg_match('@^php\.(.+)$@', $requirement, $matches)) {
                    $extensions[$matches[1]] = ifset($requirement_info['strict']);
                }
            }
            waRequest::setParam('extensions', $extensions);
        } elseif ($requirements === false) {
            $result = false;
        }

        return $result;
    }

    private function testConfig()
    {
        $available = array(
            'name',
            'description',
            'version',
            'vendor',
            'img',
            'icon',
            'logo',
            'frontend',
            'license',
            'critical',
        );
        switch ($this->type) {
            case 'app':
                $available = array_merge(
                    $available,
                    array(
                        'plugins',
                        'sms_plugins',
                        'shipping_plugins',
                        'payment_plugins',
                        'routing_params',
                        'pages',
                        'themes',
                        'rights',
                        'csrf',
                        'auth',
                        'my_account',
                        'mobile',
                        'sash_color',
                        'system',
                    )
                );
                break;
            case 'widget':
                $available = array_merge(
                    $available,
                    array(
                        'rights',
                        'size',
                    )
                );
                break;
            case 'plugin':
                $available = array_merge(
                    $available,
                    array(
                        'handlers',
                        'rights',
                        'custom_settings',
                    )
                );
                switch ($this->app_id) {
                    case 'shop':
                        $available = array_merge(
                            $available,
                            array(
                                'shop_settings',
                                'importexport',
                                'export_profile',
                            )
                        );
                        break;
                    case 'wa-plugins/shipping':
                        $available = array_merge(
                            $available,
                            array(
                                'external_tracking',
                                'external',
                            )
                        );
                        break;
                    default:
                        break;
                }
                break;
        }
        if ($this->config) {
            $keys = array_diff(array_keys($this->config), $available);
            if ($keys) {
                $this->tracef("Invalid %s's settings: unknown config options (%s)", $this->type, implode(',', $keys));
            }
        } else {
            $this->trace('ERROR: Empty or invalid item config');
            return false;
        }

        return empty($keys);
    }

    private function testRouting()
    {
        $result = true;
        $routing = $this->getItemConfig('routing');
        if (!empty($this->config['frontend'])) {
            if (empty($routing)) {
                $result = false;
                $this->tracef("Invalid %s's settings: empty routing for frontend", $this->type);
            } else {
                //TODO test $routing
            }
        } else {
            if ($routing !== null) {
                $this->tracef("Invalid %s's settings: routing exists but frontend disabled", $this->type);
                $result = false;
            }
            if (!empty($this->config['themes'])) {
                $this->tracef("Invalid %s's settings: themes option will be ignored", $this->type);
            }
        }
        return $result;
    }

    private function testInstall()
    {
        $install = in_array('lib/config/install.php', $this->files);
        $uninstall = in_array('lib/config/uninstall.php', $this->files);
        if (($install && !$uninstall) || (!$install && $uninstall)) {
            $this->tracef('NOTICE: only one of install.php & uninstall.php present');
        }
        return true;
    }


    private function testDb()
    {
        $result = true;
        switch ($this->type) {
            case 'plugin':
                $namespace = waRequest::param('prefix', sprintf('%s_%s', $this->app_id, $this->extension_id));
                $deprecated = 'lib/config/plugin.sql';
                break;
            case 'app':
                $namespace = $this->app_id;
                $deprecated = 'lib/config/app.sql';
                break;
            default:
                $namespace = null;
                $deprecated = null;
                break;
        }

        if ($namespace) {
            $db = $this->getItemConfig('db');
            if ($db) {
                $pattern = "@^{$namespace}(_.+)?$@";
                foreach ($db as $table => $info) {
                    if (!preg_match($pattern, $table)) {
                        $result = false;
                        $this->tracef("Invalid table name:\t\"%s\"", $table);
                    }
                }

                if (!$result) {
                    $this->tracef("Valid table names are \"%1\$s\" or \"%1\$s_*\"", $namespace);
                }
            } elseif ($deprecated && in_array($deprecated, $this->files)) {
                $this->tracef("Usage %s file id deprecated, use db.php", $deprecated);
                $this->tracef("Valid table names are \"%1\$s\" or \"%1\$s_*\"", $namespace);
            }
        }

        return $result;
    }

    private function testPhp()
    {
        $result = true;

        if ($this->execAvailable()) {

            if (defined('PHP_BINARY')) {
                $php_bin = constant('PHP_BINARY');
            } elseif (defined('PHP_BINDIR')) {
                $php_bin = constant('PHP_BINDIR');
            } else {
                $php_bin = 'php';
            }

            $command = sprintf('%s -v', $php_bin);
            $res = $this->exec($command, $outputs);
            if (($res !== 0) && ($php_bin != 'php')) {
                $php_bin = 'php';
                $command = sprintf('%s -v', $php_bin);
                $res = $this->exec($command, $outputs);
            }
            if ($res === 0) {
                $this->trace('Run PHP syntax check');
                $this->tracef("bin path: %s\nPHP Version:\n\t%s\n", $php_bin, implode("\n\t", $outputs));
                foreach ($this->files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) == 'php') {
                        $command = sprintf('%s -l -f "%s/%s"', $php_bin, $this->path, $file);
                        $outputs = null;

                        $res = $this->exec($command, $outputs);

                        if ($res !== 0) {
                            $outputs = array_filter($outputs, 'trim');
                            if ($outputs) {
                                $this->tracef("PHP errors at file %s:", $file);
                                foreach ($outputs as $output) {
                                    $this->tracef("\t%s", trim(str_replace($this->path.'/'.$file, 'file', $output)));
                                }
                                $result = false;
                            }
                        }
                    }
                }

                if ($result) {
                    $this->trace("PHP file syntax check\tOK");
                }
            } else {
                $this->trace("PHP binary not found, compile check skipped");
            }
        } else {
            $this->trace("WARNING: PHP syntax check skipped (proc_open function not available)");
        }
        return $result;
    }

    private function execAvailable()
    {
        if (function_exists('proc_open')) {
            $disabled = preg_split('@(,\s*)@', @ini_get('disable_functions'));
            if (in_array('proc_open', $disabled)) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    private function exec($command, &$lines)
    {
        $lines = array();
        $descriptor_spec = array(
            array('pipe', 'r'),//stdin
            array('pipe', 'w'),//stdout
            array('pipe', 'w'),//stderr
        );
        $pipes = array();

        $process = proc_open($command, $descriptor_spec, $pipes);
        $lines = preg_split("@[\r\n]+@", stream_get_contents($pipes[1]));
        $res = stream_get_contents($pipes[2]);

        foreach ($pipes as &$pipe) {
            fclose($pipe);
        }
        proc_close($process);
        if (strlen($res) == 0) {
            $res = 0;
        }

        return $res;
    }

    private function checkCode()
    {
        $result = true;
        $info = array(
            'functions' => array(),
            'classes'   => array(),
            'vars'      => array(),
        );


        $variables_blacklist = array(
            '^\$_(POST|GET|REQUEST|COOKIE|SERVER)^' => 'Use waRequest or waStorage classes',
        );

        foreach ($this->files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'php') {
                $path = $this->path.'/'.$file;

                $tokens = token_get_all(file_get_contents($path));
                foreach ($tokens as $id => $token) {
                    if (is_array($token)) {
                        switch ($token[0]) {
                            case T_CLASS:
                                $next_id = $id;
                                do {
                                    $next = ifset($tokens[++$next_id]);
                                } while (ifset($next[0]) != T_STRING);
                                if (!isset($info['classes'][$next[1]])) {
                                    $info['classes'][$next[1]] = array();
                                }
                                $info['classes'][$next[1]][] = $file;
                                break;
                            case T_STRING_VARNAME:
                            case T_VARIABLE:
                                foreach ($variables_blacklist as $pattern => $description) {
                                    if (preg_match($pattern, $token[1])) {
                                        $result = false;
                                        $this->tracef(
                                            "Not allowed variable %s at %s:%d\n\t%s",
                                            $token[1],
                                            $file,
                                            $token[2],
                                            $description
                                        );
                                    }
                                }

                                break;
                            case T_STRING:
                                if (function_exists($token[1])) {
                                    if (!isset($info['functions'][$token[1]])) {
                                        $info['functions'][$token[1]] = array();
                                    }
                                    $info['functions'][$token[1]][] = $file;
                                    $info['functions'][$token[1]] = array_unique($info['functions'][$token[1]]);
                                }
                                break;
                            case T_EVAL:
                            case T_EXIT:
                                $this->tracef("Not allowed function %s at %s:%s", $token[1], $file, $token[2]);
                                $result = false;
                                break;
                            case T_OPEN_TAG:
                                if ($token[1] == '<?') {
                                    $result = false;
                                    $this->tracef("PHP short open tag not allowed at %s:%d", $file, $token[2]);
                                }
                                break;
                            case T_CLOSE_TAG:
                                $this->tracef("PHP closing tag not required at %s:%d", $file, $token[2]);
                                break;
                        }
                    }
                }
            }
        }

        # 3. Check namespaces
        # 3.1 File names
        # 3.2 Class names
        switch ($this->type) {
            case 'plugin':
                $pattern = sprintf('@^%s\w*$@', waRequest::param('prefix', $this->app_id.ucfirst($this->extension_id)));
                break;
            case 'app':
                $pattern = sprintf('@^%s\w+$@', $this->app_id);
                break;
            default:
                $pattern = null;
                break;
        }
        if ($pattern) {
            foreach ($info['classes'] as $class => $files) {
                if (!preg_match($pattern, $class)) {
                    $result = false;
                    $this->tracef("Invalid class name %s at %s", $class, implode(', ', $files));
                } else {
                    //verify file name for class
                }
            }
        }


        $functions_blacklist = array(
            '@^mysqli?_\.+@'              => 'Use waModel instead',
            '@^call_user_func(_array)?$@' => 'Bad practice',
            '@^eregi?(_replace)$@'        => 'Deprecated, use preg/preg_replace',
            '@^spliti?$@'                 => 'Deprecated, use explode',
        );

        foreach ($info['functions'] as $function => $files) {
            foreach ($functions_blacklist as $pattern => $description) {
                if (preg_match($pattern, $function)) {
                    $this->tracef("Function %s not allowed\n\tHint:%s", $function, $description);
                    $result = false;
                }
            }
        }

        $extensions = array();
        $requirements = $this->getItemConfig('requirements');
        if ($requirements) {
            foreach ($requirements as $requirement => $requirement_info) {
                if (preg_match('@^php\.(.+)$@', $requirement, $matches)) {
                    $extensions[$matches[1]] = ifset($requirement_info['strict']);
                }
            }
        }
        waRequest::setParam('extensions', $extensions);

        $extensions = get_loaded_extensions();
        $extensions = array_diff(
            $extensions,
            array(
                'standard',
                'SPL',
                'iconv',
                'date',
                'gettext',
                'mbstring',
                'mysql',
                'mysqli',
                'tokenizer',
            )
        );
        $functions = array();
        foreach ($extensions as $extension) {
            $extension_functions = get_extension_funcs($extension);
            if ($extension_functions) {
                foreach ($extension_functions as $function) {
                    $functions[$function] = $extension;
                }
            }
        }

        return $result;
    }

    private function compress()
    {
        if (file_exists($this->archive_path)) {
            $date = date('c', max(filemtime($this->archive_path), filectime($this->archive_path)));
            $this->tracef('Unlink previous version of archive  from %s', $date);
            unlink($this->archive_path);
        }
        $current = getcwd();
        chdir($this->path);
        $files_md5_path = "{$this->path}/.files.md5";
        $this->md5($this->files, $files_md5_path);
        $temp_files = array();
        $temp_files[] = $files_md5_path;
        chdir($this->path.'/../');

        $archive_files = array();
        $archive_files[] = array($this->folder.'/.files.md5', $files_md5_path);
        foreach ($this->files as $file) {
            $archive_files[] = $this->folder.'/'.$file;
        }
        try {
            $time = microtime(true);
            if (class_exists('Archive_Tar')) {
                $tar_object = new Archive_Tar($this->archive_path, true);
                $result = $tar_object->create($archive_files);
                if ($result) {
                    $size = filesize($this->archive_path);
                    $this->tracef("\ntime\t%d ms\nsize\t%0.2f KByte\n", (microtime(true) - $time) * 1000, $size / 1024);

                } else {
                    $this->trace('Error during compress archive');
                }
            } else {
                $this->trace('Error during compress archive: class Archive_Tar not found');
            }
        } catch (Exception $ex) {
            $this->trace($ex->getMessage());
            $temp_files[] = $this->archive_path;
        }

        foreach ($temp_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        chdir($current);
    }

    private function md5($files, $path = null)
    {
        $total_size = 0;
        $count = 0;
        $time = microtime(true);
        $fp = @fopen($path, 'w');
        if ($fp) {
            foreach ($files as $file) {
                if (file_exists($file)) {

                    $md5 = md5_file($file);
                    $file_size = filesize($file);
                    $total_size += $file;
                    fprintf($fp, "%s *%s\n", $md5, $file);
                    $this->tracef("%5d\t%s\t%32s\t%s", ++$count, $file_size, $md5, $file);
                } else {
                    $this->tracef("%5d\t0\t%32s\t%s", $count, 'missed', $file);
                }
            }
            fclose($fp);
        } else {
            if (class_exists('waLog')) {
                waLog::log(
                    sprintf("Error while create checksum file [%d] %s at", strlen(basename($path)), $path, __METHOD__)
                );
            }
            throw new Exception('Error while create checksum file', 500);
        }
        $this->tracef(
            "time: %dms\t%d files, %0.2f KBytes %s",
            (microtime(true) - $time) * 1000,
            $count,
            $total_size / 1024,
            getcwd()
        );
        return $count;
    }

    private function filter(&$files, $blacklist = array(), $whitelist = array())
    {
        $blacklist = array_merge(
            $blacklist,
            array(
                '@^lib/updates/dev/.+@'                               => 'developer stage updates',
                '@\.(bak|old|user|te?mp|www)(\.(php|css|js|html))?$@' => 'temp file',
                '@(/|^)(\.DS_Store|\.desktop\.ini|thumbs\.db)$@'      => 'system file',
                '@\b\.(svn|git|hg_archival\.txt)\b@'                  => 'CVS file',
                '@^\.git.*@'                                          => 'GIT file',
                '@\.(zip|rar|gz)$@'                                   => 'archive',
                '@\.log$@'                                            => 'log file',
                '@\.md5$@'                                            => 'checksum file',
                '@\.(exe|dll|sys)$@'                                  => 'executable file',
                '@(/|^)[^\.]*todo$@i'                                 => 'TODO file',
                '@(/|^)[^\.]+$@'                                      => 'unknown type file',
                '@(/|^)[^0-9a-z_\-\.]+$@'                             => 'invalid filename characters',
            )
        );
        $skipped = array();
        foreach ($files as $id => $file) {
            foreach ($blacklist as $pattern => $description) {
                if (preg_match($pattern, $file)) {
                    $skipped[$file] = $description;
                    //@TODO add whitelist check
                    unset($files[$id]);
                    break;
                }
            }
        }
        return $skipped;
    }


    /**
     * @param string $format
     * @param mixed $_1
     * @param mixed $_2
     * @return int
     */
    protected function tracef()
    {
        $args = func_get_args();
        $this->trace(call_user_func_array('sprintf', $args));
    }

    protected function trace($string = '')
    {
        print $string."\n";
    }
}
