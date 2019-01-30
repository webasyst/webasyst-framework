<?php
$spm = new siteDomainModel();
$meta_data = $spm->getMetadata();

try {
    if (!isset($meta_data['title']['default'])) {
        $spm->exec("ALTER TABLE `site_domain` ALTER COLUMN `title` SET DEFAULT ''");
    }
} catch (Exception $e) {

}
