<?php

/**
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * Console installer for Webasyst framework. Installs a copy of the framework
 * that was already placed on disk (git checkout, composer create-project or
 * homebrew) without opening a browser: validates requirements, writes
 * wa-config/* files, creates the database schema and (optionally) the first
 * backend user. Mirrors the steps of the web installer wa-installer/install.php.
 *
 * Usage: php wa-installer/cli.php [options]   (see --help)
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @package wa-installer
 */
class waInstallerCli
{
    /**
     * Apps enabled in wa-config/apps.php by default.
     * The 'webasyst' system app is always installed and is not listed in wa-config/apps.php.
     */
    const DEFAULT_APPS = 'site,installer,team';

    // Validation rules mirroring the framework classes named in the comments.
    // The framework autoloader is not available at this install stage, so the
    // same rules are applied here to fail before any file or table is created.

    /** Same as waEmailValidator::REGEX_EMAIL */
    const REGEX_EMAIL = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:[a-z0-9](?:[\\-a-z0-9]*[a-z0-9])*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';

    /** Same as waLoginValidator::REGEX_LOGIN; length limits are 4..32 (waLoginValidator init) */
    const REGEX_LOGIN = '/^[a-z0-9\.@_-]+$/i';
    const LOGIN_MIN_LENGTH = 4;
    const LOGIN_MAX_LENGTH = 32;

    /** Same as waAuth::PASSWORD_MAX_LENGTH */
    const PASSWORD_MAX_LENGTH = 255;

    /**
     * Files and directories the installer can create during installation.
     * On abort everything from this list that did not exist before the run
     * started is removed, so that the installation can be retried.
     * The root "cli.php" and ".htaccess" are deliberately not listed: they
     * belong to the framework distribution itself and are re-created
     * idempotently by the next run, so cleanup never removes them.
     */
    protected static $managed_paths = array(
        'wa-config/db.php',
        'wa-config/config.php',
        'wa-config/apps.php',
        'wa-config/routing.php',
        'wa-config/locale.php',
        'wa-config/SystemConfig.class.php',
        'wa-config/.htaccess',
        'wa-config/apps',
        'wa-config',
        // stale framework cache breaks re-installation: cached table field
        // maps make the schema step skip tables that were dropped by cleanup
        'wa-cache',
    );

    /** @var array managed path => true when it already existed before this run and must never be removed */
    protected $preexisting_paths = array();

    /** @var array database connection settings captured by stepDatabase() */
    protected $db = array();

    /** @var array table names that existed in the database before this installation run */
    protected $preexisting_tables = array();

    /** @var array long option name => true if option requires a value */
    protected static $options_spec = array(
        'locale'          => true,
        'db-host'         => true,
        'db-port'         => true,
        'db-user'         => true,
        'db-password'     => true,
        'db-name'         => true,
        'domain'          => true,
        'account-name'    => true,
        'apps'            => true,
        'mod-rewrite'     => true,
        'login'           => true,
        'email'           => true,
        'password'        => true,
        'firstname'       => true,
        'lastname'        => true,
        'non-interactive' => false,
        'force'           => false,
        'quiet'           => false,
        'help'            => false,
        // internal: set by composer post-create-project-cmd script
        'from-composer'   => false,
    );

    /** @var array short option letter => long option name */
    protected static $short_options = array(
        'n' => 'non-interactive',
        'f' => 'force',
        'q' => 'quiet',
        'h' => 'help',
    );

    /** @var array option name => value */
    protected $options = array();

    /** @var bool ask user for missing parameters */
    protected $interactive = true;

    /** @var bool suppress progress output */
    protected $quiet = false;

    /** @var string selected locale id, e.g. en_US */
    protected $locale_id = 'en_US';

    /** @var waInstallerLocale */
    protected $locale;

    /** @var string site domain, written to routing and default_host_domain */
    protected $domain;

    /** @var array app ids to enable in wa-config/apps.php (includes 'webasyst') */
    protected $apps = array();

    /** @var int total step count, for [n/m] progress labels */
    protected $steps_total = 4;

