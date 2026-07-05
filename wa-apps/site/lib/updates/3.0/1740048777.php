<?php

try {
    $m = new waModel();
    $m->query("UPDATE site_blockpage SET status='final_published' WHERE status='final_unpublished' AND final_page_id IS NULL");
} catch (Exception $e) {
}
