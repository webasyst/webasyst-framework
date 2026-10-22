<?php

/**
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * Second phase of the console installer (wa-installer/cli.php), re-executed
 * in a separate process via the internal --finish-install option. wa-config/*
 * files are already written by the first phase; this phase boots the framework
 * (which creates the database schema and runs app install scripts), creates
 * the first backend user and prints the final summary.
 *
 * IMPORTANT: must not require wa-installer/lib/init.php — its _w() function
 * conflicts with the framework's _w() defined when the framework boots.
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @package wa-installer
 */
class waInstallerCliFinish
{
    /** @var string site domain */
    protected $domain = 'localhost';

    /** @var string locale id */
    protected $locale_id = 'en_US';

    /** @var waInstallerLocale */
    protected $locale;

    /** @var array app ids to initialize (includes 'webasyst') */
    protected $apps = array('webasyst');

    /** @var array first backend user options: login, email, password, firstname, lastname */
    protected $user = array();

    /** @var string|null account (company) name */
    protected $account_name;

    /** @var bool ask user for missing parameters */
    protected $interactive = false;

    /** @var bool suppress progress output */
    protected $quiet = false;

    /** @var bool whether the first backend user was created */
    protected $user_created = false;

    /** @var int total step count, for [n/m] progress labels */
    protected $steps_total = 6;

    /** @var int step number this phase starts from */
    protected $step_offset = 4;

    /**
     * @return int exit code
     */
    public function run()
    {
        try {
            $payload = getenv('WA_CLI_FINISH');
            $params = $payload ? json_decode($payload, true) : null;
            if (!is_array($params)) {
                throw new Exception('wa-installer/cli.php --finish-install is for internal use by the console installer.');
            }
            $this->domain = isset($params['domain']) && strlen($params['domain']) ? strtolower($params['domain']) : 'localhost';
            $this->locale_id = isset($params['locale']) && strlen($params['locale']) ? $params['locale'] : 'en_US';
            $this->apps = !empty($params['apps']) && is_array($params['apps']) ? $params['apps'] : array('webasyst');
            $this->account_name = isset($params['account_name']) ? $params['account_name'] : null;
            $this->user = array(
                'login'     => isset($params['user']['login']) ? $params['user']['login'] : '',
                'email'     => isset($params['user']['email']) ? $params['user']['email'] : '',
                'password'  => isset($params['user']['password']) ? $params['user']['password'] : '',
                'firstname' => isset($params['user']['firstname']) ? $params['user']['firstname'] : '',
                'lastname'  => isset($params['user']['lastname']) ? $params['user']['lastname'] : '',
            );
            $this->interactive = !empty($params['interactive']);
            $this->quiet = !empty($params['quiet']);

            if (!file_exists($this->rootPath().'wa-config/SystemConfig.class.php')) {
                throw new Exception('wa-config/SystemConfig.class.php not found. Run the console installer without --finish-install first.');
            }

            require_once dirname(__FILE__).'/wainstallerlocale.class.php';
            $this->locale = new waInstallerLocale($this->locale_id);

            $this->stepInitSchema();
            $this->stepCreateUser();
            $this->printSummary();
            return 0;
        } catch (Exception $e) {
            $this->err("\n[!] ".$e->getMessage());
            $this->err("[!] ".($this->locale ? $this->_('Installation aborted.') : 'Installation aborted.'));
            return 1;
        }
    }