    public function run($argv)
    {
        try {
            $this->parseOptions($argv);
            if (!empty($this->options['help'])) {
                $this->usage();
                exit(0);
            }
            $this->quiet = !empty($this->options['quiet']);
            $this->interactive = empty($this->options['non-interactive']) && $this->stdinIsTty();
            if (!$this->interactive && !empty($this->options['from-composer'])) {
                // composer create-project without a terminal: do not fail,
                // just tell how to complete the installation
                $this->out('');
                $this->out('Webasyst framework has been downloaded.');
                $this->out('To complete the installation run:');
                $this->out('  php wa-installer/cli.php');
                $this->out('For non-interactive installation see:');
                $this->out('  php wa-installer/cli.php --help');
                $this->out('');
                exit(0);
            }
            $this->snapshotManagedPaths();
            $this->registerSignalHandlers();
            $this->checkNotInstalled();
            $this->setupLocale();
            $this->printBanner();
            $this->setupDomain();
            if (!$this->interactive) {
                // Every command line option must be validated before the first
                // file is written or a database is touched: a single invalid
                // option must abort the installation with no side effects.
                $this->validateOptions();
            }
            $this->stepRequirements();
            $this->stepDatabase();
            $this->stepApps();
            $this->stepSystemConfig();
            // Re-execute in a separate process to boot the framework, create
            // the database schema and (optionally) the first backend user.
            $exit_code = $this->runFinishPhase();
            if ($exit_code !== 0) {
                // A failed finish phase must not leave the config files and
                // the created database tables behind: otherwise the next run
                // mistakes the unfinished installation for a completed one
                $this->cleanupCreatedFiles();
                $this->cleanupCreatedTables();
            } else {
                $this->applyRootPermissions();
            }
            exit($exit_code);
        } catch (Throwable $e) {
            $this->err("\n".sprintf("[!] %s", $e->getMessage()));
            $this->err(sprintf("[!] %s", $this->locale ? $this->locale->_('Installation aborted.') : 'Installation aborted.'));
            $this->cleanupCreatedFiles();
            exit(1);
        }
    }

    //
    // Options, locale and basic I/O
    //

    protected function parseOptions($argv)
    {
        array_shift($argv); // script name
        $count = count($argv);
        for ($i = 0; $i < $count; $i++) {
            $arg = $argv[$i];
            if (substr($arg, 0, 2) === '--') {
                $name = substr($arg, 2);
                $value = null;
                if (strpos($name, '=') !== false) {
                    list($name, $value) = explode('=', $name, 2);
                }
                if (!isset(self::$options_spec[$name])) {
                    throw new Exception(sprintf('Unknown option "--%s". Use --help to list available options.', $name));
                }
                if (self::$options_spec[$name]) {
                    if ($value === null) {
                        $value = ($i + 1 < $count) ? $argv[++$i] : null;
                    }
                    $this->options[$name] = (string)$value;
                } else {
                    $this->options[$name] = true;
                }
            } elseif (strlen($arg) > 1 && $arg[0] === '-' && $arg[1] != '-') {
                for ($j = 1; $j < strlen($arg); $j++) {
                    $letter = $arg[$j];
                    if (!isset(self::$short_options[$letter])) {
                        throw new Exception(sprintf('Unknown option "-%s". Use --help to list available options.', $letter));
                    }
                    $this->options[self::$short_options[$letter]] = true;
                }
            } else {
                throw new Exception(sprintf('Unexpected argument "%s". Use --help to list available options.', $arg));
            }
        }
    }

    protected function usage()
    {
        $apps_default = self::DEFAULT_APPS;
        print <<<TEXT
Webasyst framework console installer

Installs the framework from a copy already placed on disk (git checkout,
composer create-project or homebrew) without opening a browser.

Usage:
  php wa-installer/cli.php [options]

Options:
  -n, --non-interactive   Do not ask anything; fail if required options are missing.
                            Without this flag missing values are asked interactively.
  -f, --force             Allow installation into a non-empty database.
  -q, --quiet             Suppress progress messages.
  -h, --help              Show this help.

  --locale=LOCALE         Interface and system locale: en_US (default), ru_RU.
  --db-host=HOST          MySQL server host (default: localhost). host:port is accepted.
  --db-port=PORT          MySQL server port (default: 3306).
  --db-user=USER          MySQL user (default: root).
  --db-password=PASSWORD  MySQL password.
  --db-name=DATABASE      MySQL database to install into. Must exist and must not
                            contain Webasyst tables.
  --domain=DOMAIN         Site domain for routing config and backend URL
                            (default: value of WA_INSTALLATION_DOMAIN env var, or localhost).
  --account-name=NAME     Account (company) name (default: "My company").
  --apps=LIST             Comma-separated app ids to enable (default: {$apps_default}).
                            "webasyst" system app is always installed.
  --mod-rewrite=1|0       Whether URL rewriting works on the web server. Cannot be
                            auto-detected from console, default is 1.
  --login=LOGIN           First backend user. When --login, --email and --password are all
  --email=EMAIL             provided, the user is created right away. Otherwise the first
  --password=PASSWORD       user can be created by opening the backend in a browser.
  --firstname=NAME        First user's first name (optional).
  --lastname=NAME         First user's last name (optional).

Examples:
  Interactive install:
    php wa-installer/cli.php

  Fully automated install:
    php wa-installer/cli.php -n --db-user=root --db-password=secret --db-name=webasyst \\
        --domain=example.com --login=admin --email=admin@example.com --password=secret

TEXT;
    }

