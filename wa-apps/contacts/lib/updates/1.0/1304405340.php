<?php

$model = new waModel();
$model->exec("UPDATE wa_country SET iso2letter=LOWER(iso2letter), iso3letter=LOWER(iso3letter)");
