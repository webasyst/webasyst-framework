<?php
//
// Fix consequences of a bug in 1.8.3
// when saving DKIM to mail.php config
// did not copy default settings to new domain.
//

// allows hook for unit-test
if (empty($wamail)) {
    $wamail = new waMail();
}

$something_changed = false;
$mail_config = (array)$wamail->readConfigFile();
if (isset($mail_config['default'])) {
    foreach ($mail_config as $key => $settings) {
        if ($key === 'default') {
            continue;
        }
        if (empty($settings) || !is_array($settings)) {
            unset($mail_config[$key]);
            $something_changed = true;
        }
        if (count($settings) == 4
            && isset(
                $settings['dkim'],
                $settings['dkim_pvt_key'],
                $settings['dkim_pub_key'],
                $settings['dkim_selector']
            )
        ) {
            $mail_config[$key] = $settings + $mail_config['default'];
            $something_changed = true;
        }
    }
}

if ($something_changed) {
    $wamail->saveConfigFile($mail_config);
}