    protected function out($message)
    {
        if (!$this->quiet) {
            fwrite(STDOUT, $message."\n");
        }
    }

    protected function err($message)
    {
        fwrite(STDERR, $message."\n");
    }

    protected function stdinIsTty()
    {
        if (function_exists('posix_isatty')) {
            return @posix_isatty(STDIN);
        }
        return true;
    }

    protected function prompt($message, $default = null)
    {
        $suffix = ($default !== null && $default !== '') ? sprintf(' [%s]', $default) : '';
        fwrite(STDOUT, $message.$suffix.': ');
        $line = trim((string)fgets(STDIN));
        if ($line === '' && $default !== null) {
            $line = $default;
        }
        return $line;
    }

    protected function promptHidden($message)
    {
        fwrite(STDOUT, $message.': ');
        $stty = function_exists('shell_exec') ? @shell_exec('stty -g 2>/dev/null') : false;
        if ($stty !== false) {
            @shell_exec('stty -echo 2>/dev/null');
        }
        $line = trim((string)fgets(STDIN));
        if ($stty !== false) {
            @shell_exec('stty '.$stty.' 2>/dev/null');
            fwrite(STDOUT, "\n");
        }
        return $line;
    }

    protected function confirm($message, $default = true)
    {
        $hint = $default ? 'Y/n' : 'y/N';
        fwrite(STDOUT, $message.' ['.$hint.']: ');
        $line = strtolower(trim((string)fgets(STDIN)));
        if ($line === '') {
            return $default;
        }
        return in_array($line, array('y', 'yes', 'д', 'да'), true);
    }

    /** Translation helper */
    protected function _($string)
    {
        $args = func_get_args();
        return call_user_func_array(array($this->locale, '_'), $args);
    }

    protected function checkNotInstalled()
    {
        if (file_exists(WA_ROOT.'wa-config/SystemConfig.class.php') && file_exists(WA_ROOT.'wa-config/db.php')) {
            throw new Exception('Webasyst is already installed in this directory. Delete wa-config/SystemConfig.class.php and wa-config/db.php to reinstall.');
        }
    }

    protected function snapshotManagedPaths()
    {
        $this->preexisting_paths = array();
        foreach (self::$managed_paths as $path) {
            $this->preexisting_paths[$path] = file_exists(WA_ROOT.$path) || is_dir(WA_ROOT.$path);
        }
    }

