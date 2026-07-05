<?php

//
// This is the second part of app initialization script. It runs the first time
// when a logged-in admin user opens the app.
// See siteConfig->installAfter(), install.php
//

$site_installer = new siteInstaller();

$site_installer->addDefaultVariables();

$site_installer->prepareRouting();
$site_installer->addDomains();
$site_installer->addPages();
