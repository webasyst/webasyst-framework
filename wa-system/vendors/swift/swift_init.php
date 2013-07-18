<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * Dependency injection initialization for Swift Mailer.
 */

if (defined('SWIFT_INIT_LOADED')) {
    return;
}

define('SWIFT_INIT_LOADED', true);

$swift_root_path = wa()->getConfig()->getPath('system').'/vendors/swift';

//Load in dependency maps
require $swift_root_path . '/dependency_maps/cache_deps.php';
require $swift_root_path . '/dependency_maps/mime_deps.php';
require $swift_root_path . '/dependency_maps/message_deps.php';
require $swift_root_path . '/dependency_maps/transport_deps.php';

//Load in global library preferences
require $swift_root_path . '/preferences.php';
