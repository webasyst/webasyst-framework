<?php

/**
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * Console installer for Webasyst framework. An alternative to the web
 * installer (install.php) for copies installed from git, composer
 * (`composer create-project webasyst/framework`) or homebrew.
 *
 * Usage:
 *   php wa-installer/cli.php [--help]
 *   php wa-installer/cli.php -n --db-user ... --db-name ... --login ... --email ... --password ...
 *
 * Installation runs in two processes: this one prepares wa-config/* files
 * using the standalone installer library, then re-executes itself with the
 * internal --finish-install option to boot the framework and create the
 * database schema and the first user. The second process must not load
 * wa-installer/lib/init.php, because its _w() function conflicts with the
 * framework's _w() defined in wa-system/locale/waLocale.class.php.
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @package wa-installer
 */

if (PHP_SAPI !== 'cli') {
    print "This script must be run from the command line.";
    exit(1);
}

if (version_compare(PHP_VERSION, '7.4', '<')) {
    print sprintf("PHP version 7.4 or greater required, but current is %s", PHP_VERSION);
    exit(1);
}

if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--finish-install') {
    require_once dirname(__FILE__).'/lib/classes/wainstallerclifinish.class.php';
    exit((new waInstallerCliFinish())->run());
}

require_once dirname(__FILE__).'/lib/init.php';

(new waInstallerCli())->run(isset($_SERVER['argv']) ? $_SERVER['argv'] : array());
