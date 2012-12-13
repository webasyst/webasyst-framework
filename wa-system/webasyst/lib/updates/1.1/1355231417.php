<?php

$mod = new waModel();
$mod->exec("UPDATE wa_contact_data SET field='address:region' WHERE field='address:state'");

