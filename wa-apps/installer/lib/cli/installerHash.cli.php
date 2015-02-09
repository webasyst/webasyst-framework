<?php
class installerHashCli extends waCliController
{
    public function execute()
    {
        $installer = new waInstallerApps();
        print $installer->getHash();
    }
}
