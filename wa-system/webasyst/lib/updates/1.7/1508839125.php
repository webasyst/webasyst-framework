<?php
$m = new waModel();

try {
    $m->exec("ALTER TABLE `wa_contact` ADD INDEX `is_user` (`is_user`)");
} catch (waDbException $e) {

}