    /**
     * On SIGINT/SIGTERM/SIGHUP remove the files created so far and terminate,
     * so that a new installer run can start from a clean state.
     */
    protected function registerSignalHandlers()
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }
        pcntl_async_signals(true);
        $installer = $this;
        $handler = function ($signal) use ($installer) {
            $installer->cleanupCreatedFiles();
            $installer->cleanupCreatedTables();
            exit(128 + $signal);
        };
        foreach (array(SIGINT, SIGTERM, SIGHUP) as $signal) {
            pcntl_signal($signal, $handler);
        }
    }

    /**
     * Remove the files and directories created by this installation attempt.
     * Anything that already existed before the run started is never touched.
     * Best effort: removal failures are reported but do not stop the abort.
     */
    protected function cleanupCreatedFiles()
    {
        $removed = false;
        foreach (self::$managed_paths as $managed) {
            if (!empty($this->preexisting_paths[$managed])) {
                continue; // existed before this run: leave it alone
            }
            $path = WA_ROOT.$managed;
            if (!file_exists($path) && !is_dir($path)) {
                continue;
            }
            if (is_dir($path)) {
                if ($managed === 'wa-config') {
                    // dropped only when empty: pre-existing content stays
                    if (@rmdir($path)) {
                        $removed = true;
                    }
                    continue;
                }
                $done = self::removeDirRecursive($path);
            } else {
                $done = @unlink($path);
            }
            if ($done) {
                $removed = true;
            } else {
                $this->err(sprintf('[!] Unable to remove "%s" left over from the interrupted installation. Please remove it manually.', $managed));
            }
        }
        if ($removed) {
            $this->err('[i] Files created by the interrupted installation have been removed.');
        }
    }

    protected function snapshotTables($link)
    {
        $this->preexisting_tables = array();
        if ($result = mysqli_query($link, 'SHOW TABLES')) {
            while ($row = mysqli_fetch_row($result)) {
                $this->preexisting_tables[] = $row[0];
            }
            mysqli_free_result($result);
        }
    }

    /**
     * Drop the database tables created by this installation attempt. Tables
     * that already existed when the run connected to the database — including
     * leftovers from earlier runs — are never touched. Best effort: failures
     * are reported but do not stop the abort sequence.
     */
    protected function cleanupCreatedTables()
    {
        if (empty($this->db['database'])) {
            return; // the database step was not reached: nothing could be created
        }
        $link = @mysqli_connect(
            $this->db['host'],
            $this->db['user'],
            $this->db['password'],
            $this->db['database'],
            !empty($this->db['port']) ? (int)$this->db['port'] : 3306
        );
        if (!$link) {
            $this->err('[!] Unable to connect to the database to remove the tables created by the interrupted installation. Drop them manually or run the installer again with the --force option.');
            return;
        }
        $dropped = 0;
        $current = array();
        if ($result = mysqli_query($link, 'SHOW TABLES')) {
            while ($row = mysqli_fetch_row($result)) {
                $current[] = $row[0];
            }
            mysqli_free_result($result);
        }
        foreach ($current as $table) {
            if (in_array($table, $this->preexisting_tables, true)) {
                continue; // existed before this run
            }
            if (mysqli_query($link, 'DROP TABLE IF EXISTS `'.str_replace('`', '', $table).'`')) {
                $dropped++;
            } else {
                $this->err(sprintf('[!] Unable to drop database table "%s": %s. Please remove it manually.', $table, mysqli_error($link)));
            }
        }
        mysqli_close($link);
        if ($dropped) {
            $this->err(sprintf('[i] %d database table(s) created by the interrupted installation have been dropped. Run the installer again to retry.', $dropped));
        }
    }

    protected static function removeDirRecursive($path)
    {
        if (!is_dir($path) || is_link($path)) {
            return false;
        }
        foreach (array_diff(scandir($path), array('.', '..')) as $item) {
            $item_path = $path.'/'.$item;
            if (is_dir($item_path) && !is_link($item_path)) {
                self::removeDirRecursive($item_path);
            } else {
                @unlink($item_path);
            }
        }
        return @rmdir($path);
    }

    /**
     * Make everything created during installation owned by the same user and
     * group as the framework root directory and as readable/writable: created
     * directories get the root directory's access mode, created files get the
     * same read/write bits without the execute bits. Pre-existing regular
     * files shipped with the distribution are not touched. Best effort:
     * ownership can only be changed when the process has enough privileges.
     */
    protected function applyRootPermissions()
    {
        $root_path = rtrim(WA_ROOT, '/\\');
        $owner = fileowner($root_path);
        $group = filegroup($root_path);
        $dir_mode = fileperms($root_path) & 0777;
        $file_mode = $dir_mode & 0666;
        // root cli.php/.htaccess are not cleanup-managed but still need the
        // permission normalization when the installer created them
        foreach (array_merge(self::$managed_paths, array('wa-log', 'wa-data', 'cli.php', '.htaccess')) as $managed) {
            $path = WA_ROOT.$managed;
            // regular files that were already there (e.g. the shipped .htaccess)
            // are not created by the installer and keep their original mode
            if (!empty($this->preexisting_paths[$managed]) && is_file($path)) {
                continue;
            }
            $this->applyPermissionsRecursively($path, $owner, $group, $dir_mode, $file_mode);
        }
    }

    protected function applyPermissionsRecursively($path, $owner, $group, $dir_mode, $file_mode)
    {
        if (!file_exists($path)) {
            return;
        }
        if (is_dir($path) && !is_link($path)) {
            if ((fileperms($path) & 0777) != $dir_mode) {
                @chmod($path, $dir_mode);
            }
            $this->applyOwnerAndGroup($path, $owner, $group);
            foreach (array_diff(scandir($path), array('.', '..')) as $item) {
                $this->applyPermissionsRecursively($path.'/'.$item, $owner, $group, $dir_mode, $file_mode);
            }
        } else {
            if ((fileperms($path) & 0777) != $file_mode) {
                @chmod($path, $file_mode);
            }
            $this->applyOwnerAndGroup($path, $owner, $group);
        }
    }

    protected function applyOwnerAndGroup($path, $owner, $group)
    {
        if (fileowner($path) != $owner) {
            @chown($path, $owner);
        }
        if (filegroup($path) != $group) {
            @chgrp($path, $group);
        }
    }

    protected function printBanner()
    {
        $this->out('');
        $this->out('=== Webasyst framework console installer ===');
        $this->out('');
    }

    protected function setupLocale()
    {
        $available = waInstallerLocale::listAvailable();
        $locale = isset($this->options['locale']) ? $this->options['locale'] : null;
        if ($locale !== null) {
            if (!isset($available[$locale])) {
                throw new Exception(sprintf('Locale "%s" is not available. Available locales: %s.', $locale, implode(', ', $available)));
            }
        } elseif ($this->interactive) {
            $env = strtolower((string)getenv('LANG').' '.(string)getenv('LC_ALL'));
            $default = 'en_US';
            if (preg_match('/([a-z]{2}_[a-z]{2})/i', $env, $matches)) {
                // locale ids are mixed case ("ru_RU") while LANG is lowercase: match case-insensitively
                foreach ($available as $available_id => $name) {
                    if (strcasecmp($available_id, $matches[1]) === 0) {
                        $default = $available_id;
                        break;
                    }
                }
            }
            // _() below needs a locale object: start with the default one,
            // it is re-created with the confirmed locale at the end of the method
            $this->locale = new waInstallerLocale($default);
            $this->out(sprintf('Available locales: %s', implode(', ', $available)));
            $locale = $this->prompt($this->_('Select locale'), $default);
            if (!isset($available[$locale])) {
                $locale = $default;
            }
        } else {
            $locale = 'en_US';
        }
        $this->locale_id = $locale;
        $this->locale = new waInstallerLocale($locale);
        waInstallerApps::setLocale($locale);
        // waInstallerLocale::detect() inside requirements checker reads this
        $_POST['lang'] = $locale;
    }

    protected function setupDomain()
    {
        $domain = isset($this->options['domain']) ? $this->options['domain'] : '';
        if (!$domain) {
            $domain = (string)getenv('WA_INSTALLATION_DOMAIN');
        }
        if (!$domain && $this->interactive) {
            $default = function_exists('gethostname') ? (string)gethostname() : '';
            $domain = $this->prompt($this->_('Site domain (used in routing config and backend URL)'), $default ? $default : 'localhost');
        }
        if (!$domain) {
            $domain = 'localhost';
        }
        $this->domain = strtolower(trim($domain));

        // Routing and domain detection routines must not see the script path
        // (e.g. "wa-installer/cli.php") as a subdirectory of the site.
        $_SERVER['SCRIPT_NAME'] = '/';
        $_SERVER['PHP_SELF'] = '/';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = $this->domain;
    }

    /**
     * Validate every command line option before anything is written to disk
     * or to the database. Used in non-interactive mode only: interactively
     * every value is asked from the user instead. Database connectivity is
     * verified later in stepDatabase(), which also runs before it writes
     * wa-config/db.php.
     *
     * @throws Exception listing every invalid option
     */
    protected function validateOptions()
    {
        $errors = array();

        // --db-host: HOST or HOST:PORT; --db-port: numeric; --db-user: not empty; --db-name: required
        $db_host = isset($this->options['db-host']) ? $this->options['db-host'] : '';
        if (strlen($db_host) && strpos($db_host, ':') !== false && !preg_match('~^[^:]+:[0-9]+$~', $db_host)) {
            $errors[] = '--db-host: expected HOST or HOST:PORT, "'.$db_host.'" given.';
        }
        $db_port = isset($this->options['db-port']) ? $this->options['db-port'] : '';
        if (strlen($db_port) && !preg_match('~^[0-9]+$~', $db_port)) {
            $errors[] = '--db-port: port must be numeric, "'.$db_port.'" given.';
        }
        if (isset($this->options['db-user']) && !strlen($this->options['db-user'])) {
            $errors[] = '--db-user: MySQL user must not be empty.';
        }
        if (!strlen(isset($this->options['db-name']) ? $this->options['db-name'] : '')) {
            $errors[] = '--db-name: '.$this->_('Database name is required.').' Use --db-name option.';
        }

        // --domain: bare domain name, optionally with a numeric :port
        $domain = (string)$this->domain;
        if (strlen($domain)) {
            if (strpos($domain, '://') !== false || preg_match('~[/\\\\\s]~', $domain)) {
                $errors[] = '--domain: expected a domain name (e.g. example.com), "'.$domain.'" given.';
            } elseif (strpos($domain, ':') !== false && !preg_match('~^[^:]+:[0-9]+$~', $domain)) {
                $errors[] = '--domain: port must be numeric, "'.$domain.'" given.';
            }
        }

        // --mod-rewrite: 1 or 0 (boolean-ish values accepted by stepDatabase)
        if (isset($this->options['mod-rewrite'])
            && !in_array(strtolower($this->options['mod-rewrite']), array('0', '1', 'true', 'false', 'yes', 'no', 'on', 'off'), true)
        ) {
            $errors[] = '--mod-rewrite: expected 1 or 0, "'.$this->options['mod-rewrite'].'" given.';
        }

        // --apps: non-empty list of installed application ids
        $apps = isset($this->options['apps']) ? $this->options['apps'] : self::DEFAULT_APPS;
        $apps = array_filter(array_map('trim', explode(',', $apps)), 'strlen');
        if (!$apps) {
            $errors[] = '--apps: no applications selected.';
        }
        foreach ($apps as $app) {
            if ($app == 'webasyst') {
                continue; // system app, always installed
            }
            if (!preg_match('~^[a-z0-9_]+$~i', $app)) {
                $errors[] = '--apps: "'.$app.'" is not a valid application id.';
            } elseif (!is_dir(WA_ROOT.'wa-apps/'.$app) || !file_exists(WA_ROOT.'wa-apps/'.$app.'/lib/config/app.php')) {
                $errors[] = '--apps: application "'.$app.'" not found in wa-apps/. Use --apps option to list installed applications.';
            }
        }

        // --login, --email, --password: first user credentials, validated when provided
        $login = isset($this->options['login']) ? $this->options['login'] : '';
        if (strlen($login)) {
            if (mb_strlen($login) < self::LOGIN_MIN_LENGTH || mb_strlen($login) > self::LOGIN_MAX_LENGTH) {
                $errors[] = sprintf('--login: login must be %1$d to %2$d characters long, "%3$s" given.', self::LOGIN_MIN_LENGTH, self::LOGIN_MAX_LENGTH, $login);
            } elseif (!preg_match(self::REGEX_LOGIN, $login)) {
                $errors[] = '--login: login may contain only latin letters, digits, dots, "@", underscores and hyphens, "'.$login.'" given.';
            }
        }
        $email = isset($this->options['email']) ? $this->options['email'] : '';
        if (strlen($email) && !preg_match(self::REGEX_EMAIL, $email)) {
            $errors[] = '--email: "'.$email.'" is not a valid email address.';
        }
        $password = isset($this->options['password']) ? (string)$this->options['password'] : '';
        if (strlen($password) > self::PASSWORD_MAX_LENGTH) {
            $errors[] = sprintf('--password: password must not exceed %d characters.', self::PASSWORD_MAX_LENGTH);
        }

        if ($errors) {
            throw new Exception("Invalid options:\n".implode("\n", $errors));
        }
    }

    //
    // Installation steps
    //

    protected function stepRequirements()
    {
        $this->out('');
        $this->out(sprintf('[1/%d] %s', $this->steps_total, $this->_('System requirements')));
        if (!class_exists('waInstallerApps')) {
            throw new Exception('Class waInstallerApps not found');
        }
        $requirements = null;
        $passed = waInstallerApps::checkRequirements($requirements, true);
        if (!is_array($requirements)) {
            $requirements = array();
        }
        foreach ($requirements as $requirement) {
            $name = isset($requirement['name']) ? $requirement['name'] : '';
            $note = isset($requirement['note']) && $requirement['note'] ? strip_tags($requirement['note']) : '';
            $warning = isset($requirement['warning']) && $requirement['warning'] ? strip_tags($requirement['warning']) : '';
            if (empty($requirement['passed'])) {
                $this->out(sprintf('  [FAIL] %s', trim($name.' — '.$note)));
                if ($warning) {
                    $this->out(sprintf('         %s', $warning));
                }
                $description = isset($requirement['description']) ? strip_tags($requirement['description']) : '';
                if ($description) {
                    $this->out(sprintf('         %s', $description));
                }
            } elseif ($warning) {
                $this->out(sprintf('  [WARN] %s: %s', $name, $warning));
            } else {
                $this->out(sprintf('  [ok]   %s%s', $name, $note ? ' — '.$note : ''));
            }
        }
        if (!$passed) {
            throw new Exception($this->_('Server does not meet Webasyst system requirements. Installer can not proceed with the installation unless all requirements are satisfied.'));
        }
        $this->out($this->_('Server fully satisfies Webasyst system requirements.'));
    }

    protected function stepDatabase()
    {
        $this->out('');
        $this->out(sprintf('[2/%d] %s', $this->steps_total, $this->_('MySQL database')));

        $db = array(
            'host'     => isset($this->options['db-host']) ? $this->options['db-host'] : 'localhost',
            'port'     => isset($this->options['db-port']) ? $this->options['db-port'] : false,
            'user'     => isset($this->options['db-user']) ? $this->options['db-user'] : 'root',
            'password' => isset($this->options['db-password']) ? $this->options['db-password'] : '',
            'database' => isset($this->options['db-name']) ? $this->options['db-name'] : '',
        );

        if ($this->interactive) {
            $db['host'] = $this->prompt($this->_('MySQL server host'), $db['host']);
            // A TCP port only applies to non-socket connections: with the
            // "localhost" host mysqli uses the unix socket, and an explicit
            // "host:port" value already carries the port
            if (strpos($db['host'], ':') === false && strtolower(trim($db['host'])) !== 'localhost') {
                $db['port'] = $this->prompt($this->_('MySQL server port'), $db['port'] !== false ? $db['port'] : 3306);
            }
            $db['user'] = $this->prompt($this->_('MySQL user'), $db['user']);
            $db['password'] = $this->promptHidden($this->_('MySQL password'));
            $db['database'] = $this->prompt($this->_('Database name'));
        }
        if (!strlen($db['database'])) {
            throw new Exception($this->_('Database name is required.').($this->interactive ? '' : ' Use --db-name option.'));
        }
        if (!extension_loaded('mysqli')) {
            throw new Exception('PHP extension mysqli required');
        }

        if (strpos($db['host'], ':')) {
            list($db['host'], $db['port']) = explode(':', $db['host'], 2);
        }

        $this->out(sprintf('  %s %s@%s%s ...', $this->_('Connecting to'), $db['user'], $db['host'], $db['port'] ? ':'.$db['port'] : ''));
        $link = @mysqli_connect($db['host'], $db['user'], $db['password'], '', $db['port'] ? (int)$db['port'] : 3306);
        if (!$link) {
            throw new Exception(sprintf($this->_('Failed to connect to "%s" MySQL database server. (%s)'), $db['host'], '#'.mysqli_connect_errno().': '.mysqli_connect_error()));
        }
        try {
            if (!@mysqli_select_db($link, $db['database'])) {
                throw new Exception(sprintf($this->_('Failed to connect to the "%s" database. (%s)'), $db['database'], '#'.mysqli_errno($link).': '.mysqli_error($link)));
            }
            // remember the connection settings and the tables that already
            // exist: if the installation is interrupted, everything that
            // appeared after this point is removed during cleanup
            $this->db = $db;
            $this->snapshotTables($link);
            $table_count = 0;
            if ($result = mysqli_query($link, 'SHOW TABLES LIKE "wa_app_settings"')) {
                if (mysqli_num_rows($result)) {
                    if (empty($this->options['force'])) {
                        throw new Exception(sprintf($this->_('Webasyst cannot be installed into "%s" database because this database already contains Webasyst tables. Please specify connection credentials for another MySQL database.'), $db['database']).($this->interactive ? '' : ' Use --force option to install anyway.'));
                    }
                    $this->out(sprintf('  [WARN] %s', $this->_('The database already contains Webasyst tables. Installation proceeds because the --force option is set.')));
                }
            } elseif ($result = mysqli_query($link, 'SHOW TABLES')) {
                $table_count = (int)mysqli_num_rows($result);
            }
            if ($table_count && empty($this->options['force'])) {
                throw new Exception(sprintf($this->_('The database already contains %d tables.')."\n".'%s', $table_count, 'Use --force option to install anyway.'));
            } elseif ($table_count) {
                $this->out(sprintf('  [WARN] %s', sprintf($this->_('The database already contains %d tables.'), $table_count)));
            }

            if ($result = mysqli_query($link, 'SELECT VERSION()')) {
                $row = mysqli_fetch_row($result);
                if ($row && version_compare(reset($row), '5.7', '>=')) {
                    $db['sql_mode'] = 'TRADITIONAL';
                }
            }

            $db['type'] = 'mysqli';
            $installer_apps = new waInstallerApps();
            $installer_apps->updateDbConfig($db);

            // mod_rewrite cannot be detected from console, so requirements check
            // result is overridden here; default follows the web installer (true)
            $mod_rewrite = isset($this->options['mod-rewrite']) ? $this->options['mod-rewrite'] : '1';
            $mod_rewrite = in_array($mod_rewrite, array('0', 'false', 'no', 'off'), true) ? 0 : 1;
            $installer_apps->setGenericOptions(array(
                'mod_rewrite'         => (bool)$mod_rewrite,
                'default_host_domain' => $this->domain,
            ));
        } catch (Exception $e) {
            mysqli_close($link);
            throw $e;
        }
        mysqli_close($link);
        $this->out('  wa-config/db.php — ok');
    }

    protected function stepApps()
    {
        $this->out('');
        $this->out(sprintf('[3/%d] %s', $this->steps_total, $this->_('Applications')));

        $this->writeRootHtaccess();

        $apps = isset($this->options['apps']) ? $this->options['apps'] : self::DEFAULT_APPS;
        $apps = array_filter(array_map('trim', explode(',', $apps)), 'strlen');
        if (!$apps) {
            throw new Exception('No applications selected. Use --apps option.');
        }
        $this->apps = array();
        foreach ($apps as $app) {
            if ($app == 'webasyst') {
                continue; // system app, always installed
            }
            if (!is_dir(WA_ROOT.'wa-apps/'.$app) || !file_exists(WA_ROOT.'wa-apps/'.$app.'/lib/config/app.php')) {
                throw new Exception(sprintf('Application "%s" not found in wa-apps/. Use --apps option to list installed applications.', $app));
            }
            $this->apps[] = $app;
        }
        $this->apps[] = 'webasyst';

        $installer_apps = new waInstallerApps();
        foreach ($this->apps as $app) {
            if ($app == 'webasyst') {
                continue;
            }
            $installer_apps->installWebAsystApp($app);
            $this->out(sprintf('  %s — ok', $app));
            $plugins_path = WA_ROOT.'wa-apps/'.$app.'/plugins/';
            if (is_dir($plugins_path)) {
                foreach (scandir($plugins_path) as $plugin) {
                    if ($plugin === '.' || $plugin === '..' || !is_dir($plugins_path.$plugin)) {
                        continue;
                    }
                    if (!file_exists($plugins_path.$plugin.'/lib/config/plugin.php')) {
                        continue;
                    }
                    $installer_apps->updateAppPluginsConfig($app, $plugin);
                    $this->out(sprintf('    %s/%s — ok', $app, $plugin));
                }
            }
        }
        $this->out('  webasyst — ok ('.$this->_('system app').')');
    }

    protected function writeRootHtaccess()
    {
        $htaccess_path = WA_ROOT.'.htaccess';
        if (file_exists($htaccess_path)) {
            return;
        }
        if (!($fp = fopen($htaccess_path, 'w'))) {
            throw new Exception('Unable to write root .htaccess');
        }
        $htaccess_content = <<<HTACCESS

<FilesMatch "\.md5$">
    Deny from all
</FilesMatch>

DirectoryIndex index.php
Options -Indexes
# Comment the following line, if option Multiviews not allowed here
Options -MultiViews

AddDefaultCharset utf-8

<ifModule mod_rewrite.c>
    RewriteEngine On
    # Uncomment the following line, if you are having trouble
    #RewriteBase /

    RewriteCond %{REQUEST_URI} !\.(js|css|jpg|jpeg|gif|png)$
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L,QSA]
</ifModule>

