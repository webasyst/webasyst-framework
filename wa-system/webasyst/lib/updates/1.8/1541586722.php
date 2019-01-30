<?php

$_installer = new webasystInstaller();
$_installer->createTable(array('wa_verification_channel', 'wa_verification_channel_params', 'wa_verification_channel_assets'));
$_installer->installDefaultVerificationChannel();