    protected function rootPath()
    {
        $root = preg_replace('@([/\\\\]+)@', '/', dirname(__FILE__).'/');
        return preg_replace('@/wa-installer/lib/classes/$@', '/', $root);
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

    protected function stepInitSchema()
    {
        $this->out('');
        $this->out(sprintf('[%d/%d] %s', $this->step_offset + 1, $this->steps_total, $this->_('Database schema')));

        // Routing and domain detection routines must not see the script path
        // (e.g. "wa-installer/cli.php") as a subdirectory of the site.
        $_SERVER['SCRIPT_NAME'] = '/';
        $_SERVER['PHP_SELF'] = '/';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = $this->domain;

        if (function_exists('date_default_timezone_set')) {
            @date_default_timezone_set(@date_default_timezone_get());
        }

        require_once $this->rootPath().'wa-config/SystemConfig.class.php';

        // Boot sequence identical to wa.php / waDispatch::dispatchCli():
        // waSystem::getInstance() for an app config calls checkUpdates()
        // which creates the app tables and runs the app install script.
        waSystem::getInstance(null, new SystemConfig('cli'));
        // webasyst is the core backend app: boot it like the web installer does,
        // so its classes (e.g. webasystFirstUser used by stepCreateUser) are autoloadable
        foreach (array_unique(array_merge(array('webasyst'), $this->apps)) as $app) {
            try {
                waSystem::getInstance($app);
                $this->out(sprintf('  %s — ok', $app));
            } catch (Exception $e) {
                $this->out(sprintf('  [WARN] %s: %s', $app, $e->getMessage()));
            }
        }
    }

    protected function stepCreateUser()
    {
        $this->out('');
        $this->out(sprintf('[%d/%d] %s', $this->step_offset + 2, $this->steps_total, $this->_('First user')));

        $create = true;
        if ($this->interactive) {
            $create = $this->confirm($this->_('Create the first backend user now? (Otherwise you will be able to do it by opening the backend in a browser)'), true);
            if ($create) {
                $this->user['login'] = $this->prompt($this->_('Login'), $this->user['login']);
                $this->user['email'] = $this->prompt($this->_('Email'), $this->user['email']);
                $this->user['password'] = $this->promptHidden($this->_('Password'));
            }
        } else {
            $create = strlen($this->user['login']) && strlen($this->user['email']) && strlen($this->user['password']);
        }

        if (!$create) {
            return;
        }

        $contact_model = new waContactModel();
        if (!$contact_model->isEmpty()) {
            $this->out('  '.$this->_('Contacts already exist. Skipping first user creation.'));
            return;
        }

        // save the locale selected in the installer into the backend, like web installer does
        try {
            waSystem::getInstance()->setLocale($this->locale_id);
        } catch (Exception $e) {
        }
        try {
            $app_settings_model = new waAppSettingsModel();
            $app_settings_model->set('webasyst', 'locale', $this->locale_id);
        } catch (Throwable $e) {
        }

        try {
            $this->createFirstUser();
        } catch (Throwable $e) {
            // The first user is optional: any problem with it (invalid login or
            // email format, database error, ...) must not fail the whole
            // installation. Behave exactly like the "no first user" choice:
            // the account can be created by opening the backend in a browser.
            $this->out('  [WARN] '.str_replace("\n", '; ', trim(strip_tags($e->getMessage()))));
            $this->out('  '.$this->_('Skipping first user creation. You will be able to create it by opening the backend in a browser.'));
            return;
        }

        // Web installer removes the browser-based installer stub once the
        // first user is created; do the same here.
        if (file_exists($this->rootPath().'install.php')) {
            @unlink($this->rootPath().'install.php');
        }

        $this->user_created = true;
        $this->out(sprintf('  %s <%s> — ok', $this->user['login'], $this->user['email']));
    }

    /**
     * Validate the first user options and create the account.
     * Throws on any problem; stepCreateUser() turns failures into the
     * "create the user in the browser" flow instead of aborting.
     */
    protected function createFirstUser()
    {
        foreach (array('login', 'email', 'password') as $field) {
            if (!strlen($this->user[$field])) {
                throw new Exception(sprintf('%s is required to create the first user.', $field).($this->interactive ? '' : ' Use --'.$field.' option.'));
            }
        }
        if (strlen($this->user['password']) > waAuth::PASSWORD_MAX_LENGTH) {
            throw new Exception($this->_('Specified password is too long.'));
        }

        $validator = new waLoginValidator();
        if (!$validator->isValid($this->user['login'])) {
            $login_errors = $validator->getErrors();
            if ($login_errors) {
                throw new Exception('login: '.implode("; ", $login_errors));
            }
        }
        $validator = new waEmailValidator();
        if (!$validator->isValid($this->user['email'])) {
            $email_errors = $validator->getErrors();
            if ($email_errors) {
                throw new Exception('email: '.implode("; ", $email_errors));
            }
        }

        $user = new waUser();
        $user['firstname'] = strlen($this->user['firstname']) ? $this->user['firstname'] : $this->user['login'];
        $user['lastname'] = $this->user['lastname'];
        $user['email'] = $this->user['email'];
        $result = webasystFirstUser::createFirstUser($user, $this->user['login'], $this->user['password'], $this->account_name);
        if (!empty($result['errors'])) {
            $messages = array();
            foreach ($result['errors'] as $field => $error) {
                $messages[] = (is_array($error) ? implode(', ', $error) : (string)$error);
            }
            throw new Exception(implode("\n", $messages));
        }
    }

    protected function printSummary()
    {
        $this->out('');
        $this->out('===');
        $this->out($this->_('Installed!'));
        $this->out($this->_('Webasyst is installed and ready.'));

        $config = array();
        $config_path = $this->rootPath().'wa-config/config.php';
        if (file_exists($config_path)) {
            $config = include($config_path);
            if (!is_array($config)) {
                $config = array();
            }
        }
        $login_path = empty($config['mod_rewrite']) ? 'index.php/webasyst/' : 'webasyst/';
        $backend_url = 'http://'.$this->domain.'/'.$login_path;
        $this->out('');
        $this->out(sprintf('%s: %s', $this->_('Backend URL'), $backend_url));
        if ($this->user_created) {
            $this->out(sprintf('%s: %s', $this->_('Login'), $this->user['login']));
        } else {
            $this->out(sprintf($this->_('Open %s in a browser to create the first user and finish the setup.'), $backend_url.'?lang='.$this->locale_id));
        }
        $this->out('');
        $this->out($this->_('Remember this address. This is the address for logging into your Webasyst backend.'));
        $this->out('');
    }
}
