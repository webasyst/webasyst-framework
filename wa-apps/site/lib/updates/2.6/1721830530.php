<?php

$tables = ['site_blockpage', 'site_blockpage_params', 'site_blockpage_blocks', 'site_globalblock', 'site_blockpage_block_files', 'site_blockpage_file'];
$db_path = wa()->getAppPath('lib/config/db.php', $this->application);
$db_partial = array_intersect_key(include($db_path), array_fill_keys($tables, 1));
if (count($db_partial) < count($tables)) {
    throw new waException('Unable to find table definitions for '.join(", ", $tables));
}

$m = new waAppSettingsModel();
$m->createSchema($db_partial);
$m->set('site', 'migrated_from_ui13', 1);
