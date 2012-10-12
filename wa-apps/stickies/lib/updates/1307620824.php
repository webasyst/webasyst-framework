<?php

$model = new waModel();
try {
	$model->exec("ALTER TABLE stickies_sheet DROP INDEX sort");
} catch (waDbException $e) {	
}
