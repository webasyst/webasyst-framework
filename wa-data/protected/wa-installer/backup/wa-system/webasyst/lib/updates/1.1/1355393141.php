<?php

$mod = new waModel();
if ( ( $sql = @file_get_contents($this->getAppPath('lib/config/wa_region.sql')))) {
    $mod->exec($sql);
}

