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
            waAutoload::getInstance()->add('Archive_Tar', '/wa-installer/lib/vendors/PEAR/Tar.php');
            waAutoload::getInstance()->add('PEAR', '/wa-installer/lib/vendors/PEAR/PEAR.php');
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
                if (preg_match("@^({$id_pattern})($|/(plugins|themes)/({$id_pattern})$)@", $slug, $matches)) {
                    $this->type = ifset($matches[3], 'app');
                    $this->app_id = $matches[1];
                    $this->extension_id = ifset($matches[4]);
                } elseif (preg_match("@^wa-plugins/(payment|shipping|sms)/({$id_pattern})$@", $slug, $matches)) {
                    $this->app_id = 'wa-plugins';
                    $this->type = $matches[1];
                    $this->extension_id = $matches[2];
                } else {
                    throw new Exception("invalid SLUG");
                }

                $this->tracef('Check & compress %s with params:', $slug);

                foreach (self::$default as $param => $default) {
                    $this->tracef("\t%s\t%s", $param, implode(', ', $this->getParam($param)));
                }


                $this->tracef('PHP version %s', phpversion());


                if ($skipped = $this->initPath()) {
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
                    if ($compress = $this->test()) {
                        $this->trace('Config check OK');
                    }
                } else {
                    $this->trace('Config check skipped');
                }

                if ('theme' != $this->type) {
                    $style = $this->getParam('style');
                    if (!in_array('false', $style, true)) {
                        $count = $this->codeStyle($style);
                        if ($count === false) {
                            $this->trace('Code style check skipped');
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
                    $this->compress();
                }
            }
        } catch (Exception $ex) {
            $this->tracef("ERROR: %s\n\n", $ex->getMessage());
            $this->printHelp();
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

        $type = preg_replace('@s$@', '', $this->type);
        switch ($type) {
            case 'app':
                $namespace = $this->app_id;
                $this->path = wa()->getConfig()->getAppsPath($this->app_id, null);
                break;
            case 'plugin':
            case 'theme':
                $namespace = $this->app_id.ucfirst($this->extension_id);

                $this->path = wa()->getConfig()->getAppsPath($this->app_id, $this->type.'/'.$this->extension_id);
                break;
            case 'shipping':
            case 'payment':
                $namespace = $this->extension_id.ucfirst($this->type);
                $this->path = wa()->getConfig()->getPath('plugins').'/'.$this->type.'/'.$this->extension_id;
                break;
            case 'sms':
                $namespace = $this->extension_id.strtoupper($this->type);
                $this->path = wa()->getConfig()->getPath('plugins').'/'.$this->type.'/'.$this->extension_id;
                break;
            default:
                throw new waException('');
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
            $blacklist['@^plugins/.+@'] = 'application\'s plugins';
            if (true) {
                $blacklist['@^themes/(?!default).+@'] = 'application\'s themes';
            }
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
        return $this->filter($this->files, $blacklist);
    }

    private function getItemConfig($path)
    {
        $config_path = $this->path.'/lib/config/'.$path.'.php';
        if (file_exists($config_path)) {
            $config = include($config_path);
            if (!is_array($config)) {
                $config = array();
            }
        } else {
            $config = false;
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
                if (in_array('no-vendors', $param, true) && preg_match('@^(lib/|js/)?vendors/@', $file)) {
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
            $this->tracef('ERROR: %s', $ex->getMessage());
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

        $phpcs = new PHP_CodeSniffer(0, 4, 'UTF-8', $interactive);
        // Set file extensions if they were specified. Otherwise,
        // let PHP_CodeSniffer decide on the defaults.
        if (true) {
            $extensions = array(
                'php',
                // 'html',
                'js',
                'css',
            );
            $phpcs->setAllowedFileExtensions($extensions);
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
            $installed = $phpcs->getInstalledStandardPath($standard);
            if ($installed !== null) {
                $standard = $installed;
            } else {
                if (is_dir($standard) === true
                    && is_file(realpath($standard.'/ruleset.xml')) === true
                ) {
                    $standard = realpath($standard.'/ruleset.xml');
                }
            }
            $sniffs = array_merge($sniffs, $phpcs->processRuleset($standard));
        }
        //end foreach

        $sniffRestrictions = array();

        $phpcs->registerSniffs($sniffs, $sniffRestrictions);
        $phpcs->populateTokenListeners();

        // The SVN pre-commit calls process() to init the sniffs
        // and ruleset so there may not be any files to process.
        // But this has to come after that initial setup.

        //define('PHP_CODESNIFFER_IN_TESTS',true);
        $_SERVER['argc'] = 0;
        $errors_count = 0;
        foreach ($files as $file) {
            $phpcsFile = $phpcs->processFile($file);
            // Show progress information.
            if ($phpcsFile !== null) {
                $count = ($phpcsFile->getErrorCount() + $phpcsFile->getWarningCount());
                if (!$interactive && $count) {
                    $report = array(
                        'ERROR'   => $phpcsFile->getErrors(),
                        'WARNING' => $phpcsFile->getWarnings(),
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
        $extensions = array();
        if ($requirements = $this->getItemConfig('requirements')) {
            foreach ($requirements as $requirement => $requirement_info) {
                if (preg_match('@^php\.(.+)$@', $requirement, $matches)) {
                    $extensions[$matches[1]] = ifset($requirement_info['strict']);
                }
            }
        }
        waRequest::setParam('extensions', $extensions);
        return true;
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
                    )
                );
                break;
            case 'plugin':
                $available = array_merge(
                    $available,
                    array(
                        'handlers',
                        'rights',
                    )
                );
                if ($this->app_id == 'shop') {
                    $available = array_merge(
                        $available,
                        array(
                            'shop_settings',
                            'importexport',
                            'export_profile',
                        )
                    );
                }
                break;
        }
        if ($this->config) {
            $keys = array_diff(array_keys($this->config), $available);
            if ($keys) {
                $this->tracef("Invalid %s's settings: unknown config options (%s)", $this->type, implode(',', $keys));
            }
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
                foreach ($routing as $route) {
                    //TODO
                }
            }
        } else {
            if ($routing !== false) {
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
                $namespace = waRequest::param('prefix', "{$this->app_id}_{$this->extension_id}");
                break;
            case 'app';
                $namespace = "{$this->app_id}";
                break;
            default:
                $namespace = null;
                break;
        }

        if ($namespace) {

            if ($db = $this->getItemConfig('db')) {
                $pattern = "@^{$namespace}(_.+)?$@";
                foreach ($db as $table => $info) {
                    if (!preg_match($pattern, $table)) {
                        $result = false;
                        $this->tracef("Invalid table name:\t\"%s\"", $table);
                    }
                }
            }
        } else {

        }
        if (!$result) {
            $this->tracef("Valid table names are \"%1\$s\" or \"%1\$s_*\"", $namespace);
        }
        return $result;
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
                $tokens = token_get_all(file_get_contents($this->path.'/'.$file));
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
                $pattern = "@^".waRequest::param('prefix', $this->app_id.ucfirst($this->extension_id))."\w*\$@";
                break;
            case 'app';
                $pattern = "@^{$this->app_id}\w+\$@";
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
        if ($requirements = $this->getItemConfig('requirements')) {
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
            if ($extension_functions = get_extension_funcs($extension)) {
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
            $tar_object = new Archive_Tar($this->archive_path, true);
            if ($result = $tar_object->create($archive_files)) {
                $size = filesize($this->archive_path);
                $this->tracef("\ntime\t%d ms\nsize\t%0.2f KByte\n", (microtime(true) - $time) * 1000, $size / 1024);

            } else {
                $this->trace('Error during compress archive');
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
        if ($fp = @fopen($path, 'w')) {
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

    private function filter(&$files, $blacklist = array())
    {
        $blacklist = array_merge(
            $blacklist,
            array(
                '@^lib/updates/dev/.+@'                               => 'developer stage updates',
                '@\.(bak|old|user|te?mp|www)(\.(php|css|js|html))?$@' => 'temp file',
                '@(/|^)(\.DS_Store|\.desktop\.ini|thumbs\.db)$@'      => 'system file',
                '@\b\.(svn|git|hg_archival\.txt)\b@'                  => 'CVS file',
                '@\.(zip|rar|gz)$@'                                   => 'archive',
                '@\.log$@'                                            => 'log file',
                '@\.md5$@'                                            => 'checksum file',
                '@\.(exe|dll|sys)$@'                                  => 'executable file',
                '@(/|^)[^\.]*todo$@i'                                 => 'TODO file',
                '@(/|^)[^\.]+$@'                                      => 'unknown type file',
            )
        );
        $skipped = array();
        foreach ($files as $id => $file) {
            foreach ($blacklist as $pattern => $description) {
                if (preg_match($pattern, $file)) {
                    unset($files[$id]);
                    $skipped[$file] = $description;
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

    protected function trace($string)
    {
        print $string."\n";
    }
}
