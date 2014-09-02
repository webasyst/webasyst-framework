<?php

$model = new waModel();
$sql = "UPDATE wa_contact c JOIN wa_contact_data d ON c.id = d.contact_id AND d.field = 'is_banned' SET c.is_user = -1";
$model->exec($sql);