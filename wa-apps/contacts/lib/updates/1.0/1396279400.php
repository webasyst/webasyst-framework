<?php

$wcrm = new waContactRightsModel();
$wcrm->updateByField(array(
    'app_id' => 'contacts',
    'name' => 'category.all'
), array('name' => 'edit'));