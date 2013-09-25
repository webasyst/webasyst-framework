<?php

$model = new waModel();

$sql = "UPDATE photos_photo p LEFT JOIN wa_contact c ON p.contact_id = c.id 
        SET p.contact_id = 0 
        WHERE c.id IS NULL";
$model->exec($sql);

try {
    $sql = "SELECT id FROM photos_comment WHERE 0";
    $model->exec($sql);
    $sql = "UPDATE photos_comment cm LEFT JOIN wa_contact ct ON cm.contact_id = ct.id
            SET cm.contact_id = 0
            WHERE ct.id IS NULL AND cm.contact_id != 0";
    $model->exec($sql);
} catch (waException $e) {
    
}