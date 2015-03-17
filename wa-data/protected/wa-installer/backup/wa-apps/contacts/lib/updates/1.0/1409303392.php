<?php

$path = wa()->getDataPath('photos', true, 'contacts');

waFiles::write($path.'/thumb.php', '<?php
$file = realpath(dirname(__FILE__)."/../../../../")."/wa-apps/contacts/lib/config/data/thumb.php";

if (file_exists($file)) {
    include($file);
} else {
    header("HTTP/1.0 404 Not Found");
}
');
waFiles::copy(wa()->getAppPath('lib/config/data/.htaccess', 'contacts'), $path.'/.htaccess');

$old_path = wa()->getDataPath('photo', true, 'contacts', false);

if (file_exists($old_path) && is_dir($old_path)) {

    $model = new waModel();

    $all_success = true;
    $contact_ids = $model->query("SELECT id FROM wa_contact WHERE photo > 0")->fetchAll(null, true);
    foreach ($contact_ids as $contact_id) {
        $old_filepath = $old_path . '/' . $contact_id;
        if (file_exists($old_filepath) && is_dir($old_filepath)) {
            $filepath = wa()->getDataPath(waContact::getPhotoDir($contact_id), true, 'contacts');
            try {
                $success = @waFiles::move($old_filepath, $filepath);
                $all_success = $all_success && $success;
            } catch (Exception $e) {
                $all_success = false;
            }
        }
    }
    if ($all_success) {
        try {
            waFiles::delete(wa()->getDataPath('photo', true, 'contacts', false));
        } catch (waException $e) {

        }
    }
}