<ifModule mod_headers.c>
    <FilesMatch "\.(jpg|jpeg|png|gif|js|css)$">
    Header set Cache-Control "max-age=3153600, public"
    </FilesMatch>
</ifModule>

HTACCESS;
        fwrite($fp, $htaccess_content);
        fclose($fp);
        $this->out('  .htaccess — ok');
    }

    protected function stepSystemConfig()
    {
        $this->out('');
        $this->out(sprintf('[4/%d] %s', $this->steps_total, $this->_('System configuration')));

        $config_dir = WA_ROOT.'wa-config/';
        if (!file_exists($config_dir)) {
            if (!mkdir($config_dir)) {
                throw new Exception('Unable to create directory wa-config');
            }
            $htaccess_path = $config_dir.'.htaccess';
            if (!file_exists($htaccess_path)) {
                if ($fp = @fopen($htaccess_path, 'w')) {
                    fwrite($fp, "Deny from all\n");
                    fclose($fp);
                } else {
                    throw new Exception("Error while trying to protect a directory wa-config with htaccess");
                }
            }
        }

        $config_path = $config_dir.'SystemConfig.class.php';
        if (!file_exists($config_path)) {
            if (!($fp = fopen($config_path, 'w'))) {
                throw new Exception("Error while create SystemConfig at wa-config");
            }
            $config_content = <<<PHP
<?php

require_once dirname(__FILE__).'/../wa-system/autoload/waAutoload.class.php';
waAutoload::register();

class SystemConfig extends waSystemConfig
{

}

PHP;
            fwrite($fp, $config_content);
            fclose($fp);
        }
        $this->out('  wa-config/SystemConfig.class.php — ok');
    }

    /**
     * Execute the finish phase in a separate process. It boots the framework
     * (creating the database schema and running app install scripts), creates
     * the first backend user and prints the final summary. A separate process
     * is required because the installer library's _w() function conflicts with
     * the framework's _w() and cannot coexist in one PHP process.
     *
     * @return int exit code of the finish process
     */
    protected function runFinishPhase()
    {
        $payload = array(
            'apps'         => $this->apps,
            'locale'       => $this->locale_id,
            'domain'       => $this->domain,
            'account_name' => isset($this->options['account-name']) ? $this->options['account-name'] : null,
            'user'         => array(
                'login'     => isset($this->options['login']) ? $this->options['login'] : '',
                'email'     => isset($this->options['email']) ? $this->options['email'] : '',
                'password'  => isset($this->options['password']) ? $this->options['password'] : '',
                'firstname' => isset($this->options['firstname']) ? $this->options['firstname'] : '',
                'lastname'  => isset($this->options['lastname']) ? $this->options['lastname'] : '',
            ),
            'interactive'  => $this->interactive,
            'quiet'        => $this->quiet,
        );
        $cmd = sprintf(
            'WA_CLI_FINISH=%s %s %s --finish-install',
            escapeshellarg(json_encode($payload)),
            escapeshellarg(PHP_BINARY),
            escapeshellarg(WA_ROOT.'wa-installer/cli.php')
        );
        passthru($cmd, $exit_code);
        return $exit_code;
    }
}
