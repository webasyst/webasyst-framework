<?php

$tm = new mailerTemplateModel();
foreach($tm->getTemplates() as $template) {
    $count = null;
    $body = str_replace(array('%7B', '%24', '%7D'), array('{', '$', '}'), $template['body'], $count);
    if ($count > 0) {
        $tm->updateById($template['id'], array(
            'body' => $body,
        ));
    }
}

