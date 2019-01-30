<?php

$m = new waApiTokensModel();

$sql = "SELECT c.name AS contact_name, t.*
        FROM wa_api_tokens AS t
            LEFT JOIN wa_contact AS c
                ON c.id=t.contact_id
        WHERE IFNULL(c.is_user, 0) = 0
            AND t.contact_id <> 0";
$tokens = array();
foreach($m->query($sql) as $row) {
    $tokens[$row['token']] = $row['token'];
    waLog::log("Removing API token that does not belong to backend user: ".wa_dump_helper($row));
}

$m->deleteById(array_values($tokens));